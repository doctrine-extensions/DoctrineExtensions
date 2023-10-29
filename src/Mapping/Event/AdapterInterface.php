<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Mapping\Event;

use Doctrine\Common\EventArgs;
use Doctrine\ODM\MongoDB\UnitOfWork as MongoDBUnitOfWork;
use Doctrine\ORM\UnitOfWork as ORMUnitOfWork;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;

/**
 * Doctrine event adapter for Doctrine extensions.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @method LifecycleEventArgs createLifecycleEventArgsInstance(object $object, ObjectManager $manager)
 * @method object             getObject()
 */
interface AdapterInterface
{
    /**
     * @deprecated since gedmo/doctrine-extensions 3.5, will be removed in version 4.0.
     *
     * Calls a method on the event args object.
     *
     * @param string            $method
     * @param array<int, mixed> $args
     *
     * @return mixed
     */
    public function __call($method, $args);

    /**
     * Set the event args object.
     *
     * @return void
     */
    public function setEventArgs(EventArgs $args);

    /**
     * Get the name of the domain object.
     *
     * @return string
     */
    public function getDomainObjectName();

    /**
     * Get the name of the manager used by this adapter.
     *
     * @return string
     */
    public function getManagerName();

    /**
     * Get the root object class, handles inheritance
     *
     * @param ClassMetadata $meta
     *
     * @return string
     *
     * @phpstan-return class-string
     */
    public function getRootObjectClass($meta);

    /**
     * Get the object manager.
     *
     * @return ObjectManager
     */
    public function getObjectManager();

    /**
     * Gets the state of an object from the unit of work.
     *
     * @param ORMUnitOfWork|MongoDBUnitOfWork $uow    The UnitOfWork as provided by the object manager
     * @param object                          $object
     *
     * @return int The object state as reported by the unit of work
     */
    public function getObjectState($uow, $object);

    /**
     * Gets the changeset for an object from the unit of work.
     *
     * @param ORMUnitOfWork|MongoDBUnitOfWork $uow    The UnitOfWork as provided by the object manager
     * @param object                          $object
     *
     * @return array<string, array<int, mixed>|object>
     *
     * @phpstan-return array<string, array{0: mixed, 1: mixed}|object>
     */
    public function getObjectChangeSet($uow, $object);

    /**
     * Get the single identifier field name.
     *
     * @param ClassMetadata $meta
     *
     * @return string
     */
    public function getSingleIdentifierFieldName($meta);

    /**
     * Computes the changeset of an individual object, independently of the
     * computeChangeSets() routine that is used at the beginning of a unit
     * of work's commit.
     *
     * @param ORMUnitOfWork|MongoDBUnitOfWork $uow    The UnitOfWork as provided by the object manager
     * @param ClassMetadata                   $meta
     * @param object                          $object
     *
     * @return void
     */
    public function recomputeSingleObjectChangeSet($uow, $meta, $object);

    /**
     * Gets the currently scheduled object updates from the unit of work.
     *
     * @param ORMUnitOfWork|MongoDBUnitOfWork $uow The UnitOfWork as provided by the object manager
     *
     * @return array<int|string, object>
     */
    public function getScheduledObjectUpdates($uow);

    /**
     * Gets the currently scheduled object insertions in the unit of work.
     *
     * @param ORMUnitOfWork|MongoDBUnitOfWork $uow The UnitOfWork as provided by the object manager
     *
     * @return array<int|string, object>
     */
    public function getScheduledObjectInsertions($uow);

    /**
     * Gets the currently scheduled object deletions in the unit of work.
     *
     * @param ORMUnitOfWork|MongoDBUnitOfWork $uow The UnitOfWork as provided by the object manager
     *
     * @return array<int|string, object>
     */
    public function getScheduledObjectDeletions($uow);

    /**
     * Sets a property value of the original data array of an object.
     *
     * @param ORMUnitOfWork|MongoDBUnitOfWork $uow
     * @param object                          $object
     * @param string                          $property
     * @param mixed                           $value
     *
     * @return void
     */
    public function setOriginalObjectProperty($uow, $object, $property, $value);

    /**
     * Clears the property changeset of the object with the given OID.
     *
     * @param ORMUnitOfWork|MongoDBUnitOfWork $uow
     * @param object                          $object
     *
     * @return void
     */
    public function clearObjectChangeSet($uow, $object);
}
