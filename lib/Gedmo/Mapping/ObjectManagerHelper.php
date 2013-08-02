<?php

namespace Gedmo\Mapping;

use Doctrine\Common\EventArgs;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo as MongoOdmMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo as OrmMetadata;
use Doctrine\ORM\UnitOfWork as OrmUow;
use Doctrine\ODM\MongoDB\UnitOfWork as MongoOdmUow;
use Doctrine\ORM\Proxy\Proxy as OrmObjectProxy;
use Doctrine\ODM\MongoDB\Proxy\Proxy as MongoOdmObjectProxy;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\PropertyChangedListener;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\Event\LifecycleEventArgs as OrmLifecycleEventArgs;
use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs as MongoOdmLifecycleEventArgs;

use Gedmo\Exception\UnsupportedObjectManagerException;

final class ObjectManagerHelper
{
    /**
     * Attempt to get object manager from event args $args
     *
     * @param \Doctrine\Common\EventArgs $args
     * @return \Doctrine\Common\Persistence\ObjectManager
     * @throws \Gedmo\Exception\UnsupportedObjectManagerException - if manager is not supported
     */
    static function getObjectManagerFromEvent(EventArgs $args)
    {
        if (method_exists($args, 'getEntityManager')) {
            return $args->getEntityManager();
        } elseif (method_exists($args, 'getDocumentManager')) {
            return $args->getDocumentManager();
        }
        throw new UnsupportedObjectManagerException("Could not identify object manager from EventArgs");
    }

    /**
     * Attempt to get object from event args $args
     *
     * @param \Doctrine\Common\EventArgs $args
     * @return Object - doctrine managed domain object
     * @throws \Gedmo\Exception\UnsupportedObjectManagerException - if manager is not supported
     */
    static function getObjectFromEvent(EventArgs $args)
    {
        if (method_exists($args, 'getEntity')) {
            return $args->getEntity();
        } elseif (method_exists($args, 'getDocument')) {
            return $args->getDocument();
        }
        throw new UnsupportedObjectManagerException("Could not identify object manager from EventArgs");
    }

    /**
     * Get the root object class, handles inheritance
     *
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata
     * @return string
     * @throws \Gedmo\Exception\UnsupportedObjectManagerException - if manager is not supported
     */
    static function getRootObjectClass(ClassMetadata $meta)
    {
        if ($meta instanceof OrmMetadata) {
            return $meta->rootEntityName;
        } elseif ($meta instanceof MongoOdmMetadata) {
            return $meta->rootDocumentName;
        }
        throw new UnsupportedObjectManagerException("Could not identify ClassMetadata");
    }

    /**
     * Get the single identifier field name
     *
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata
     * @return string
     * @throws \Gedmo\Exception\UnsupportedObjectManagerException - if manager is not supported
     */
    static function getSingleIdentifierFieldName(ClassMetadata $meta)
    {
        if ($meta instanceof OrmMetadata) {
            return $meta->getSingleIdentifierFieldName();
        } elseif ($meta instanceof MongoOdmMetadata) {
            return $meta->identifier;
        }
        throw new UnsupportedObjectManagerException("Could not identify ClassMetadata");
    }

    /**
     * Checks if $object is a Proxy
     *
     * @param Object $object
     * @return boolean
     */
    static function isProxy($object)
    {
        return $object instanceof OrmObjectProxy || $object instanceof MongoOdmObjectProxy;
    }

    /**
     * Checks wheter the $object is an uninitialized proxy
     *
     * @param Object $object
     * @return boolean
     */
    static function isUninitializedProxy($object)
    {
        return self::isProxy($object) && !$object->__isInitialized__;
    }

    /**
     * Creates lifecycle event args instance based on $om and $object
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $om
     * @param Object $object
     * @return \Doctrine\Common\EventArgs
     */
    static function createLifecycleEventArgsInstance(ObjectManager $om, $object)
    {
        if ($om instanceof EntityManager) {
            return new OrmLifecycleEventArgs($object, $om);
        } elseif ($om instanceof DocumentManager) {
            return new MongoOdmLifecycleEventArgs($object, $om);
        }
        throw new UnsupportedObjectManagerException("Object manager: ".get_class($om)." is not supported");
    }

    /**
     * Get identifier value for managed $object
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $om
     * @param Object $object
     * @param boolean $single - whether single or multiple identifier
     * @return mixed - array of identifiers or single identifier value
     */
    static function getIdentifier(ObjectManager $om, $object, $single = true)
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
                    $identifier = (string)$uow->getDocumentIdentifier($object) ?: null;
                } elseif (!$object->__isInitialized__) {
                    $persister = $uow->getDocumentPersister($meta->name);
                    $refl = new \ReflectionClass(get_class($object));
                    if ($refl->hasProperty('__identifier__')) {
                        $prop = $refl->getProperty('__identifier__');
                    } else {
                        $prop = $refl->getProperty('identifier'); // older version of ODM
                    }
                    $prop->setAccessible(true);
                    $identifier = $prop->getValue($object) ?: null;
                    $object->__isInitialized__ = true;
                    $persister->load($identifier, $object);
                }
            }
            if (null === $identifier) {
                $identifier = (string)$meta->getReflectionProperty($meta->identifier)->getValue($object) ?: null;
            }
            if (null !== $identifier) {
                $identifier = array($meta->identifier => $identifier);
            }
        }
        return $identifier ? ($single ? current($identifier) : $identifier) : null;
    }

    /**
     * Get object changeset from UnitOfWork $uow
     *
     * @param \Doctrine\Common\PropertyChangedListener $uow
     * @param Object - doctrine managed domain object
     * @return array
     * @throws \Gedmo\Exception\UnsupportedObjectManagerException - if manager is not supported
     */
    static function getObjectChangeSet(PropertyChangedListener $uow, $object)
    {
        if ($uow instanceof OrmUow) {
            return $uow->getEntityChangeSet($object);
        } elseif ($uow instanceof MongoOdmUow) {
            return $uow->getDocumentChangeSet($object);
        }
        throw new UnsupportedObjectManagerException("Could not identify UnitOfWork");
    }

    /**
     * Recomputes single object changeset in UnitOfWork $uow
     *
     * @param \Doctrine\Common\PropertyChangedListener $uow
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata
     * @param Object - doctrine managed domain object
     * @throws \Gedmo\Exception\UnsupportedObjectManagerException - if manager is not supported
     */
    static function recomputeSingleObjectChangeSet(PropertyChangedListener $uow, ClassMetadata $meta, $object)
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
     * @param \Doctrine\Common\PropertyChangedListener $uow
     * @return array - classname => array of objects pairs
     * @throws \Gedmo\Exception\UnsupportedObjectManagerException - if manager is not supported
     */
    static function getScheduledObjectUpdates(PropertyChangedListener $uow)
    {
        if ($uow instanceof OrmUow) {
            return $uow->getScheduledEntityUpdates();
        } elseif ($uow instanceof MongoOdmUow) {
            return $uow->getScheduledDocumentUpdates();
        }
        throw new UnsupportedObjectManagerException("Could not identify UnitOfWork");
    }

    /**
     * Get all objects in UnitOfWork $uow which are scheduled as insertions
     *
     * @param \Doctrine\Common\PropertyChangedListener $uow
     * @return array - classname => array of objects pairs
     * @throws \Gedmo\Exception\UnsupportedObjectManagerException - if manager is not supported
     */
    static function getScheduledObjectInsertions(PropertyChangedListener $uow)
    {
        if ($uow instanceof OrmUow) {
            return $uow->getScheduledEntityInsertions();
        } elseif ($uow instanceof MongoOdmUow) {
            return $uow->getScheduledDocumentInsertions();
        }
        throw new UnsupportedObjectManagerException("Could not identify UnitOfWork");
    }

    /**
     * Get all objects in UnitOfWork $uow which are scheduled for deletion
     *
     * @param \Doctrine\Common\PropertyChangedListener $uow
     * @return array - classname => array of objects pairs
     * @throws \Gedmo\Exception\UnsupportedObjectManagerException - if manager is not supported
     */
    static function getScheduledObjectDeletions(PropertyChangedListener $uow)
    {
        if ($uow instanceof OrmUow) {
            return $uow->getScheduledEntityDeletions();
        } elseif ($uow instanceof MongoOdmUow) {
            return $uow->getScheduledDocumentDeletions();
        }
        throw new UnsupportedObjectManagerException("Could not identify UnitOfWork");
    }

    /**
     * Forces original object $property to maintain $value without
     * appearing in the changeset
     *
     * @param \Doctrine\Common\PropertyChangedListener $uow
     * @param string $oid - spl_object_hash od domain object
     * @param string $property
     * @param mixed $value
     * @throws \Gedmo\Exception\UnsupportedObjectManagerException - if manager is not supported
     */
    static function setOriginalObjectProperty(PropertyChangedListener $uow, $oid, $property, $value)
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
     * @param \Doctrine\Common\PropertyChangedListener $uow
     * @param string $oid - spl_object_hash od domain object
     * @throws \Gedmo\Exception\UnsupportedObjectManagerException - if manager is not supported
     */
    static function clearObjectChangeSet(PropertyChangedListener $uow, $oid)
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
