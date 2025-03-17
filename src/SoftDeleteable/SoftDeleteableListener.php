<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\SoftDeleteable;

use Doctrine\Common\EventArgs;
use Doctrine\Common\EventManager;
use Doctrine\Deprecations\Deprecation;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\UnitOfWork as MongoDBUnitOfWork;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\Persistence\Event\LoadClassMetadataEventArgs;
use Doctrine\Persistence\Event\ManagerEventArgs;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Gedmo\Mapping\MappedEventSubscriber;
use Gedmo\SoftDeleteable\Event\PostSoftDeleteEventArgs;
use Gedmo\SoftDeleteable\Event\PreSoftDeleteEventArgs;
use Gedmo\SoftDeleteable\Mapping\Event\SoftDeleteableAdapter;

/**
 * SoftDeleteable listener
 *
 * @phpstan-extends MappedEventSubscriber<array, SoftDeleteableAdapter>
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @final since gedmo/doctrine-extensions 3.11
 */
class SoftDeleteableListener extends MappedEventSubscriber
{
    /**
     * Pre soft-delete event
     *
     * @var string
     */
    public const PRE_SOFT_DELETE = 'preSoftDelete';

    /**
     * Post soft-delete event
     *
     * @var string
     */
    public const POST_SOFT_DELETE = 'postSoftDelete';

    /**
     * Objects soft-deleted on flush.
     *
     * @var array<object>
     */
    private array $softDeletedObjects = [];

    /**
     * @return string[]
     */
    public function getSubscribedEvents()
    {
        return [
            'loadClassMetadata',
            'onFlush',
            'postFlush',
        ];
    }

    /**
     * If it's a SoftDeleteable object, update the "deletedAt" field
     * and skip the removal of the object
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
        /** @var EntityManagerInterface|DocumentManager $om */
        $om = $ea->getObjectManager();
        $uow = $om->getUnitOfWork();
        $evm = $om->getEventManager();

        // getScheduledDocumentDeletions
        foreach ($ea->getScheduledObjectDeletions($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            $config = $this->getConfiguration($om, $meta->getName());

            if (isset($config['softDeleteable']) && $config['softDeleteable']) {
                $reflProp = $meta->getReflectionProperty($config['fieldName']);
                $oldValue = $reflProp->getValue($object);
                $date = $ea->getDateValue($meta, $config['fieldName']);

                if (isset($config['hardDelete']) && $config['hardDelete'] && $oldValue instanceof \DateTimeInterface && $oldValue <= $date) {
                    continue; // want to hard delete
                }

                if ($evm->hasListeners(self::PRE_SOFT_DELETE)) {
                    // @todo: in the next major remove check and only instantiate the event
                    $preSoftDeleteEventArgs = $this->hasToDispatchNewEvent($om, $evm, self::PRE_SOFT_DELETE, PreSoftDeleteEventArgs::class)
                        ? new PreSoftDeleteEventArgs($object, $om)
                        : $ea->createLifecycleEventArgsInstance($object, $om);

                    $evm->dispatchEvent(
                        self::PRE_SOFT_DELETE,
                        $preSoftDeleteEventArgs,
                    );
                }

                $reflProp->setValue($object, $date);

                $om->persist($object);
                $uow->propertyChanged($object, $config['fieldName'], $oldValue, $date);
                if ($uow instanceof MongoDBUnitOfWork) {
                    $ea->recomputeSingleObjectChangeSet($uow, $meta, $object);
                } else {
                    $uow->scheduleExtraUpdate($object, [
                        $config['fieldName'] => [$oldValue, $date],
                    ]);
                }

                if ($evm->hasListeners(self::POST_SOFT_DELETE)) {
                    // @todo: in the next major remove check and only instantiate the event
                    $postSoftDeleteEventArgs = $this->hasToDispatchNewEvent($om, $evm, self::POST_SOFT_DELETE, PostSoftDeleteEventArgs::class)
                        ? new PostSoftDeleteEventArgs($object, $om)
                        : $ea->createLifecycleEventArgsInstance($object, $om);

                    $evm->dispatchEvent(
                        self::POST_SOFT_DELETE,
                        $postSoftDeleteEventArgs
                    );
                }

                $this->softDeletedObjects[] = $object;
            }
        }
    }

    /**
     * Detach soft-deleted objects from object manager.
     *
     * @return void
     */
    public function postFlush(EventArgs $args)
    {
        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
        foreach ($this->softDeletedObjects as $index => $object) {
            $om->detach($object);
            unset($this->softDeletedObjects[$index]);
        }
    }

    /**
     * Maps additional metadata
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

    protected function getNamespace()
    {
        return __NAMESPACE__;
    }

    /** @param class-string $eventClass */
    private function hasToDispatchNewEvent(ObjectManager $objectManager, EventManager $eventManager, string $eventName, string $eventClass): bool
    {
        if ($objectManager instanceof EntityManagerInterface && !class_exists(LifecycleEventArgs::class)) {
            return true;
        }

        foreach ($eventManager->getListeners($eventName) as $listener) {
            $reflMethod = new \ReflectionMethod($listener, $eventName);

            $parameters = $reflMethod->getParameters();

            if (
                1 !== count($parameters)
                || !$parameters[0]->hasType()
                || !$parameters[0]->getType() instanceof \ReflectionNamedType
                || $eventClass !== $parameters[0]->getType()->getName()
            ) {
                Deprecation::trigger(
                    'gedmo/doctrine-extensions',
                    'https://github.com/doctrine-extensions/DoctrineExtensions/pull/2649',
                    'Type-hinting to something different than "%s" in "%s::%s()" is deprecated.',
                    $eventClass,
                    get_class($listener),
                    $reflMethod->getName()
                );

                return false;
            }
        }

        return true;
    }
}
