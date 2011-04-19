<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventArgs,
    Doctrine\Common\Persistence\ObjectManager,
    Doctrine\Common\Persistence\Mapping\ClassMetadata,
    Gedmo\Mapping\MappedEventSubscriber,
    Gedmo\Sluggable\Mapping\Event\SluggableAdapter;

/**
 * The SluggableListener handles the generation of slugs
 * for documents and entities.
 *
 * This behavior can inpact the performance of your application
 * since it does some additional calculations on persisted objects.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Klein Florian <florian.klein@free.fr>
 * @subpackage SluggableListener
 * @package Gedmo.Sluggable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SluggableListener extends MappedEventSubscriber
{
    /**
     * Specifies the list of events to listen
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            'prePersist',
            'onFlush',
            'loadClassMetadata'
        );
    }

    /**
     * The power exponent to jump
     * the slug unique number by tens.
     *
     * @var integer
     */
    private $exponent = 0;

    /**
     * Transliteration callback for slugs
     *
     * @var array
     */
    private $transliterator = array('Gedmo\Sluggable\Util\Urlizer', 'transliterate');

    /**
     * Set the transliteration callable method
     * to transliterate slugs
     *
     * @param mixed $callable
     */
    public function setTransliterator($callable)
    {
        if (!is_callable($callable)) {
            throw new \Gedmo\Exception\InvalidArgumentException('Invalid transliterator callable parameter given');
        }
        $this->transliterator = $callable;
    }

    /**
     * Mapps additional metadata
     *
     * @param EventArgs $eventArgs
     * @return void
     */
    public function loadClassMetadata(EventArgs $eventArgs)
    {
        $ea = $this->getEventAdapter($eventArgs);
        $this->loadMetadataForObjectClass($ea->getObjectManager(), $eventArgs->getClassMetadata());
    }

    /**
     * Checks for persisted object to specify slug
     *
     * @param EventArgs $args
     * @return void
     */
    public function prePersist(EventArgs $args)
    {
        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
        $object = $ea->getObject();
        $meta = $om->getClassMetadata(get_class($object));

        if ($config = $this->getConfiguration($om, $meta->name)) {
            $this->generateSlug($ea, $object, false);
        }
    }

    /**
     * Generate slug on objects being updated during flush
     * if they require changing
     *
     * @param EventArgs $args
     * @return void
     */
    public function onFlush(EventArgs $args)
    {
        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
        $uow = $om->getUnitOfWork();

        // we use onFlush and not preUpdate event to let other
        // event listeners be nested together
        foreach ($ea->getScheduledObjectUpdates($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            if ($config = $this->getConfiguration($om, $meta->name)) {
                if ($config['updatable']) {
                    $this->generateSlug($ea, $object, $ea->getObjectChangeSet($uow, $object));
                }
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function getNamespace()
    {
        return __NAMESPACE__;
    }

    /**
     * Creates the slug for object being flushed
     *
     * @param SluggableAdapter $ea
     * @param object $object
     * @param mixed $changeSet
     *      case array: the change set array
     *      case boolean(false): object is not managed
     * @throws UnexpectedValueException - if parameters are missing
     *      or invalid
     * @return void
     */
    protected function generateSlug(SluggableAdapter $ea, $object, $changeSet)
    {
        $om = $ea->getObjectManager();
        $meta = $om->getClassMetadata(get_class($object));
        $uow = $om->getUnitOfWork();
        $config = $this->getConfiguration($om, $meta->name);

        // sort sluggable fields by position
        $fields = $config['fields'];
        usort($fields, function($a, $b) {
            if ($a['position'] == $b['position']) {
                return 1;
            }
            return ($a['position'] < $b['position']) ? -1 : 1;
        });

        // collect the slug from fields
        $slug = '';
        $needToChangeSlug = false;
        foreach ($fields as $sluggableField) {
            if ($changeSet === false || isset($changeSet[$sluggableField['field']])) {
                $needToChangeSlug = true;
            }
            $slug .= $meta->getReflectionProperty($sluggableField['field'])->getValue($object) . ' ';
        }
        // if slug is not changed, no need further processing
        if (!$needToChangeSlug) {
            return; // nothing to do
        }

        if (!strlen(trim($slug))) {
            throw new \Gedmo\Exception\UnexpectedValueException('Unable to find any non empty sluggable fields, make sure they have something at least.');
        }

        // build the slug
        $slug = call_user_func_array(
            $this->transliterator,
            array($slug, $config['separator'], $object)
        );

        // stylize the slug
        switch ($config['style']) {
            case 'camel':
                $slug = preg_replace_callback(
                    '@^[a-z]|' . $config['separator'] . '[a-z]@smi',
                    create_function('$m', 'return strtoupper($m[0]);'),
                    $slug
                );
                break;

            default:
                // leave it as is
                break;
        }

        // cut slug if exceeded in length
        $mapping = $meta->getFieldMapping($config['slug']);
        if (isset($mapping['length']) && strlen($slug) > $mapping['length']) {
            $slug = substr($slug, 0, $mapping['length']);
        }

        // make unique slug if requested
        if ($config['unique']) {
            $this->exponent = 0;
            $slug = $this->makeUniqueSlug($ea, $object, $slug);
        }
        // set the final slug
        $meta->getReflectionProperty($config['slug'])->setValue($object, $slug);
        // recompute changeset if object is managed
        if ($changeSet !== false) {
            $ea->recomputeSingleObjectChangeSet($uow, $meta, $object);
        }
    }

    /**
     * Generates the unique slug
     *
     * @param SluggableAdapter $ea
     * @param object $object
     * @param string $preferedSlug
     * @return string - unique slug
     */
    protected function makeUniqueSlug(SluggableAdapter $ea, $object, $preferedSlug)
    {
        $om = $ea->getObjectManager();
        $meta = $om->getClassMetadata(get_class($object));
        $config = $this->getConfiguration($om, $meta->name);

        // search for similar slug
        $result = $ea->getSimilarSlugs($object, $meta, $config, $preferedSlug);

        if ($result) {
            $generatedSlug = $preferedSlug;
            $sameSlugs = array();
            foreach ((array)$result as $list) {
                $sameSlugs[] = $list[$config['slug']];
            }

            $i = pow(10, $this->exponent);
            do {
                $generatedSlug = $preferedSlug . $config['separator'] . $i++;
            } while (in_array($generatedSlug, $sameSlugs));

            $mapping = $meta->getFieldMapping($config['slug']);
            if (isset($mapping['length']) && strlen($generatedSlug) > $mapping['length']) {
                $generatedSlug = substr(
                    $generatedSlug,
                    0,
                    $mapping['length'] - (strlen($i) + strlen($config['separator']))
                );
                $this->exponent = strlen($i) - 1;
                $generatedSlug = $this->makeUniqueSlug($ea, $object, $generatedSlug);
            }
            $preferedSlug = $generatedSlug;
        }
        return $preferedSlug;
    }
}