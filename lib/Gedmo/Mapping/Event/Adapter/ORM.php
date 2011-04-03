<?php

namespace Gedmo\Mapping\Event\Adapter;

use Gedmo\Mapping\Event\AdapterInterface;
use Doctrine\Common\EventArgs;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Proxy\Proxy;

/**
 * Doctrine event adapter for ORM specific
 * event arguments
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Mapping.Event.Adapter
 * @subpackage ORM
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class ORM implements AdapterInterface
{
    /**
     * @var EventArgs
     */
    private $args;

    /**
     * {@inheritdoc}
     */
    public function setEventArgs(EventArgs $args)
    {
        $this->args = $args;
    }

    /**
     * {@inheritdoc}
     */
    public function getDomainObjectName()
    {
        return 'Entity';
    }

    /**
     * {@inheritdoc}
     */
    public function getManagerName()
    {
        return 'ORM';
    }

    /**
     * Extracts identifiers from object or proxy
     *
     * @param EntityManager $em
     * @param object $object
     * @param bool $single
     * @return mixed - array or single identifier
     */
    public function extractIdentifier(EntityManager $em, $object, $single = true)
    {
        if ($object instanceof Proxy) {
            $id = $em->getUnitOfWork()->getEntityIdentifier($object);
        } else {
            $meta = $em->getClassMetadata(get_class($object));
            $id = array();
            foreach ($meta->identifier as $name) {
                $id[$name] = $meta->getReflectionProperty($name)->getValue($object);
                // return null if one of identifiers is missing
                if (!$id[$name]) {
                    return null;
                }
            }
        }

        if ($single) {
            $id = current($id);
        }
        return $id;
    }

    /**
     * Call event specific method
     *
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        $method = str_replace('Object', $this->getDomainObjectName(), $method);
        return call_user_func_array(array($this->args, $method), $args);
    }

    /**
     * Get the object changeset from a UnitOfWork
     *
     * @param UnitOfWork $uow
     * @param Object $object
     * @return array
     */
    public function getObjectChangeSet(UnitOfWork $uow, $object)
    {
        return $uow->getEntityChangeSet($object);
    }

    /**
     * Get the single identifier field name
     *
     * @param ClassMetadataInfo $meta
     * @throws MappingException - if identifier is composite
     * @return string
     */
    public function getSingleIdentifierFieldName(ClassMetadataInfo $meta)
    {
        return $meta->getSingleIdentifierFieldName();
    }

    /**
     * Recompute the single object changeset from a UnitOfWork
     *
     * @param UnitOfWork $uow
     * @param ClassMetadataInfo $meta
     * @param Object $object
     * @return void
     */
    public function recomputeSingleObjectChangeSet(UnitOfWork $uow, ClassMetadataInfo $meta, $object)
    {
        $uow->recomputeSingleEntityChangeSet($meta, $object);
    }

    /**
     * Get the scheduled object updates from a UnitOfWork
     *
     * @param UnitOfWork $uow
     * @return array
     */
    public function getScheduledObjectUpdates(UnitOfWork $uow)
    {
        return $uow->getScheduledEntityUpdates();
    }

    /**
     * Get the scheduled object insertions from a UnitOfWork
     *
     * @param UnitOfWork $uow
     * @return array
     */
    public function getScheduledObjectInsertions(UnitOfWork $uow)
    {
        return $uow->getScheduledEntityInsertions();
    }

    /**
     * Get the scheduled object deletions from a UnitOfWork
     *
     * @param UnitOfWork $uow
     * @return array
     */
    public function getScheduledObjectDeletions(UnitOfWork $uow)
    {
        return $uow->getScheduledEntityDeletions();
    }

    /**
     * Sets a property value of the original data array of an object
     *
     * @param UnitOfWork $uow
     * @param string $oid
     * @param string $property
     * @param mixed $value
     * @return void
     */
    public function setOriginalObjectProperty(UnitOfWork $uow, $oid, $property, $value)
    {
        $uow->setOriginalEntityProperty($oid, $property, $value);
    }

    /**
     * Clears the property changeset of the object with the given OID.
     *
     * @param UnitOfWork $uow
     * @param string $oid The object's OID.
     */
    public function clearObjectChangeSet(UnitOfWork $uow, $oid)
    {
        $uow->clearEntityChangeSet($oid);
    }
}