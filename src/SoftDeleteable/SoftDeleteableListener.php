<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\SoftDeleteable;

use Doctrine\Common\EventArgs;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\UnitOfWork as MongoDBUnitOfWork;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Event\LoadClassMetadataEventArgs;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Gedmo\Mapping\MappedEventSubscriber;

/**
 * SoftDeleteable listener
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
     * @return string[]
     */
    public function getSubscribedEvents()
    {
        return [
            'loadClassMetadata',
            'onFlush',
        ];
    }

    /**
     * If it's a SoftDeleteable object, update the "deletedAt" field
     * and skip the removal of the object
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

                // @todo: in the next major remove check and call createPreSoftDeleteEventArgs
                $preSoftDeleteEventArgs = method_exists($ea, 'createPreSoftDeleteEventArgs')
                    ? $ea->createPreSoftDeleteEventArgs($object, $om)
                    : $ea->createLifecycleEventArgsInstance($object, $om);

                $evm->dispatchEvent(
                    self::PRE_SOFT_DELETE,
                    $preSoftDeleteEventArgs
                );

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

                // @todo: in the next major remove check and call createPostSoftDeleteEventArgs
                $postSoftDeleteEventArgs = method_exists($ea, 'createPostSoftDeleteEventArgs')
                    ? $ea->createPostSoftDeleteEventArgs($object, $om)
                    : $ea->createLifecycleEventArgsInstance($object, $om);

                $evm->dispatchEvent(
                    self::POST_SOFT_DELETE,
                    $postSoftDeleteEventArgs
                );
            }
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
}
