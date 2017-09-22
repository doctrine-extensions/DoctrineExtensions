<?php

namespace Gedmo\SoftDeleteable;

use Doctrine\Entity;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\ListenersInvoker;
use Gedmo\Mapping\MappedEventSubscriber;
use Doctrine\Common\EventArgs;
use Doctrine\ODM\MongoDB\UnitOfWork as MongoDBUnitOfWork;

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
            'onFlush',
        );
    }

    /**
     * If it's a SoftDeleteable object, update the "deletedAt" field
     * and skip the removal of the object
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

        if ( $om instanceof EntityManagerInterface ) {
            $listenerInvoker = new ListenersInvoker($om);
        }

        $evm = $om->getEventManager();

        //getScheduledDocumentDeletions
        foreach ($ea->getScheduledObjectDeletions($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            $config = $this->getConfiguration($om, $meta->name);

            if (isset($config['softDeleteable']) && $config['softDeleteable']) {
                $reflProp = $meta->getReflectionProperty($config['fieldName']);
                $oldValue = $reflProp->getValue($object);

                $date = new \DateTime();

                if ($oldValue instanceof \Datetime && $oldValue <= $date) {
                    continue; // want to hard delete. Future (time aware) deletions will be soft deleted now.
                }

                if ( $om instanceof EntityManagerInterface && isset($listenerInvoker) ) {
                    $invoke = $listenerInvoker->getSubscribedSystems($meta, self::PRE_SOFT_DELETE);
                    if ($invoke !== ListenersInvoker::INVOKE_NONE) {
                        $listenerInvoker->invoke($meta, self::PRE_SOFT_DELETE, $object, $ea->createLifecycleEventArgsInstance($object, $om), $invoke);
                    }
                } else {
                    $evm->dispatchEvent(
                        self::PRE_SOFT_DELETE,
                        $ea->createLifecycleEventArgsInstance($object, $om)
                    );
                }

                $reflProp->setValue($object, $date);

                $om->persist($object);
                $uow->propertyChanged($object, $config['fieldName'], $oldValue, $date);
                if ($uow instanceof MongoDBUnitOfWork && !method_exists($uow, 'scheduleExtraUpdate')) {
                    $ea->recomputeSingleObjectChangeSet($uow, $meta, $object);
                } else {
                    $uow->scheduleExtraUpdate($object, array(
                        $config['fieldName'] => array($oldValue, $date),
                    ));
                }

                if ( $om instanceof EntityManagerInterface && isset($listenerInvoker) ) {
                    $invoke = $listenerInvoker->getSubscribedSystems($meta, self::POST_SOFT_DELETE);
                    if ($invoke !== ListenersInvoker::INVOKE_NONE) {
                        $listenerInvoker->invoke($meta, self::POST_SOFT_DELETE, $object, $ea->createLifecycleEventArgsInstance($object, $om), $invoke);
                    }
                } else {
                    $evm->dispatchEvent(
                        self::POST_SOFT_DELETE,
                        $ea->createLifecycleEventArgsInstance($object, $om)
                    );
                }

            }
        }
    }

    /**
     * Maps additional metadata
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
     * {@inheritDoc}
     */
    protected function getNamespace()
    {
        return __NAMESPACE__;
    }
}
