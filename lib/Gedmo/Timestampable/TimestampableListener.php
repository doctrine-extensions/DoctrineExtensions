<?php

namespace Gedmo\Timestampable;

use Doctrine\Common\EventArgs;
use Gedmo\Mapping\MappedEventSubscriber;
use Doctrine\Common\NotifyPropertyChanged;
use Gedmo\Exception\UnexpectedValueException;
use Gedmo\Timestampable\Mapping\Event\TimestampableAdapter;

/**
 * The Timestampable listener handles the update of
 * dates on creation and update.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TimestampableListener extends MappedEventSubscriber
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
            'loadClassMetadata',
        );
    }

    /**
     * Maps additional metadata for the Entity
     *
     * @param EventArgs $eventArgs
     *
     * @return void
     */
    public function loadClassMetadata(EventArgs $eventArgs)
    {
        $ea = $this->getEventAdapter($eventArgs);
        $this->loadMetadataForObjectClass($ea->getObjectManager(), $eventArgs->getClassMetadata());
    }

    /**
     * Looks for Timestampable objects being updated
     * to update modification date
     *
     * @param EventArgs $args
     *
     * @return void
     */
    public function onFlush(EventArgs $args)
    {
        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
        $uow = $om->getUnitOfWork();
        // check all scheduled updates
        foreach ($ea->getScheduledObjectUpdates($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            if ($config = $this->getConfiguration($om, $meta->name)) {
                $changeSet = $ea->getObjectChangeSet($uow, $object);
                $needChanges = false;

                if (isset($config['update'])) {
                    foreach ($config['update'] as $field) {
                        if (!isset($changeSet[$field])) { // let manual values
                            $needChanges = true;
                            $this->updateField($object, $ea, $meta, $field);
                        }
                    }
                }

                if (isset($config['change'])) {
                    foreach ($config['change'] as $options) {
                        if (isset($changeSet[$options['field']])) {
                            continue; // value was set manually
                        }

                        if (!is_array($options['trackedField'])) {
                            $singleField = true;
                            $trackedFields = array($options['trackedField']);
                        } else {
                            $singleField = false;
                            $trackedFields = $options['trackedField'];
                        }

                        foreach ($trackedFields as $tracked) {
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
                                        throw new UnexpectedValueException(
                                            "Field - [{$field}] is expected to be object in class - {$meta->name}"
                                        );
                                    }
                                    $objectMeta = $om->getClassMetadata(get_class($changingObject));
                                    $om->initializeObject($changingObject);
                                    $value = $objectMeta->getReflectionProperty($trackedChild)->getValue($changingObject);
                                } else {
                                    $value = $changes[1];
                                }

                                if (($singleField && in_array($value, (array) $options['value'])) || $options['value'] === null) {
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
    }

    /**
     * Checks for persisted Timestampable objects
     * to update creation and modification dates
     *
     * @param EventArgs $args
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
                    if ($meta->getReflectionProperty($field)->getValue($object) === null) { // let manual values
                        $this->updateField($object, $ea, $meta, $field);
                    }
                }
            }

            if (isset($config['create'])) {
                foreach ($config['create'] as $field) {
                    if ($meta->getReflectionProperty($field)->getValue($object) === null) { // let manual values
                        $this->updateField($object, $ea, $meta, $field);
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
     * Updates a field
     *
     * @param object               $object
     * @param TimestampableAdapter $ea
     * @param object               $meta
     * @param string               $field
     */
    protected function updateField($object, $ea, $meta, $field)
    {
        $property = $meta->getReflectionProperty($field);
        $oldValue = $property->getValue($object);
        $newValue = $ea->getDateValue($meta, $field);
        $property->setValue($object, $newValue);
        if ($object instanceof NotifyPropertyChanged) {
            $uow = $ea->getObjectManager()->getUnitOfWork();
            $uow->propertyChanged($object, $field, $oldValue, $newValue);
        }
    }
}
