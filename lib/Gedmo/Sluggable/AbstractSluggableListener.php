<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventArgs,
    Gedmo\Mapping\MappedEventSubscriber;

/**
 * The AbstractSluggableListener is an abstract class
 * of sluggable listener in order to support diferent
 * object managers.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Klein Florian <florian.klein@free.fr>
 * @subpackage AbstractSluggableListener
 * @package Gedmo.Sluggable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
abstract class AbstractSluggableListener extends MappedEventSubscriber
{
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
    private $transliterator = array('Gedmo\Sluggable\Util\Urlizer', 'urlize');

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
        $this->loadMetadataForObjectClass($this->getObjectManager($eventArgs), $eventArgs->getClassMetadata());
    }

    /**
     * Checks for persisted object to specify slug
     *
     * @param EventArgs $args
     * @return void
     */
    public function prePersist(EventArgs $args)
    {
        $om = $this->getObjectManager($args);
        $object = $this->getObject($args);
        $meta = $om->getClassMetadata(get_class($object));

        if ($config = $this->getConfiguration($om, $meta->name)) {
            $this->generateSlug($om, $object, false);
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
        $om = $this->getObjectManager($args);
        $uow = $om->getUnitOfWork();

        // we use onFlush and not preUpdate event to let other
        // event listeners be nested together
        foreach ($this->getScheduledObjectUpdates($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            if ($config = $this->getConfiguration($om, $meta->name)) {
                if ($config['updatable']) {
                    $this->generateSlug($om, $object, $this->getObjectChangeSet($uow, $object));
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
     * @param ObjectManager $om
     * @param object $object
     * @param mixed $changeSet
     *      case array: the change set array
     *      case boolean(false): object is not managed
     * @throws UnexpectedValueException - if parameters are missing
     *      or invalid
     * @return void
     */
    protected function generateSlug($om, $object, $changeSet)
    {
        $meta = $om->getClassMetadata(get_class($object));
        $uow = $om->getUnitOfWork();
        $config = $this->getConfiguration($om, $meta->name);

        // collect the slug from fields
        $slug = '';
        $needToChangeSlug = false;
        foreach ($config['fields'] as $sluggableField) {
            if ($changeSet === false || isset($changeSet[$sluggableField])) {
                $needToChangeSlug = true;
            }
            $slug .= $meta->getReflectionProperty($sluggableField)->getValue($object) . ' ';
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
            $slug = $this->makeUniqueSlug($om, $object, $slug);
        }
        // set the final slug
        $meta->getReflectionProperty($config['slug'])->setValue($object, $slug);
        // recompute changeset if object is managed
        if ($changeSet !== false) {
            $this->recomputeSingleObjectChangeSet($uow, $meta, $object);
        }
    }

    /**
     * Generates the unique slug
     *
     * @param ObjectManager $om
     * @param object $object
     * @param string $preferedSlug
     * @return string - unique slug
     */
    protected function makeUniqueSlug($om, $object, $preferedSlug)
    {
        $meta = $om->getClassMetadata(get_class($object));
        $config = $this->getConfiguration($om, $meta->name);

        // search for similar slug
        $result = $this->getUniqueSlugResult($om, $object, $meta, $config, $preferedSlug);

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
                $generatedSlug = $this->makeUniqueSlug($om, $object, $generatedSlug);
            }
            $preferedSlug = $generatedSlug;
        }
        return $preferedSlug;
    }

    /**
     * Get the ObjectManager from EventArgs
     *
     * @param EventArgs $args
     * @return ObjectManager
     */
    abstract protected function getObjectManager(EventArgs $args);

    /**
     * Get the Object from EventArgs
     *
     * @param EventArgs $args
     * @return object
     */
    abstract protected function getObject(EventArgs $args);

    /**
     * Get the object changeset from a UnitOfWork
     *
     * @param UnitOfWork $uow
     * @param Object $object
     * @return array
     */
    abstract protected function getObjectChangeSet($uow, $object);

    /**
     * Recompute the single object changeset from a UnitOfWork
     *
     * @param UnitOfWork $uow
     * @param ClassMetadata $meta
     * @param Object $object
     * @return void
     */
    abstract protected function recomputeSingleObjectChangeSet($uow, $meta, $object);

    /**
     * Get the scheduled object updates from a UnitOfWork
     *
     * @param UnitOfWork $uow
     * @return array
     */
    abstract protected function getScheduledObjectUpdates($uow);

    /**
     * Loads the similar slugs
     *
     * @param ObjectManager $om
     * @param object $object
     * @param ClassMetadata $meta
     * @param array $config
     * @param string $preferedSlug
     * @return array
     */
    abstract protected function getUniqueSlugResult($om, $object, $meta, array $config, $preferedSlug);
}