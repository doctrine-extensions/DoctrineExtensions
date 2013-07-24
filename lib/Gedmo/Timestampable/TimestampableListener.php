<?php

namespace Gedmo\Timestampable;

use Gedmo\Mapping\ObjectManagerHelper as OMH;
use Gedmo\Mapping\MappedEventSubscriber;
use Gedmo\Exception\UnexpectedValueException;
use Doctrine\Common\NotifyPropertyChanged;
use Doctrine\Common\EventArgs;
use Doctrine\Common\Persistence\ObjectManager;

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
            'loadClassMetadata'
        );
    }

    /**
     * Mapps additional metadata for the Entity
     *
     * @param EventArgs $event
     */
    public function loadClassMetadata(EventArgs $event)
    {
        $this->loadMetadataForObjectClass(OMH::getObjectManagerFromEvent($event), $event->getClassMetadata());
    }

    /**
     * Looks for Timestampable objects being updated
     * to update modification date
     *
     * @param EventArgs $event
     */
    public function onFlush(EventArgs $event)
    {
        $om = OMH::getObjectManagerFromEvent($event);
        $uow = $om->getUnitOfWork();
        // check all scheduled updates
        foreach (OMH::getScheduledObjectUpdates($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            if ($config = $this->getConfiguration($om, $meta->name)) {
                $changeSet = OMH::getObjectChangeSet($uow, $object);
                $needChanges = false;

                if (isset($config['update'])) {
                    foreach ($config['update'] as $field) {
                        if (!isset($changeSet[$field])) { // let manual values
                            $needChanges = true;
                            $this->updateField($om, $object, $field);
                        }
                    }
                }

                if (isset($config['change'])) {
                    foreach ($config['change'] as $options) {
                        if (isset($changeSet[$options['field']])) {
                            continue; // date/timestamp was set manually
                        }
                        $trackedFields = (array)$options['trackedField'];
                        if (count($trackedFields) > 1 && $options['value'] !== null) {
                            throw new UnexpectedValueException("If there is more than one field observed for changes, 'value' cannot be set");
                        }
                        foreach ($trackedFields as $field) {
                            $parts = explode('.', $field);
                            $field = array_pop($parts);
                            $targetObject = $object;
                            if ($assoc = array_shift($parts)) {
								$om->initializeObject($assoc);
                                if (!$meta->isSingleValuedAssociation($assoc)) {
                                    throw new UnexpectedValueException(
                                        "Field - [{$assoc}] is expected to be a single valued association in class - {$meta->name}"
                                    );
                                }
                                if ($assoc = $meta->getReflectionProperty($assoc)->getValue($targetObject)) {
                                    $assocMeta = $om->getClassMetadata(get_class($assoc));
                                    if (!$assocMeta->hasField($field)) {
                                        throw new UnexpectedValueException(
                                            "Field [$field] - was not found in associated class - {$meta->name}"
                                        );
                                    }
                                    // association is available, check if it is scheduled in UOW
                                    if ($uow->isScheduledForInsert($assoc) || $uow->isScheduledForUpdate($assoc)) {
                                        $targetObject = $assoc; // will test field there
                                    }
                                }
                            }
                            $targetChangeSet = OMH::getObjectChangeSet($uow, $targetObject); // reload, since might be an association
                            if (isset($targetChangeSet[$field])) {
                                $value = $targetChangeSet[$field][1];
                                // comparison is not explicit, because string 'true' value should match true - boolean value
                                if (null === $options['value'] || $value == $options['value']) {
                                    $needChanges = true;
                                    $this->updateField($om, $object, $options['field']);
                                    if (count($trackedFields) > 1) {
                                        break; // no point to iterate again
                                    }
                                }
                            }
                        }
                    }
                }

                if ($needChanges) {
                    OMH::recomputeSingleObjectChangeSet($uow, $meta, $object);
                }
            }
        }
    }

    /**
     * Checks for persisted Timestampable objects
     * to update creation and modification dates
     *
     * @param EventArgs $event
     * @return void
     */
    public function prePersist(EventArgs $event)
    {
        $om = OMH::getObjectManagerFromEvent($event);
        $object = OMH::getObjectFromEvent($event);
        $meta = $om->getClassMetadata(get_class($object));

        if ($config = $this->getConfiguration($om, $meta->getName())) {
            if (isset($config['update'])) {
                foreach ($config['update'] as $field) {
                    if ($meta->getReflectionProperty($field)->getValue($object) === null) { // let manual values
                        $this->updateField($om, $object, $field);
                    }
                }
            }

            if (isset($config['create'])) {
                foreach ($config['create'] as $field) {
                    if ($meta->getReflectionProperty($field)->getValue($object) === null) { // let manual values
                        $this->updateField($om, $object, $field);
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
     * Updates a date field
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $om
     * @param $object
     * @param $field
     */
    protected function updateField(ObjectManager $om, $object, $field)
    {
        $meta = $om->getClassMetadata(get_class($object));
        $property = $meta->getReflectionProperty($field);
        $oldValue = $property->getValue($object);

        $mapping = $meta->getFieldMapping($field);
        switch ($mapping['type']) {
            case 'integer':
            case 'timestamp': // mongodb
                $newValue = time(); break;
            case 'zenddate':
                $newValue = new \Zend_Date; break;
            default:
                $newValue = new \DateTime;
        }
        $property->setValue($object, $newValue);
        if ($object instanceof NotifyPropertyChanged) {
            $om->getUnitOfWork()->propertyChanged($object, $field, $oldValue, $newValue);
        }
    }
}
