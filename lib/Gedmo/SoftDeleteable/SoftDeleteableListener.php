<?php

namespace Gedmo\SoftDeleteable;

use Gedmo\Mapping\MappedEventSubscriber;
use Gedmo\Mapping\ObjectManagerHelper as OMH;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\EventArgs;

/**
 * SoftDeleteable listener
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SoftDeleteableListener extends MappedEventSubscriber
{
    /**
     * Pre soft-delete event
     *
     * @var string
     */
    const PRE_SOFT_DELETE = "preSoftDelete";

    /**
     * Post soft-delete event
     *
     * @var string
     */
    const POST_SOFT_DELETE = "postSoftDelete";

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return array(
            'loadClassMetadata',
            'onFlush'
        );
    }

    /**
     * If it's a SoftDeleteable object, update the "deletedAt" field
     * and skip the removal of the object
     *
     * @param EventArgs $event
     */
    public function onFlush(EventArgs $event)
    {
        $om = OMH::getObjectManagerFromEvent($event);
        $uow = $om->getUnitOfWork();
        $evm = $om->getEventManager();

        foreach (OMH::getScheduledObjectDeletions($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            $exm = $this->getConfiguration($om, $meta->name);

            if ($exm = $this->getConfiguration($om, $meta->name)) {
                $reflProp = $meta->getReflectionProperty($exm->getField());
                $oldValue = $reflProp->getValue($object);
                if ($oldValue instanceof \Datetime) {
                    continue; // want to hard delete
                }

                $lifecycleEvent = OMH::createLifecycleEventArgsInstance($om, $object);
                $evm->dispatchEvent(self::PRE_SOFT_DELETE, $lifecycleEvent);
                $reflProp->setValue($object, $date = new \DateTime);

                $om->persist($object);
                $uow->propertyChanged($object, $exm->getField(), $oldValue, $date);
                $uow->scheduleExtraUpdate($object, array(
                    $exm->getField() => array($oldValue, $date)
                ));
                $evm->dispatchEvent(self::POST_SOFT_DELETE, $lifecycleEvent);
            }
        }
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
     * {@inheritDoc}
     */
    protected function getNamespace()
    {
        return __NAMESPACE__;
    }
}
