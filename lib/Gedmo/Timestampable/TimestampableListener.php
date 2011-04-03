<?php

namespace Gedmo\Timestampable;

use Doctrine\Common\Persistence\Mapping\ClassMetadata,
    Doctrine\Common\EventArgs,
    Gedmo\Mapping\MappedEventSubscriber;

/**
 * The Timestampable listener handles the update of
 * dates on creation and update.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Timestampable
 * @subpackage TimestampableListener
 * @link http://www.gediminasm.org
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
     * @param EventArgs $eventArgs
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
                            $meta->getReflectionProperty($field)->setValue($object, $ea->getDateValue($meta, $field));
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
                                    ->setValue($object, $ea->getDateValue($meta, $options['field']));
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
     * @return void
     */
    public function prePersist(EventArgs $args)
    {
        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
        $object = $ea->getObject();

        $meta = $om->getClassMetadata(get_class($object));
        if ($config = $this->getConfiguration($om, $meta->name)) {
            if (isset($config['update'])) {
                foreach ($config['update'] as $field) {
                    if ($meta->getReflectionProperty($field)->getValue($object) === null) { // let manual values
                        $meta->getReflectionProperty($field)->setValue($object, $ea->getDateValue($meta, $field));
                    }
                }
            }

            if (isset($config['create'])) {
                foreach ($config['create'] as $field) {
                    if ($meta->getReflectionProperty($field)->getValue($object) === null) { // let manual values
                        $meta->getReflectionProperty($field)->setValue($object, $ea->getDateValue($meta, $field));
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
}
