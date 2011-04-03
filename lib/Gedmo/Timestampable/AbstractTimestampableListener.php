<?php

namespace Gedmo\Timestampable;

use Doctrine\Common\EventArgs,
    Gedmo\Mapping\MappedEventSubscriber,
    Doctrine\ORM\Proxy\Proxy;

/**
 * The AbstractTimestampableListener is an abstract class
 * of timestampable listener in order to support diferent
 * object managers.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Timestampable
 * @subpackage AbstractTimestampableListener
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
abstract class AbstractTimestampableListener extends MappedEventSubscriber
{
    /**
     * Mapps additional metadata for the Entity
     *
     * @param EventArgs $eventArgs
     * @return void
     */
    public function loadClassMetadata(EventArgs $eventArgs)
    {
        $this->loadMetadataForObjectClass($this->getObjectManager($eventArgs), $eventArgs->getClassMetadata());
    }

    /**
     * Looks for Timestampable objects being updated
     * to update modification date
     *
     * @param EventArgs $args
     * @return void
     */
    public function onFlush(EventArgs $args)
    {
        $om = $this->getObjectManager($args);
        $uow = $om->getUnitOfWork();
        // check all scheduled updates
        foreach ($this->getScheduledObjectUpdates($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            if ($config = $this->getConfiguration($om, $meta->name)) {
                $changeSet = $this->getObjectChangeSet($uow, $object);
                $needChanges = false;

                if (isset($config['update'])) {
                    foreach ($config['update'] as $field) {
                        if (!isset($changeSet[$field])) { // let manual values
                            $needChanges = true;
                            $meta->getReflectionProperty($field)->setValue($object, $this->getDateValue($meta, $field));
                        }
                    }
                }

                if (isset($config['change'])) {
                    foreach ($config['change'] as $options) {
                        if (isset($changeSet[$options['field']])) {
                            continue; // value was set manually
                        }

                        $tracked = $options['trackedField'];
                        $trackedChild = null;
                        $parts = explode('.', $tracked);
                        if (isset($parts[1])) {
                            $tracked = $parts[0];
                            $trackedChild = $parts[1];
                        }

                        if (isset($changeSet[$tracked])) {
                            $changes = $changeSet[$tracked];
                            if (isset($trackedChild)) {
                                $changingObject = $changes[1];
                                if (!is_object($changingObject)) {
                                    throw new \Gedmo\Exception\UnexpectedValueException("Field - [{$field}] is expected to be object in class - {$meta->name}");
                                }
                                $objectMeta = $om->getClassMetadata(get_class($changingObject));
                                $trackedChild instanceof Proxy && $om->refresh($trackedChild);
                                $value = $objectMeta->getReflectionProperty($trackedChild)
                                    ->getValue($changingObject);
                            } else {
                                $value = $changes[1];
                            }

                            if ($options['value'] == $value) {
                                $needChanges = true;
                                $meta->getReflectionProperty($options['field'])
                                    ->setValue($object, $this->getDateValue($meta, $options['field']));
                            }
                        }
                    }
                }

                if ($needChanges) {
                    $this->recomputeSingleObjectChangeSet($uow, $meta, $object);
                }
            }
        }
    }

    /**
     * Checks for persisted Timestampable objects
     * to update creation and modification dates
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
            if (isset($config['update'])) {
                foreach ($config['update'] as $field) {
                    if ($meta->getReflectionProperty($field)->getValue($object) === null) { // let manual values
                        $meta->getReflectionProperty($field)->setValue($object, $this->getDateValue($meta, $field));
                    }
                }
            }

            if (isset($config['create'])) {
                foreach ($config['create'] as $field) {
                    if ($meta->getReflectionProperty($field)->getValue($object) === null) { // let manual values
                        $meta->getReflectionProperty($field)->setValue($object, $this->getDateValue($meta, $field));
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
     * Get the scheduled object updates from a UnitOfWork
     *
     * @param UnitOfWork $uow
     * @return array
     */
    abstract protected function getScheduledObjectUpdates($uow);

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
     * Get the date value
     *
     * @param ClassMetadata $meta
     * @param string $field
     * @return mixed
     */
    abstract protected function getDateValue($meta, $field);
}
