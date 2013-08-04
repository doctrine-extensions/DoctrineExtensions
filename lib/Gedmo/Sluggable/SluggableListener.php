<?php

namespace Gedmo\Sluggable;

use Gedmo\Mapping\MappedEventSubscriber;
use Gedmo\Exception\InvalidArgumentException;
use Gedmo\Mapping\ObjectManagerHelper as OMH;
use Doctrine\Common\EventArgs;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Cursor;

/**
 * The SluggableListener handles the generation of slugs
 * for documents and entities.
 *
 * This behavior can inpact the performance of your application
 * since it does some additional calculations on persisted objects.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Klein Florian <florian.klein@free.fr>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SluggableListener extends MappedEventSubscriber
{
    /**
     * {@inheritDoc}
     */
    protected $ignoredFilters = array(
        'Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter'
    );

    /**
     * Transliteration callback for slugs
     *
     * @var callable
     */
    private $transliterator = array('Gedmo\Sluggable\Util\Urlizer', 'transliterate');

    /**
     * Urlize callback for slugs
     *
     * @var callable
     */
    private $urlizer = array('Gedmo\Sluggable\Util\Urlizer', 'urlize');

    /**
     * List of inserted slugs for each object class.
     * This is needed in case there are identical slug
     * composition in number of persisted objects
     * during the same flush
     *
     * @var array
     */
    private $persisted = array();

    /**
     * Specifies the list of events to listen
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            'onFlush',
            'loadClassMetadata',
            'prePersist'
        );
    }

    /**
     * Set the transliteration callable method
     * to transliterate slugs
     *
     * @param callable $callable
     * @throws \Gedmo\Exception\InvalidArgumentException
     * @return void
     */
    public function setTransliterator($callable)
    {
        if (!is_callable($callable)) {
            throw new InvalidArgumentException('Invalid transliterator callable parameter given');
        }
        $this->transliterator = $callable;
    }

    /**
     * Set the urlization callable method
     * to urlize slugs
     *
     * @param callable $callable
     */
    public function setUrlizer($callable)
    {
        if (!is_callable($callable)) {
            throw new InvalidArgumentException('Invalid urlizer callable parameter given');
        }
        $this->urlizer = $callable;
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
     * Get currently used urlizer callable
     *
     * @return callable
     */
    public function getUrlizer()
    {
        return $this->urlizer;
    }

    /**
     * Mapps additional metadata
     *
     * @param EventArgs $event
     */
    public function loadClassMetadata(EventArgs $event)
    {
        $this->loadMetadataForObjectClass(OMH::getObjectManagerFromEvent($event), $event->getClassMetadata());
    }

    /**
     * Allows identifier fields to be slugged as usual
     *
     * @param EventArgs $event
     */
    public function prePersist(EventArgs $event)
    {
        $om = OMH::getObjectManagerFromEvent($event);
        $object = OMH::getObjectFromEvent($event);
        $meta = $om->getClassMetadata(get_class($object));

        if ($exm = $this->getConfiguration($om, $meta->name)) {
            foreach ($exm->getSlugFields() as $slugField) {
                if ($meta->isIdentifier($slugField)) {
                    $meta->getReflectionProperty($slugField)->setValue($object, '__id__');
                }
            }
        }
    }

    /**
     * Generate slug on objects being updated during flush
     * if they require changing
     *
     * @param EventArgs $event
     * @return void
     */
    public function onFlush(EventArgs $event)
    {
        $this->persisted = array();
        $om = OMH::getObjectManagerFromEvent($event);
        $uow = $om->getUnitOfWork();

        $this->disableFilters($om);

        // process all objects being inserted, using scheduled insertions instead
        // of prePersist in case if record will be changed before flushing this will
        // ensure correct result. No additional overhead is encoutered
        foreach (OMH::getScheduledObjectInsertions($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            if ($this->getConfiguration($om, $meta->name)) {
                // generate first to exclude this object from similar persisted slugs result
                $this->generateSlug($om, $object);
                $this->persisted[OMH::getRootObjectClass($meta)][] = $object;
            }
        }
        // we use onFlush and not preUpdate event to let other
        // event listeners be nested together
        foreach (OMH::getScheduledObjectUpdates($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            if ($this->getConfiguration($om, $meta->name) && !$uow->isScheduledForInsert($object)) {
                $this->generateSlug($om, $object);
                $this->persisted[OMH::getRootObjectClass($meta)][] = $object;
            }
        }

        $this->enableFilters($om);
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
     * @param \Doctrine\Common\Persistence\ObjectManager $om
     * @param object $object
     * @throws UnexpectedValueException - if parameters are missing or invalid
     */
    private function generateSlug(ObjectManager $om, $object)
    {
        $meta = $om->getClassMetadata(get_class($object));
        $uow = $om->getUnitOfWork();
        $changeSet = OMH::getObjectChangeSet($uow, $object);
        $isInsert = $uow->isScheduledForInsert($object);
        $exm = $this->getConfiguration($om, $meta->name);
        foreach ($exm->getSlugFields() as $slugField) {
            $options = $exm->getSlugMapping($slugField);
            // collect the slug from fields
            $slug = $meta->getReflectionProperty($slugField)->getValue($object);
            // if slug should not be updated, skip it
            if (!$options['updatable'] && !$isInsert && (!isset($changeSet[$slugField]) || $slug === '__id__')) {
                continue;
            }
            // must fetch the old slug from changeset, since $object holds the new version
            $oldSlug = isset($changeSet[$slugField]) ? $changeSet[$slugField][0] : $slug;
            $needToChangeSlug = false;
            // if slug is null, regenerate it, or needs an update
            if (null === $slug || $slug === '__id__' || !isset($changeSet[$slugField])) {
                $slug = '';
                foreach ($options['fields'] as $sluggableField) {
                    if (isset($changeSet[$sluggableField]) || isset($changeSet[$slugField])) {
                        $needToChangeSlug = true;
                    }
                    $slug .= $meta->getReflectionProperty($sluggableField)->getValue($object) . ' ';
                }
            } else {
                // slug was set manually
                $needToChangeSlug = true;
            }
            // if slug is changed, do further processing
            if ($needToChangeSlug) {
                $mapping = $meta->getFieldMapping($slugField);
                // build the slug
                // Step 1: transliteration, changing 北京 to 'Bei Jing'
                $slug = call_user_func_array(
                    $this->transliterator,
                    array($slug, $options['separator'], $object)
                );
                // Step 2: urlization (replace spaces by '-' etc...)
                $slug = call_user_func($this->urlizer, $slug, $options['separator']);
                // Step 3: stylize the slug
                switch ($options['style']) {
                    case 'camel':
                        $slug = preg_replace_callback('/^[a-z]|' . $options['separator'] . '[a-z]/smi', function ($m) {
                            return strtoupper($m[0]);
                        }, $slug);
                        break;

                    case 'lower':
                        if (function_exists('mb_strtolower')) {
                            $slug = mb_strtolower($slug);
                        } else {
                            $slug = strtolower($slug);
                        }
                        break;

                    case 'upper':
                        if (function_exists('mb_strtoupper')) {
                            $slug = mb_strtoupper($slug);
                        } else {
                            $slug = strtoupper($slug);
                        }
                        break;

                    default:
                        // leave it as is
                        break;
                }

                // cut slug if exceeded in length
                if (isset($mapping['length']) && strlen($slug) > $mapping['length']) {
                    $slug = substr($slug, 0, $mapping['length']);
                }

                // add suffix/prefix
                $slug = $options['prefix'] . $slug . $options['suffix'];

                if (isset($mapping['nullable']) && $mapping['nullable'] && !$slug) {
                    $slug = null;
                }
                // make unique slug if requested
                if ($options['unique'] && null !== $slug) {
                    $slug = $this->makeUniqueSlug($om, $object, $slugField, $slug, 0, false);
                }
                // set final slug
                $meta->getReflectionProperty($slugField)->setValue($object, $slug);
                // notify about changes
                $uow->propertyChanged($object, $slugField, $oldSlug, $slug);

                // recompute changeset
                OMH::recomputeSingleObjectChangeSet($uow, $meta, $object);
            }
        }
    }

    /**
     * Generates the unique slug
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $om
     * @param object $object
     * @param string $slugField - specific slug field
     * @param string $preferedSlug
     * @param integer $exponent
     * @param boolean $recurse - status if in recursion
     *
     * @return string - unique slug
     */
    private function makeUniqueSlug(ObjectManager $om, $object, $slugField, $preferedSlug, $exponent, $recurse)
    {
        $meta = $om->getClassMetadata(get_class($object));
        $exm = $this->getConfiguration($om, $meta->name);
        $options = $exm->getSlugMapping($slugField);
        $similarPersisted = array();
        $slugProp = $meta->getReflectionProperty($slugField);
        $sep = $options['separator']; // shortcut

        // extract unique base
        $base = false;
        if ($options['unique'] && isset($options['unique_base'])) {
            $base = $meta->getReflectionProperty($options['unique_base'])->getValue($object);
        }
        // collect similar persisted slugs during this flush
        if (isset($this->persisted[$options['rootClass']])) {
            foreach ($this->persisted[$options['rootClass']] as $obj) {
                if ($base !== false && $meta->getReflectionProperty($options['unique_base'])->getValue($obj) !== $base) {
                    continue; // if unique_base field is not the same, do not take slug as similar
                }
                if (preg_match("@^{$preferedSlug}.*@smi", $slug = $slugProp->getValue($obj))) {
                    $similarPersisted[] = $slug;
                }
            }
        }
        // load similar slugs
        $sameSlugs = array_merge($this->getSimilarSlugs($om, $object, $slugField, $preferedSlug), $similarPersisted);
        // leave only right slugs, if not in recursion
        if (!$recurse) {
            // filter similar slugs
            foreach ($sameSlugs as $key => $similar) {
                if (!preg_match("@{$preferedSlug}($|{$sep}[\d]+$)@smi", $similar)) {
                    unset($sameSlugs[$key]);
                }
            }
        }

        if ($sameSlugs) {
            $i = pow(10, $exponent);
            do {
                $generatedSlug = $preferedSlug . $sep . $i++;
            } while (in_array($generatedSlug, $sameSlugs));

            $mapping = $meta->getFieldMapping($slugField);
            if (isset($mapping['length']) && strlen($generatedSlug) > $mapping['length']) {
                $generatedSlug = substr($generatedSlug, 0, $mapping['length'] - (strlen($i) + strlen($sep)));
                if (substr($generatedSlug, -strlen($sep)) == $sep) {
                    $generatedSlug = substr($generatedSlug, 0, strlen($generatedSlug) - strlen($sep));
                }
                $generatedSlug = $this->makeUniqueSlug($om, $object, $slugField, $generatedSlug, strlen($i) - 1, true);
            }
            $preferedSlug = $generatedSlug;
        }
        return $preferedSlug;
    }

    /**
     * Loads the similar slugs
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $om
     * @param object $object
     * @param string $slugField - specific slug field
     * @param string $slug
     *
     * @return array
     */
    protected function getSimilarSlugs(ObjectManager $om, $object, $slugField, $slug)
    {
        $meta = $om->getClassMetadata(get_class($object));
        $exm = $this->getConfiguration($om, $meta->name);
        $options = $exm->getSlugMapping($slugField);
        $ids = OMH::getIdentifier($om, $object, false);

        $similar = array();
        if ($om instanceof EntityManager) {
            $qb = $om->createQueryBuilder();
            $qb->select('rec.' . $slugField)
                ->from($options['rootClass'], 'rec')
                ->where($qb->expr()->like(
                    'rec.' . $slugField,
                    $qb->expr()->literal($slug . '%')
                ));

            // use the unique_base to restrict the uniqueness check
            if ($options['unique'] && isset($options['unique_base'])) {
                if ($ubase = $meta->getReflectionProperty($options['unique_base'])->getValue($object)) {
                    $qb->andWhere('rec.' . $options['unique_base'] . ' = :unique_base');
                    $qb->setParameter(':unique_base', $ubase);
                } else {
                    $qb->andWhere($qb->expr()->isNull('rec.' . $options['unique_base']));
                }
            }

            if ($ids) {
                // include identifiers
                foreach ($ids as $id => $value) {
                    if (!$meta->isIdentifier($slugField)) {
                        $qb->andWhere($qb->expr()->neq('rec.' . $id, ':' . $id));
                        $qb->setParameter($id, $value);
                    }
                }
            }
            $similar = $qb->getQuery()->getArrayResult();
        } elseif ($om instanceof DocumentManager) {
            $qb = $om->createQueryBuilder($options['rootClass']);
            if ($ids && ($id = current($ids)) && !$meta->isIdentifier($slugField)) {
                $qb->field($meta->identifier)->notEqual($id);
            }
            $qb->field($slugField)->equals(new \MongoRegex('/^' . preg_quote($slug, '/') . '/'));

            // use the unique_base to restrict the uniqueness check
            if ($options['unique'] && isset($options['unique_base'])) {
                if (is_object($ubase = $meta->getReflectionProperty($options['unique_base'])->getValue($object))) {
                    $qb->field($options['unique_base'] . '.$id')->equals(new \MongoId(OMH::getIdentifier($om, $ubase)));
                } elseif ($ubase) {
                    $qb->where('/^' . preg_quote($ubase, '/') . '/.test(this.' . $options['unique_base'] . ')');
                } else {
                    $qb->field($options['unique_base'])->equals(null);
                }
            }
            $q = $qb->getQuery();
            $q->setHydrate(false);

            $similar = $q->execute();
            if ($similar instanceof Cursor) {
                $similar = $similar->toArray();
            }
        }
        return array_map(function(array $item) use($slugField) {
            return $item[$slugField];
        }, $similar);
    }
}
