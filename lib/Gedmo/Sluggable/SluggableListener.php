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
 * This behavior can impact the performance of your application
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
        'Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter',
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
            'prePersist',
        );
    }

    /**
     * Set the transliteration callable method
     * to transliterate slugs
     *
     * @param callable $callable
     *
     * @throws \Gedmo\Exception\InvalidArgumentException
     *
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
            $uow = $om->getUnitOfWork();
            foreach ($exm->getFields() as $slugField) {
                $options = $exm->getOptions($slugField);
                // generate if was not set manually
                if (!$slug = $meta->getReflectionProperty($slugField)->getValue($object)) {
                    $slug = $this->generateSlug($om, $object, $slugField);
                }
                // checks length, prefixes, uniquiness
                $slug = $this->processSlug($om, $object, $slugField, $slug);
                // set final slug
                $meta->getReflectionProperty($slugField)->setValue($object, $slug);
                // notify about changes
                $uow->propertyChanged($object, $slugField, null, $slug);
                // keep it for unique check
                $this->persisted[$options['rootClass']][] = $object;
            }
        }
    }

    /**
     * Generate slug on objects being updated during flush
     * if they require changing
     *
     * @param EventArgs $event
     */
    public function onFlush(EventArgs $event)
    {
        $om = OMH::getObjectManagerFromEvent($event);
        $uow = $om->getUnitOfWork();

        // we use onFlush and not preUpdate event to let other
        // event listeners be nested together
        foreach (OMH::getScheduledObjectUpdates($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            if (($exm = $this->getConfiguration($om, $meta->name)) && !$uow->isScheduledForInsert($object)) {
                $changeSet = OMH::getObjectChangeSet($uow, $object);
                foreach ($exm->getFields() as $slugField) {
                    $options = $exm->getOptions($slugField);
                    // only updatable slug can be updated
                    if (!$options['updatable'] && !isset($changeSet[$slugField])) {
                        $this->persisted[$options['rootClass']][] = $object;
                        continue;
                    }
                    $slug = $meta->getReflectionProperty($slugField)->getValue($object);
                    $oldSlug = isset($changeSet[$slugField]) ? $changeSet[$slugField][0] : $slug;
                    // if any of slug fields has changed, or slugfield was forced, do update
                    if (null === $slug || !isset($changeSet[$slugField])) {
                        $slug = $this->generateSlug($om, $object, $slugField);
                    }
                    // checks length, prefixes, uniquiness
                    $slug = $this->processSlug($om, $object, $slugField, $slug);
                    // set final slug
                    $meta->getReflectionProperty($slugField)->setValue($object, $slug);
                    // notify about changes
                    $uow->propertyChanged($object, $slugField, $oldSlug, $slug);
                    // recompute changeset
                    OMH::recomputeSingleObjectChangeSet($uow, $meta, $object);
                    // keep it for unique check
                    $this->persisted[$options['rootClass']][] = $object;
                }
            }
        }

        $this->persisted = array();
    }

    /**
     * {@inheritDoc}
     */
    protected function getNamespace()
    {
        return __NAMESPACE__;
    }

    /**
     * Polishes slug which was newly generated or set
     * trims it if it was too long and makes it unique
     *
     * @param ObjectManager $om
     * @param object        $object
     * @param string        $slugField
     * @param string        $slug      - slug to process
     *
     * @return string
     */
    private function processSlug(ObjectManager $om, $object, $slugField, $slug)
    {
        $meta = $om->getClassMetadata(get_class($object));
        $exm = $this->getConfiguration($om, $meta->name);
        $options = $exm->getOptions($slugField);
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
                $slug = preg_replace_callback('/^[a-z]|'.$options['separator'].'[a-z]/smi', function ($m) {
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

        $length = isset($mapping['length']) ? $mapping['length'] : false;
        if ($length && $om->getConnection()->getDatabasePlatform()->getName() === 'postgresql') {
            // special case for postgresql
            $length--;
        }

        // cut slug if exceeded in length
        if ($length && strlen($slug) > $length) {
            $slug = substr($slug, 0, $length);
        }

        // add suffix/prefix
        $slug = $options['prefix'].$slug.$options['suffix'];

        if (isset($mapping['nullable']) && $mapping['nullable'] && !$slug) {
            $slug = null;
        }
        // make unique slug if requested
        if ($options['unique'] && null !== $slug) {
            $this->disableFilters($om);
            $slug = $this->makeUniqueSlug($om, $object, $slugField, $slug, 0, false);
            $this->enableFilters($om);
        }

        return $slug;
    }

    /**
     * Creates the slug from sluggable fields
     *
     * @param ObjectManager $om
     * @param object        $object
     * @param string        $slugField
     *
     * @return string
     */
    private function generateSlug(ObjectManager $om, $object, $slugField)
    {
        $meta = $om->getClassMetadata(get_class($object));
        $exm = $this->getConfiguration($om, $meta->name);
        $options = $exm->getOptions($slugField);
        // collect the slug from fields
        $slug = '';

        foreach ($options['fields'] as $sluggableField) {
            $value = $meta->getReflectionProperty($sluggableField)->getValue($object);
            $slug .= ($value instanceof \DateTime) ? $value->format($options['dateFormat']) : $value;
            $slug .= ' ';
        }

        return $slug;
    }

    /**
     * Generates the unique slug
     *
     * @param ObjectManager $om
     * @param object        $object
     * @param string        $slugField     - specific slug field
     * @param string        $preferredSlug
     * @param integer       $exponent
     * @param boolean       $recurse       - status if in recursion
     *
     * @return string - unique slug
     */
    private function makeUniqueSlug(ObjectManager $om, $object, $slugField, $preferredSlug, $exponent, $recurse)
    {
        $meta = $om->getClassMetadata(get_class($object));
        $exm = $this->getConfiguration($om, $meta->name);
        $options = $exm->getOptions($slugField);
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
                if (preg_match("@^{$preferredSlug}.*@smi", $slug = $slugProp->getValue($obj))) {
                    $similarPersisted[] = $slug;
                }
            }
        }

        // load similar slugs
        $sameSlugs = array_merge($this->getSimilarSlugs($om, $object, $slugField, $preferredSlug), $similarPersisted);
        // leave only right slugs, if not in recursion
        if (!$recurse) {
            // filter similar slugs
            foreach ($sameSlugs as $key => $similar) {
                if (!preg_match("@{$preferredSlug}($|{$sep}[\d]+$)@smi", $similar)) {
                    unset($sameSlugs[$key]);
                }
            }
        }

        if ($sameSlugs) {
            $i = pow(10, $exponent);
            do {
                $generatedSlug = $preferredSlug.$sep.$i++;
            } while (in_array($generatedSlug, $sameSlugs));

            $mapping = $meta->getFieldMapping($slugField);
            $length = isset($mapping['length']) ? $mapping['length'] : false;
            if ($length && $om->getConnection()->getDatabasePlatform()->getName() === 'postgresql') {
                // special case for postgresql
                $length--;
            }
            if ($length && strlen($generatedSlug) > $length) {
                $generatedSlug = substr($generatedSlug, 0, $length - (strlen($i) + strlen($sep)));
                if (substr($generatedSlug, -strlen($sep)) == $sep) {
                    $generatedSlug = substr($generatedSlug, 0, strlen($generatedSlug) - strlen($sep));
                }
                $generatedSlug = $this->makeUniqueSlug($om, $object, $slugField, $generatedSlug, strlen($i) - 1, true);
            }
            $preferredSlug = $generatedSlug;
        }

        return $preferredSlug;
    }

    /**
     * Loads the similar slugs
     *
     * @param ObjectManager $om
     * @param object        $object
     * @param string        $slugField - specific slug field
     * @param string        $slug
     *
     * @return array
     */
    protected function getSimilarSlugs(ObjectManager $om, $object, $slugField, $slug)
    {
        $meta = $om->getClassMetadata(get_class($object));
        $exm = $this->getConfiguration($om, $meta->name);
        $options = $exm->getOptions($slugField);
        $ids = OMH::getIdentifier($om, $object, false);

        $similar = array();
        if ($om instanceof EntityManager) {
            $qb = $om->createQueryBuilder();
            $qb->select('rec.'.$slugField)
                ->from($options['rootClass'], 'rec')
                ->where($qb->expr()->like('rec.'.$slugField, ':slug'))
                ->setParameter('slug', $slug.'%');

            // use the unique_base to restrict the uniqueness check
            if ($options['unique'] && isset($options['unique_base'])) {
                $ubase = $meta->getReflectionProperty($options['unique_base'])->getValue($object);
                if ($meta->isSingleValuedAssociation($options['unique_base'])) {
                    $qb->join($meta->getAssociationTargetClass($options['unique_base']), 'unique_'.$options['unique_base']);
                } elseif ($ubase) {
                    $qb->andWhere('rec.'.$options['unique_base'].' = :unique_base');
                    $qb->setParameter('unique_base', $ubase);
                } else {
                    $qb->andWhere($qb->expr()->isNull('rec.'.$options['unique_base']));
                }
            }

            if ($ids) {
                // include identifiers
                foreach ($ids as $id => $value) {
                    if (!$meta->isIdentifier($slugField)) {
                        $qb->andWhere($qb->expr()->neq('rec.'.$id, ':'.$id));
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
            $qb->field($slugField)->equals(new \MongoRegex('/^'.preg_quote($slug, '/').'/'));

            // use the unique_base to restrict the uniqueness check
            if ($options['unique'] && isset($options['unique_base'])) {
                if (is_object($ubase = $meta->getReflectionProperty($options['unique_base'])->getValue($object))) {
                    $qb->field($options['unique_base'].'.$id')->equals(new \MongoId(OMH::getIdentifier($om, $ubase)));
                } elseif ($ubase) {
                    $qb->where('/^'.preg_quote($ubase, '/').'/.test(this.'.$options['unique_base'].')');
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

        return array_map(function (array $item) use ($slugField) {
            return $item[$slugField];
        }, $similar);
    }
}
