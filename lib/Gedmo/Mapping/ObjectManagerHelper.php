<?php

namespace Gedmo\Mapping;

use Doctrine\Common\EventArgs;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\Proxy;
use Doctrine\Common\PropertyChangedListener;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs as MongoOdmLifecycleEventArgs;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo as MongoOdmMetadata;
use Doctrine\ODM\MongoDB\Proxy\Proxy as MongoOdmObjectProxy;
use Doctrine\ODM\MongoDB\UnitOfWork as MongoOdmUow;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs as OrmLifecycleEventArgs;
use Doctrine\ORM\Mapping\ClassMetadataInfo as OrmMetadata;
use Doctrine\ORM\Proxy\Proxy as OrmObjectProxy;
use Doctrine\ORM\UnitOfWork as OrmUow;
use Gedmo\Exception\UnsupportedObjectManagerException;

final class ObjectManagerHelper
{
    /**
     * Attempt to get object manager from event args $args
     *
     * @param EventArgs $args
     *
     * @return ObjectManager
     *
     * @throws UnsupportedObjectManagerException - if manager is not supported
     */
    public static function getObjectManagerFromEvent(EventArgs $args)
    {
        if (method_exists($args, 'getObjectManager')) {
            return $args->getObjectManager();
        }

        if (method_exists($args, 'getEntityManager')) {
            return $args->getEntityManager();
        }

        if (method_exists($args, 'getDocumentManager')) {
            return $args->getDocumentManager();
        }

        throw new UnsupportedObjectManagerException("Could not identify object manager from EventArgs");
    }

    /**
     * Attempt to get object from event args $args
     *
     * @param EventArgs $args
     *
     * @return object - doctrine managed domain object
     *
     * @throws UnsupportedObjectManagerException - if manager is not supported
     */
    public static function getObjectFromEvent(EventArgs $args)
    {
        if (method_exists($args, 'getObject')) {
            return $args->getObject();
        }

        if (method_exists($args, 'getEntity')) {
            return $args->getEntity();
        }

        if (method_exists($args, 'getDocument')) {
            return $args->getDocument();
        }

        throw new UnsupportedObjectManagerException("Could not identify object manager from EventArgs");
    }

    /**
     * Get the root object class, handles inheritance
     *
     * @param ClassMetadata $meta
     *
     * @return string
     *
     * @throws UnsupportedObjectManagerException - if manager is not supported
     */
    public static function getRootObjectClass(ClassMetadata $meta)
    {
        if ($meta instanceof OrmMetadata) {
            return $meta->rootEntityName;
        }

        if ($meta instanceof MongoOdmMetadata) {
            return $meta->rootDocumentName;
        }

        throw new UnsupportedObjectManagerException("Could not identify ClassMetadata");
    }

    /**
     * Get the single identifier field name
     *
     * @param ClassMetadata $meta
     *
     * @return string
     *
     * @throws UnsupportedObjectManagerException - if manager is not supported
     */
    public static function getSingleIdentifierFieldName(ClassMetadata $meta)
    {
        if ($meta instanceof OrmMetadata) {
            return $meta->getSingleIdentifierFieldName();
        }

        if ($meta instanceof MongoOdmMetadata) {
            return $meta->identifier;
        }

        throw new UnsupportedObjectManagerException("Could not identify ClassMetadata");
    }

    /**
     * Checks if $object is a Proxy
     *
     * @param object $object
     *
     * @return boolean
     */
    public static function isProxy($object)
    {
        return $object instanceof Proxy || $object instanceof OrmObjectProxy || $object instanceof MongoOdmObjectProxy;
    }

    /**
     * Creates lifecycle event args instance based on $om and $object
     *
     * @param ObjectManager $om
     * @param object        $object
     *
     * @return EventArgs
     */
    public static function createLifecycleEventArgsInstance(ObjectManager $om, $object)
    {
        if ($om instanceof EntityManager) {
            return new OrmLifecycleEventArgs($object, $om);
        }

        if ($om instanceof DocumentManager) {
            return new MongoOdmLifecycleEventArgs($object, $om);
        }

        throw new UnsupportedObjectManagerException("Object manager: ".get_class($om)." is not supported");
    }

    /**
     * Get identifier value for managed $object
     *
     * @param ObjectManager $om
     * @param object        $object
     * @param boolean       $single - whether single or multiple identifier
     *
     * @return mixed - array of identifiers or single identifier value
     */
    public static function getIdentifier(ObjectManager $om, $object, $single = true)
    {
        $meta = $om->getClassMetadata(get_class($object));
        $identifier = null;

        if ($om instanceof EntityManager) {
            if ($object instanceof OrmObjectProxy) {
                $uow = $om->getUnitOfWork();
                if ($uow->isInIdentityMap($object)) {
                    $identifier = $uow->getEntityIdentifier($object);
                } elseif (!$object->__isInitialized__) {
                    $object->__load();
                }
            }
            if (null === $identifier) {
                $identifier = array();
                foreach ($meta->identifier as $name) {
                    if (($identifier[$name] = $meta->getReflectionProperty($name)->getValue($object)) === null) {
                        // is incomplete
                        $identifier = null;
                        break;
                    }
                }
            }
        } elseif ($om instanceof DocumentManager) {
            if ($object instanceof MongoOdmObjectProxy) {
                $uow = $om->getUnitOfWork();
                if ($uow->isInIdentityMap($object)) {
                    $identifier = array($meta->identifier => (string) $uow->getDocumentIdentifier($object));
                } elseif ($object instanceof Proxy && !$object->__isInitialized()) {
                    $object->__load();
                    $identifier = $om->getClassMetadata(get_class($object))->getIdentifierValue($object);
                } elseif (!$object->__isInitialized__) {
                    $persister = $uow->getDocumentPersister($meta->name);
                    $refl = new \ReflectionClass(get_class($object));
                    if ($refl->hasProperty('__identifier__')) {
                        $prop = $refl->getProperty('__identifier__');
                    } else {
                        $prop = $refl->getProperty('identifier'); // older version of ODM
                    }
                    $prop->setAccessible(true);
                    $identifier = $prop->getValue($object);
                    $object->__isInitialized__ = true;
                    $persister->load($identifier, $object);
                }
            }
            if (!$identifier) {
                $identifier = array($meta->identifier => (string) $meta->getReflectionProperty($meta->identifier)->getValue($object));
            }
        }

        return ($single && null !== $identifier) ? (is_array($identifier) ? current($identifier) : $identifier) : $identifier;
    }

    /**
     * Get object changeset from UnitOfWork $uow
     *
     * @param PropertyChangedListener $uow
     * @param object                  $object - doctrine managed domain object
     *
     * @return array
     *
     * @throws UnsupportedObjectManagerException - if manager is not supported
     */
    public static function getObjectChangeSet(PropertyChangedListener $uow, $object)
    {
        if ($uow instanceof OrmUow) {
            return $uow->getEntityChangeSet($object);
        }

        if ($uow instanceof MongoOdmUow) {
            return $uow->getDocumentChangeSet($object);
        }

        throw new UnsupportedObjectManagerException("Could not identify UnitOfWork");
    }

    /**
     * Recomputes single object changeset in UnitOfWork $uow
     *
     * @param PropertyChangedListener $uow
     * @param ClassMetadata           $meta
     * @param object                  $object - doctrine managed domain object
     *
     * @throws UnsupportedObjectManagerException - if manager is not supported
     */
    public static function recomputeSingleObjectChangeSet(PropertyChangedListener $uow, ClassMetadata $meta, $object)
    {
        if ($uow instanceof OrmUow) {
            $uow->recomputeSingleEntityChangeSet($meta, $object);
        } elseif ($uow instanceof MongoOdmUow) {
            $uow->recomputeSingleDocumentChangeSet($meta, $object);
        } else {
            throw new UnsupportedObjectManagerException("Could not identify UnitOfWork");
        }
    }

    /**
     * Get all objects in UnitOfWork $uow which are scheduled as updates
     *
     * @param PropertyChangedListener $uow
     *
     * @return array - classname => array of objects pairs
     *
     * @throws UnsupportedObjectManagerException - if manager is not supported
     */
    public static function getScheduledObjectUpdates(PropertyChangedListener $uow)
    {
        if ($uow instanceof OrmUow) {
            return $uow->getScheduledEntityUpdates();
        }

        if ($uow instanceof MongoOdmUow) {
            $updates = $uow->getScheduledDocumentUpdates();
            $upserts = $uow->getScheduledDocumentUpserts();

            return array_merge($updates, $upserts);
        }

        throw new UnsupportedObjectManagerException("Could not identify UnitOfWork");
    }

    /**
     * Get all objects in UnitOfWork $uow which are scheduled as insertions
     *
     * @param PropertyChangedListener $uow
     *
     * @return array - classname => array of objects pairs
     *
     * @throws UnsupportedObjectManagerException - if manager is not supported
     */
    public static function getScheduledObjectInsertions(PropertyChangedListener $uow)
    {
        if ($uow instanceof OrmUow) {
            return $uow->getScheduledEntityInsertions();
        }

        if ($uow instanceof MongoOdmUow) {
            return $uow->getScheduledDocumentInsertions();
        }

        throw new UnsupportedObjectManagerException("Could not identify UnitOfWork");
    }

    /**
     * Get all objects in UnitOfWork $uow which are scheduled for deletion
     *
     * @param PropertyChangedListener $uow
     *
     * @return array - classname => array of objects pairs
     *
     * @throws UnsupportedObjectManagerException - if manager is not supported
     */
    public static function getScheduledObjectDeletions(PropertyChangedListener $uow)
    {
        if ($uow instanceof OrmUow) {
            return $uow->getScheduledEntityDeletions();
        }

        if ($uow instanceof MongoOdmUow) {
            return $uow->getScheduledDocumentDeletions();
        }

        throw new UnsupportedObjectManagerException("Could not identify UnitOfWork");
    }

    /**
     * Forces original object $property to maintain $value without
     * appearing in the changeset
     *
     * @param PropertyChangedListener $uow
     * @param string                  $oid      - spl_object_hash od domain object
     * @param string                  $property
     * @param mixed                   $value
     *
     * @throws UnsupportedObjectManagerException - if manager is not supported
     */
    public static function setOriginalObjectProperty(PropertyChangedListener $uow, $oid, $property, $value)
    {
        if ($uow instanceof OrmUow) {
            $uow->setOriginalEntityProperty($oid, $property, $value);
        } elseif ($uow instanceof MongoOdmUow) {
            $uow->setOriginalDocumentProperty($oid, $property, $value);
        } else {
            throw new UnsupportedObjectManagerException("Could not identify UnitOfWork");
        }
    }

    /**
     * Flush the changeset of object identified by $oid
     *
     * @param PropertyChangedListener $uow
     * @param string                  $oid - spl_object_hash od domain object
     *
     * @throws UnsupportedObjectManagerException - if manager is not supported
     */
    public static function clearObjectChangeSet(PropertyChangedListener $uow, $oid)
    {
        if ($uow instanceof OrmUow) {
            $uow->clearEntityChangeSet($oid);
        } elseif ($uow instanceof MongoOdmUow) {
            $uow->clearDocumentChangeSet($oid);
        } else {
            throw new UnsupportedObjectManagerException("Could not identify UnitOfWork");
        }
    }
}
