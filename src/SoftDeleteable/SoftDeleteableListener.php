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
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\Event\LoadClassMetadataEventArgs;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Gedmo\Mapping\MappedEventSubscriber;
use Gedmo\SoftDeleteable\Event\ODM\PostSoftDeleteEventArgs as OdmPostSoftDeleteEventArgs;
use Gedmo\SoftDeleteable\Event\ODM\PreSoftDeleteEventArgs as OdmPreSoftDeleteEventArgs;
use Gedmo\SoftDeleteable\Event\ORM\PostSoftDeleteEventArgs as OrmPreSoftDeleteEventArgs;
use Gedmo\SoftDeleteable\Event\ORM\PreSoftDeleteEventArgs as OrmPostSoftDeleteEventArgs;

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

                $evm->dispatchEvent(
                    self::PRE_SOFT_DELETE,
                    $this->createPreSoftDeleteEventArgs($object, $om)
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

                $evm->dispatchEvent(
                    self::POST_SOFT_DELETE,
                    $this->createPostSoftDeleteEventArgs($object, $om)
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

    /**
     * create the appropriate event args for pre_soft_delete event
     *
     * @return OdmPreSoftDeleteEventArgs|OrmPreSoftDeleteEventArgs
     */
    private function createPreSoftDeleteEventArgs(object $object, ObjectManager $om): LifecycleEventArgs
    {
        if ($om instanceof EntityManagerInterface) {
            return new OrmPreSoftDeleteEventArgs($object, $om);
        }

        if ($om instanceof DocumentManager) {
            return new OdmPreSoftDeleteEventArgs($object, $om);
        }

        throw new \TypeError(\sprintf('Not supported object manager "%s".', \get_class($om)));
    }

    /**
     * create the appropriate event args for post_soft_delete event
     *
     * @return OdmPostSoftDeleteEventArgs|OrmPostSoftDeleteEventArgs
     */
    private function createPostSoftDeleteEventArgs(object $object, ObjectManager $om): LifecycleEventArgs
    {
        if ($om instanceof EntityManagerInterface) {
            return new OrmPostSoftDeleteEventArgs($object, $om);
        }

        if ($om instanceof DocumentManager) {
            return new OdmPostSoftDeleteEventArgs($object, $om);
        }

        throw new \TypeError(\sprintf('Not supported object manager "%s".', \get_class($om)));
    }
}
