<?php

namespace Gedmo\Mapping\Event\Adapter;

use Gedmo\Mapping\Event\AdapterInterface;
use Doctrine\Common\EventArgs;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Proxy\Proxy;

/**
 * Doctrine event adapter for ODM specific
 * event arguments
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Mapping.Event.Adapter
 * @subpackage ODM
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class ODM implements AdapterInterface
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
        return 'Document';
    }

    /**
     * {@inheritdoc}
     */
    public function getManagerName()
    {
        return 'ODM';
    }

    /**
     * Extracts identifiers from object or proxy
     *
     * @param DocumentManager $dm
     * @param object $object
     * @param bool $single
     * @return mixed - array or single identifier
     */
    public function extractIdentifier(DocumentManager $dm, $object, $single = true)
    {
        $meta = $dm->getClassMetadata(get_class($object));
        if ($object instanceof Proxy) {
            $id = $dm->getUnitOfWork()->getDocumentIdentifier($object);
        } else {
            $id = $meta->getReflectionProperty($meta->identifier)->getValue($object);
        }

        if ($single || !$id) {
            return $id;
        } else {
            return array($meta->identifier => $id);
        }
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
        return $uow->getDocumentChangeSet($object);
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
        return $meta->identifier;
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
        $uow->recomputeSingleDocumentChangeSet($meta, $object);
    }

    /**
     * Get the scheduled object updates from a UnitOfWork
     *
     * @param UnitOfWork $uow
     * @return array
     */
    public function getScheduledObjectUpdates(UnitOfWork $uow)
    {
        return $uow->getScheduledDocumentUpdates();
    }

    /**
     * Get the scheduled object insertions from a UnitOfWork
     *
     * @param UnitOfWork $uow
     * @return array
     */
    public function getScheduledObjectInsertions(UnitOfWork $uow)
    {
        return $uow->getScheduledDocumentInsertions();
    }

    /**
     * Get the scheduled object deletions from a UnitOfWork
     *
     * @param UnitOfWork $uow
     * @return array
     */
    public function getScheduledObjectDeletions(UnitOfWork $uow)
    {
        return $uow->getScheduledDocumentDeletions();
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
        $uow->setOriginalDocumentProperty($oid, $property, $value);
    }

    /**
     * Clears the property changeset of the object with the given OID.
     *
     * @param UnitOfWork $uow
     * @param string $oid The object's OID.
     */
    public function clearObjectChangeSet(UnitOfWork $uow, $oid)
    {
        $uow->clearDocumentChangeSet($oid);
    }
}