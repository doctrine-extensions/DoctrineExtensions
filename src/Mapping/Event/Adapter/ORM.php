<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Mapping\Event\Adapter;

use Doctrine\Common\EventArgs;
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
    /**
     * @var EventArgs
     */
    private $args;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __call($method, $args)
    {
        @trigger_error(sprintf(
            'Using "%s()" method is deprecated since gedmo/doctrine-extensions 3.5 and will be removed in version 4.0.',
            __METHOD__
        ), E_USER_DEPRECATED);

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
     * @param ClassMetadata $meta
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

        return $this->args->getEntityManager();
    }

    public function getObject(): object
    {
        if (null === $this->args) {
            throw new \LogicException(sprintf('Event args must be set before calling "%s()".', __METHOD__));
        }

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
     * @param ClassMetadata $meta
     */
    public function getSingleIdentifierFieldName($meta)
    {
        return $meta->getSingleIdentifierFieldName();
    }

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
        $uow->clearEntityChangeSet(spl_object_id($object));
    }

    /**
     * Creates a ORM specific LifecycleEventArgs.
     *
     * @param object                 $document
     * @param EntityManagerInterface $entityManager
     *
     * @return LifecycleEventArgs
     */
    public function createLifecycleEventArgsInstance($document, $entityManager)
    {
        return new LifecycleEventArgs($document, $entityManager);
    }
}
