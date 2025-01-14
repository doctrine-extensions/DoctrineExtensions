<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Mapping\Event\Adapter;

use Doctrine\Common\EventArgs;
use Doctrine\Deprecations\Deprecation;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Gedmo\Exception\RuntimeException;
use Gedmo\Mapping\Event\AdapterInterface;

/**
 * Doctrine event adapter for ORM specific
 * event arguments
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
class ORM implements AdapterInterface
{
    private ?EventArgs $args = null;

    private ?EntityManagerInterface $em = null;

    public function __call($method, $args)
    {
        Deprecation::trigger(
            'gedmo/doctrine-extensions',
            'https://github.com/doctrine-extensions/DoctrineExtensions/pull/2409',
            'Using "%s()" method is deprecated since gedmo/doctrine-extensions 3.5 and will be removed in version 4.0.',
            __METHOD__
        );

        if (null === $this->args) {
            throw new RuntimeException('Event args must be set before calling its methods');
        }
        $method = str_replace('Object', $this->getDomainObjectName(), $method);

        return call_user_func_array([$this->args, $method], $args);
    }

    public function setEventArgs(EventArgs $args)
    {
        $this->args = $args;
    }

    public function getDomainObjectName()
    {
        return 'Entity';
    }

    public function getManagerName()
    {
        return 'ORM';
    }

    /**
     * @param ClassMetadata<object> $meta
     */
    public function getRootObjectClass($meta)
    {
        return $meta->rootEntityName;
    }

    /**
     * Set the entity manager
     *
     * @return void
     */
    public function setEntityManager(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @return EntityManagerInterface
     */
    public function getObjectManager()
    {
        if (null !== $this->em) {
            return $this->em;
        }

        if (null === $this->args) {
            throw new \LogicException(sprintf('Event args must be set before calling "%s()".', __METHOD__));
        }

        // todo: for the next major release, uncomment the next line:
        // return $this->args->getObjectManager();
        // and remove anything past this
        if (\method_exists($this->args, 'getObjectManager')) {
            return $this->args->getObjectManager();
        }

        Deprecation::trigger(
            'gedmo/doctrine-extensions',
            'https://github.com/doctrine-extensions/DoctrineExtensions/pull/2639',
            'Calling "%s()" on event args of class "%s" that does not implement "getObjectManager()" is deprecated since gedmo/doctrine-extensions 3.14'
            .' and will throw a "%s" error in version 4.0.',
            __METHOD__,
            get_class($this->args),
            \Error::class
        );

        return $this->args->getEntityManager();
    }

    public function getObject(): object
    {
        if (null === $this->args) {
            throw new \LogicException(sprintf('Event args must be set before calling "%s()".', __METHOD__));
        }

        // todo: for the next major release, uncomment the next line:
        // return $this->args->getObject();
        // and remove anything past this
        if (\method_exists($this->args, 'getObject')) {
            return $this->args->getObject();
        }

        Deprecation::trigger(
            'gedmo/doctrine-extensions',
            'https://github.com/doctrine-extensions/DoctrineExtensions/pull/2639',
            'Calling "%s()" on event args of class "%s" that does not imeplement "getObject()" is deprecated since gedmo/doctrine-extensions 3.14'
            .' and will throw a "%s" error in version 4.0.',
            __METHOD__,
            get_class($this->args),
            \Error::class
        );

        return $this->args->getEntity();
    }

    public function getObjectState($uow, $object)
    {
        return $uow->getEntityState($object);
    }

    public function getObjectChangeSet($uow, $object)
    {
        return $uow->getEntityChangeSet($object);
    }

    /**
     * @param ClassMetadata<object> $meta
     */
    public function getSingleIdentifierFieldName($meta)
    {
        return $meta->getSingleIdentifierFieldName();
    }

    /**
     * @param ClassMetadata<object> $meta
     */
    public function recomputeSingleObjectChangeSet($uow, $meta, $object)
    {
        $uow->recomputeSingleEntityChangeSet($meta, $object);
    }

    public function getScheduledObjectUpdates($uow)
    {
        return $uow->getScheduledEntityUpdates();
    }

    public function getScheduledObjectInsertions($uow)
    {
        return $uow->getScheduledEntityInsertions();
    }

    public function getScheduledObjectDeletions($uow)
    {
        return $uow->getScheduledEntityDeletions();
    }

    public function setOriginalObjectProperty($uow, $object, $property, $value)
    {
        $uow->setOriginalEntityProperty(spl_object_id($object), $property, $value);
    }

    public function clearObjectChangeSet($uow, $object)
    {
        $changeSet = &$uow->getEntityChangeSet($object);
        $changeSet = [];
    }

    /**
     * @deprecated use custom lifecycle event classes instead
     *
     * Creates an ORM specific LifecycleEventArgs
     *
     * @param object                 $object
     * @param EntityManagerInterface $entityManager
     *
     * @return LifecycleEventArgs
     */
    public function createLifecycleEventArgsInstance($object, $entityManager)
    {
        Deprecation::trigger(
            'gedmo/doctrine-extensions',
            'https://github.com/doctrine-extensions/DoctrineExtensions/pull/2649',
            'Using "%s()" method is deprecated since gedmo/doctrine-extensions 3.15 and will be removed in version 4.0.',
            __METHOD__
        );

        if (!class_exists(LifecycleEventArgs::class)) {
            throw new \RuntimeException(sprintf('Cannot call %s() when using doctrine/orm >=3.0, use a custom lifecycle event class instead.', __METHOD__));
        }

        return new LifecycleEventArgs($object, $entityManager);
    }
}
