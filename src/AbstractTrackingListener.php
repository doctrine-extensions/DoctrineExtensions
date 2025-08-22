<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo;

use Doctrine\Common\EventArgs;
use Doctrine\DBAL\Types\Type;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Types\Type as TypeODM;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\Event\LoadClassMetadataEventArgs;
use Doctrine\Persistence\Event\ManagerEventArgs;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\NotifyPropertyChanged;
use Doctrine\Persistence\ObjectManager;
use Gedmo\Exception\UnexpectedValueException;
use Gedmo\Mapping\Event\AdapterInterface;
use Gedmo\Mapping\MappedEventSubscriber;

/**
 * The AbstractTrackingListener provides generic functions for all listeners.
 *
 * @template TConfig of array
 * @template TEventAdapter of AdapterInterface
 *
 * @template-extends MappedEventSubscriber<TConfig, TEventAdapter>
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
abstract class AbstractTrackingListener extends MappedEventSubscriber
{
    /**
     * Specifies the list of events to listen on.
     *
     * @return string[]
     */
    public function getSubscribedEvents()
    {
        return [
            'prePersist',
            'onFlush',
            'loadClassMetadata',
        ];
    }

    /**
     * Maps additional metadata for the object.
     *
     * @param LoadClassMetadataEventArgs $eventArgs
     *
     * @phpstan-param LoadClassMetadataEventArgs<ClassMetadata<object>, ObjectManager> $eventArgs
     *
     * @return void
     */
    public function loadClassMetadata(EventArgs $eventArgs)
    {
        $this->loadMetadataForObjectClass($eventArgs->getObjectManager(), $eventArgs->getClassMetadata());
    }

    /**
     * Processes object updates when the manager is flushed.
     *
     * @param ManagerEventArgs $args
     *
     * @phpstan-param ManagerEventArgs<ObjectManager> $args
     *
     * @return void
     */
    public function onFlush(EventArgs $args)
    {
        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
        $uow = $om->getUnitOfWork();
        // check all scheduled updates
        $all = array_merge($ea->getScheduledObjectInsertions($uow), $ea->getScheduledObjectUpdates($uow));
        foreach ($all as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            if (!$config = $this->getConfiguration($om, $meta->getName())) {
                continue;
            }
            $changeSet = $ea->getObjectChangeSet($uow, $object);
            $needChanges = false;

            if ($uow->isScheduledForInsert($object) && isset($config['create'])) {
                foreach ($config['create'] as $field) {
                    // Field can not exist in change set, i.e. when persisting an embedded object without a parent
                    $new = array_key_exists($field, $changeSet) ? $changeSet[$field][1] : false;
                    if (null === $new) { // let manual values
                        $needChanges = true;
                        $this->updateField($object, $ea, $meta, $field);
                    }
                }
            }

            if (isset($config['update'])) {
                foreach ($config['update'] as $field) {
                    $isInsertAndNull = $uow->isScheduledForInsert($object)
                        && array_key_exists($field, $changeSet)
                        && null === $changeSet[$field][1];
                    if (!isset($changeSet[$field]) || $isInsertAndNull) { // let manual values
                        $needChanges = true;
                        $this->updateField($object, $ea, $meta, $field);
                    }
                }
            }

            if (!$uow->isScheduledForInsert($object) && isset($config['change'])) {
                foreach ($config['change'] as $options) {
                    if (isset($changeSet[$options['field']])) {
                        continue; // value was set manually
                    }

                    if (!is_array($options['trackedField'])) {
                        $singleField = true;
                        $trackedFields = [$options['trackedField']];
                    } else {
                        $singleField = false;
                        $trackedFields = $options['trackedField'];
                    }

                    foreach ($trackedFields as $trackedField) {
                        $trackedChild = null;
                        $tracked = null;
                        $parts = explode('.', $trackedField);
                        if (isset($parts[1])) {
                            $tracked = $parts[0];
                            $trackedChild = $parts[1];
                        }

                        if (!isset($tracked) || array_key_exists($trackedField, $changeSet)) {
                            $tracked = $trackedField;
                            $trackedChild = null;
                        }

                        if (isset($changeSet[$tracked])) {
                            $changes = $changeSet[$tracked];
                            if (isset($trackedChild)) {
                                $changingObject = $changes[1];
                                if (!is_object($changingObject)) {
                                    throw new UnexpectedValueException("Field - [{$tracked}] is expected to be object in class - {$meta->getName()}");
                                }
                                $objectMeta = $om->getClassMetadata(get_class($changingObject));
                                $om->initializeObject($changingObject);
                                $value = $objectMeta->getReflectionProperty($trackedChild)->getValue($changingObject);
                            } else {
                                $value = $changes[1];
                            }

                            $configuredValues = $this->getPhpValues($options['value'], $meta->getTypeOfField($tracked), $om);

                            if (null === $configuredValues || ($singleField && in_array($value, $configuredValues, true))) {
                                $needChanges = true;
                                $this->updateField($object, $ea, $meta, $options['field']);
                            }
                        }
                    }
                }
            }

            if ($needChanges) {
                $ea->recomputeSingleObjectChangeSet($uow, $meta, $object);
            }
        }
    }

    /**
     * Processes updates when an object is persisted in the manager.
     *
     * @param LifecycleEventArgs $args
     *
     * @phpstan-param LifecycleEventArgs<ObjectManager> $args
     *
     * @return void
     */
    public function prePersist(EventArgs $args)
    {
        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
        $object = $ea->getObject();
        $meta = $om->getClassMetadata(get_class($object));
        if ($config = $this->getConfiguration($om, $meta->getName())) {
            if (isset($config['update'])) {
                foreach ($config['update'] as $field) {
                    if (null === $meta->getReflectionProperty($field)->getValue($object)) { // let manual values
                        $this->updateField($object, $ea, $meta, $field);
                    }
                }
            }
            if (isset($config['create'])) {
                foreach ($config['create'] as $field) {
                    if (null === $meta->getReflectionProperty($field)->getValue($object)) { // let manual values
                        $this->updateField($object, $ea, $meta, $field);
                    }
                }
            }
        }
    }

    /**
     * Get the value for an updated field.
     *
     * @param ClassMetadata<object> $meta
     * @param string                $field
     * @param TEventAdapter         $eventAdapter
     *
     * @return mixed
     */
    abstract protected function getFieldValue($meta, $field, $eventAdapter);

    /**
     * Updates a field.
     *
     * @param object                $object
     * @param TEventAdapter         $eventAdapter
     * @param ClassMetadata<object> $meta
     * @param string                $field
     *
     * @return void
     */
    protected function updateField($object, $eventAdapter, $meta, $field)
    {
        $property = $meta->getReflectionProperty($field);
        $oldValue = $property->getValue($object);
        $newValue = $this->getFieldValue($meta, $field, $eventAdapter);

        // if field value is reference, persist object
        if ($meta->hasAssociation($field) && is_object($newValue) && !$eventAdapter->getObjectManager()->contains($newValue)) {
            $uow = $eventAdapter->getObjectManager()->getUnitOfWork();

            // Check to persist only when the object isn't already managed, always persists for MongoDB
            if (!($uow instanceof UnitOfWork) || UnitOfWork::STATE_MANAGED !== $uow->getEntityState($newValue)) {
                $eventAdapter->getObjectManager()->persist($newValue);
            }
        }

        $property->setValue($object, $newValue);

        if ($object instanceof NotifyPropertyChanged) {
            $uow = $eventAdapter->getObjectManager()->getUnitOfWork();
            $uow->propertyChanged($object, $field, $oldValue, $newValue);
        }
    }

    /**
     * @param mixed $values
     *
     * @return mixed[]|null
     */
    private function getPhpValues($values, ?string $type, ObjectManager $om): ?array
    {
        if (null === $values) {
            return null;
        }

        if (!is_array($values)) {
            $values = [$values];
        }

        if (null !== $type) {
            foreach ($values as $i => $value) {
                if ($om instanceof DocumentManager) {
                    if (TypeODM::hasType($type)) {
                        $values[$i] = TypeODM::getType($type)
                            ->convertToPHPValue($value);
                    } else {
                        $values[$i] = $value;
                    }
                } elseif ($om instanceof EntityManagerInterface) {
                    if (Type::hasType($type)) {
                        $values[$i] = $om->getConnection()
                            ->convertToPHPValue($value, $type);
                    } else {
                        $values[$i] = $value;
                    }
                }
            }
        }

        return $values;
    }
}
