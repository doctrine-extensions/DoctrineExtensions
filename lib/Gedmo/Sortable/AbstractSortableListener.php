<?php

namespace Gedmo\Sortable;

use Doctrine\Common\EventArgs,
    Doctrine\Common\Persistence\ObjectManager,
    Doctrine\Common\Persistence\Mapping\ClassMetadata,
    Gedmo\Mapping\MappedEventSubscriber;

/**
 * The AbstractTranslationListener is an abstract class
 * of translation listener in order to support diferent
 * object managers.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Translatable
 * @subpackage AbstractTranslationListener
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
abstract class AbstractSortableListener extends MappedEventSubscriber
{
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
     * Looks for translatable objects being inserted or updated
     * for further processing
     *
     * @param EventArgs $args
     * @return void
     */
    public function onFlush(EventArgs $args)
    {
        $om = $this->getObjectManager($args);
        $uow = $om->getUnitOfWork();
        $finalPositions = array();

        
        // add all scheduled inserts for Sortable objects at the end
        foreach ($this->getScheduledObjectInsertions($uow) as $object) {

            $meta = $om->getClassMetadata(get_class($object));
            $config = $this->getConfiguration($om, $meta->name);

            if (isset($config['sort'])) {
                $gid = $this->getParentIdentifier($object, $om);
    
                if (!isset($finalPositions[$meta->name][$gid])) {
                    $finalPositions[$meta->name][$gid] = $this->getFinalPosition($om, $object);
                }

                $meta->getReflectionProperty($config['sort'])->setValue($object, $finalPositions[$meta->name][$gid]);

                // fix for MongoDB issue
                // http://www.doctrine-project.org/jira/browse/MODM-134
                if (method_exists($object, 'getChilds')) {
                    $object->getChilds()->clear();
                }

                $this->recomputeSingleObjectChangeSet($uow, $meta, $object);
                $finalPositions[$meta->name][$gid]++;
            }
        }

        foreach ($this->getScheduledObjectUpdates($uow) as $object) {

            $meta = $om->getClassMetadata(get_class($object));
            $config = $this->getConfiguration($om, $meta->name);

            if (isset($config['sort']) && isset($config['sort_identifier'])) {
                $changeSet = $uow->getDocumentChangeSet($object);

                if (isset($changeSet[$config['sort_identifier']])) {
                    $identfierChange = $changeSet[$config['sort_identifier']];

                    $oldIdentifier = $identfierChange[0]->getId();
                    $subObjects = $this->getAllSortableAfterObject($object, $om, $oldIdentifier);
                    $reflProperty = $meta->getReflectionProperty($config['sort']);

                    foreach ($subObjects as $subObject) {
                        $this->updateSortableSort($om, $subObject, $reflProperty->getValue($subObject) - 1);
                    }

                    $identifier = $identfierChange[1];
                    $identifierMeta = $om->getClassMetadata(get_class($identifier));
                    $newIdentifier = $identifierMeta->getReflectionProperty($identifierMeta->identifier)
                        ->getValue($identifier);

                    $count = $this->countSortableForIdentifier($newIdentifier, $object, $om);
                    $meta->getReflectionProperty($config['sort'])->setValue($object, $count);
                    $this->recomputeSingleObjectChangeSet($uow, $meta, $object);
                }
            }
        }

        // demote all Sortable object under deleted one
        foreach ($this->getScheduledObjectDeletions($uow) as $object) {

            $meta = $om->getClassMetadata(get_class($object));
            $config = $this->getConfiguration($om, $meta->name);

            if (isset($config['sort'])) {

                $identifier = $this->getParentIdentifier($object, $om);
                $subObjects = $this->getAllSortableAfterObject($object, $om, $identifier);
                $reflProperty = $meta->getReflectionProperty($config['sort']);

                foreach ($subObjects as $subObject) {
                    $this->updateSortableSort($om, $subObject, $reflProperty->getValue($subObject) - 1);
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

    abstract protected function countSortableForIdentifier($identifier, $object, $om);

    /**
     * If Object as sort_identifier mapping, get the identifier of this field
     */
    abstract protected function getParentIdentifier($om, $object);

    /**
     * Update sort field of a Sortable object
     */
    abstract public function updateSortableSort($om, $object, $sort);

    /**
     * Get all Sortable object where sort field value is greater than object one
     */
    abstract public function getAllSortableAfterObject($object, $om, $identifier = null);

    /**
     * count all Sortable object for a branch, useful when we need to get the last index
     */
    abstract protected function getFinalPosition($om, $object);

    abstract protected function recomputeSingleObjectChangeSet($uow, ClassMetadata $meta, $object);

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
     * Get the scheduled object insertions from a UnitOfWork
     *
     * @param UnitOfWork $uow
     * @return array
     */
    abstract protected function getScheduledObjectInsertions($uow);

    /**
     * Get the scheduled object updates from a UnitOfWork
     *
     * @param UnitOfWork $uow
     * @return array
     */
    abstract protected function getScheduledObjectUpdates($uow);

    /**
     * Get the scheduled object deletions from a UnitOfWork
     *
     * @param UnitOfWork $uow
     * @return array
     */
    abstract protected function getScheduledObjectDeletions($uow);

    /**
     * Get the single identifier field name
     *
     * @param ClassMetadata $meta
     * @throws MappingException - if identifier is composite
     * @return string
     */
    abstract protected function getSingleIdentifierFieldName(ClassMetadata $meta);
}