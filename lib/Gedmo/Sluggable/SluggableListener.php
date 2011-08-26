<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventArgs;
use Gedmo\Mapping\MappedEventSubscriber;
use Gedmo\Sluggable\Mapping\Event\SluggableAdapter;
use Doctrine\Common\Persistence\ObjectManager;

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
     * List of inserted slugs for each object class.
     * This is needed in case there are identical slug
     * composition in number of persisted objects
     *
     * @var array
     */
    private $persistedSlugs = array();

    /**
     * List of initialized slug handlers
     *
     * @var array
     */
    private $handlers = array();

    /**
     * Specifies the list of events to listen
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            'onFlush',
            'loadClassMetadata'
        );
    }

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
     * Get currently used transliterator callable
     *
     * @return callable
     */
    public function getTransliterator()
    {
        return $this->transliterator;
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

        // process all objects being inserted, using scheduled insertions instead
        // of prePersist in case if record will be changed before flushing this will
        // ensure correct result. No additional overhead is encoutered
        foreach ($ea->getScheduledObjectInsertions($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            if ($config = $this->getConfiguration($om, $meta->name)) {
                // generate first to exclude this object from similar persisted slugs result
                $this->generateSlug($ea, $object);
                foreach ($config['fields'] as $slugField => $fieldsForSlugField) {
                    $slug = $meta->getReflectionProperty($slugField)->getValue($object);
                    $this->persistedSlugs[$config['useObjectClass']][$slugField][] = $slug;
                }
            }
        }
        // we use onFlush and not preUpdate event to let other
        // event listeners be nested together
        foreach ($ea->getScheduledObjectUpdates($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            if ($config = $this->getConfiguration($om, $meta->name)) {
                foreach ($config['slugFields'] as $slugField) {
                    if ($slugField['updatable']) {
                        $this->generateSlug($ea, $object);
                    }
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
     * Get the slug handler instance by $class name
     *
     * @param string $class
     * @return Gedmo\Sluggable\Handler\SlugHandlerInterface
     */
    private function getHandler($class)
    {
        if (!isset($this->handlers[$class])) {
            $this->handlers[$class] = new $class($this);
        }
        return $this->handlers[$class];
    }

    /**
     * Creates the slug for object being flushed
     *
     * @param SluggableAdapter $ea
     * @param object $object
     * @throws UnexpectedValueException - if parameters are missing
     *      or invalid
     * @return void
     */
    private function generateSlug(SluggableAdapter $ea, $object)
    {
        $om = $ea->getObjectManager();
        $meta = $om->getClassMetadata(get_class($object));
        $uow = $om->getUnitOfWork();
        $changeSet = $ea->getObjectChangeSet($uow, $object);
        $config = $this->getConfiguration($om, $meta->name);
        foreach ($config['fields'] as $slugField => $fieldsForSlugField) {
            // sort sluggable fields by position
            $fields = $fieldsForSlugField;
            usort($fields, function($a, $b) {
                if ($a['position'] == $b['position']) {
                    return 1;
                }
                return ($a['position'] < $b['position']) ? -1 : 1;
            });

            $slugFieldConfig = $config['slugFields'][$slugField];
            // collect the slug from fields
            $slug = '';
            $needToChangeSlug = false;
            foreach ($fields as $sluggableField) {
                if (isset($changeSet[$sluggableField['field']])) {
                    $needToChangeSlug = true;
                }
                $slug .= $meta->getReflectionProperty($sluggableField['field'])->getValue($object) . ' ';
            }
            // notify slug handlers --> onChangeDecision
            if (isset($config['handlers'])) {
                foreach ($config['handlers'] as $class => $options) {
                    $this
                        ->getHandler($class)
                        ->onChangeDecision($ea, $slugFieldConfig, $object, $slug, $needToChangeSlug)
                    ;
                }
            }
            // if slug is changed, do further processing
            if ($needToChangeSlug) {
                $mapping = $meta->getFieldMapping($slugFieldConfig['slug']);
                if (!strlen(trim($slug)) && !$mapping['nullable']) {
                    throw new \Gedmo\Exception\UnexpectedValueException("Unable to find any non empty sluggable fields for slug [{$slugField}] , make sure they have something at least.");
                }

                // notify slug handlers --> postSlugBuild
                if (isset($config['handlers'])) {
                    foreach ($config['handlers'] as $class => $options) {
                        $this
                            ->getHandler($class)
                            ->postSlugBuild($ea, $slugFieldConfig, $object, $slug)
                        ;
                    }
                }

                // build the slug
                $slug = call_user_func_array(
                    $this->transliterator,
                    array($slug, $slugFieldConfig['separator'], $object)
                );
                // stylize the slug
                switch ($slugFieldConfig['style']) {
                    case 'camel':
                        $slug = preg_replace_callback(
                            '@^[a-z]|' . $slugFieldConfig['separator'] . '[a-z]@smi',
                            create_function('$m', 'return strtoupper($m[0]);'),
                            $slug
                        );
                        break;

                    default:
                        // leave it as is
                        break;
                }

                // cut slug if exceeded in length
                if (isset($mapping['length']) && strlen($slug) > $mapping['length']) {
                    $slug = substr($slug, 0, $mapping['length']);
                }

                if ($mapping['nullable'] && !$slug) {
                    $slug = null;
                }
                // make unique slug if requested
                if ($slugFieldConfig['unique'] && !is_null($slug)) {
                    $this->exponent = 0;
                    $arrayConfig = $slugFieldConfig;
                    $arrayConfig['useObjectClass'] = $config['useObjectClass'];
                    $slug = $this->makeUniqueSlug($ea, $object, $slug, false, $arrayConfig);
                }
                // notify slug handlers --> onSlugCompletion
                if (isset($config['handlers'])) {
                    foreach ($config['handlers'] as $class => $options) {
                        $this
                            ->getHandler($class)
                            ->onSlugCompletion($ea, $slugFieldConfig, $object, $slug)
                        ;
                    }
                }
                // set the final slug
                $meta->getReflectionProperty($slugFieldConfig['slug'])->setValue($object, $slug);
                // recompute changeset
                $ea->recomputeSingleObjectChangeSet($uow, $meta, $object);
            }
        }
    }

    /**
     * Generates the unique slug
     *
     * @param SluggableAdapter $ea
     * @param object $object
     * @param string $preferedSlug
     * @param array $config[$slugField]
     * @return string - unique slug
     */
    private function makeUniqueSlug(SluggableAdapter $ea, $object, $preferedSlug, $recursing = false, $config = array())
    {
        $om = $ea->getObjectManager();
        $meta = $om->getClassMetadata(get_class($object));
        if (count ($config) == 0)
        {
            $config = $this->getConfiguration($om, $meta->name);
        }
        // search for similar slug
        $result = $ea->getSimilarSlugs($object, $meta, $config, $preferedSlug);
        // add similar persisted slugs into account
        $result += $this->getSimilarPersistedSlugs($config['useObjectClass'], $preferedSlug, $config['slug']);
        // leave only right slugs
        if (!$recursing) {
            $this->filterSimilarSlugs($result, $config, $preferedSlug);
        }

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
                $generatedSlug = $this->makeUniqueSlug($ea, $object, $generatedSlug, true, $config);
            }
            $preferedSlug = $generatedSlug;
        }
        return $preferedSlug;
    }

    /**
     * In case if any number of records are persisted instantly
     * and they contain same slugs. This method will filter those
     * identical slugs specialy for persisted objects. Returns
     * array of similar slugs found
     *
     * @param string $class
     * @param string $preferedSlug
     * @param string $slugField
     * @return array
     */
    private function getSimilarPersistedSlugs($class, $preferedSlug, $slugField)
    {
        $result = array();
        if (isset($this->persistedSlugs[$class][$slugField])) {
            array_walk($this->persistedSlugs[$class][$slugField], function($val) use ($preferedSlug, &$result, $slugField) {
                if (preg_match("@^{$preferedSlug}.*@smi", $val)) {
                    $result[] = array($slugField => $val);
                }
            });
        }
        return $result;
    }

    /**
     * Filters $slugs which are matched as prefix but are
     * simply shorter slugs
     *
     * @param array $slugs
     * @param array $config
     * @param string $prefered
     */
    private function filterSimilarSlugs(array &$slugs, array &$config, $prefered)
    {
        foreach ($slugs as $key => $similar) {
            if (!preg_match("@{$prefered}($|{$config['separator']}[\d]+$)@smi", $similar[$config['slug']])) {
                unset($slugs[$key]);
            }
        }
    }
}