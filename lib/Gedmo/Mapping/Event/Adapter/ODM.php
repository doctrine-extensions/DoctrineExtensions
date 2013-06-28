<?php

namespace Gedmo\Mapping\Event\Adapter;

use Gedmo\Mapping\Event\AdapterInterface;
use Gedmo\Exception\RuntimeException;
use Doctrine\Common\EventArgs;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Doctrine\ODM\MongoDB\Proxy\Proxy;

/**
 * Doctrine event adapter for ODM specific
 * event arguments
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class ODM implements AdapterInterface
{
    /**
     * @var \Doctrine\Common\EventArgs
     */
    private $args;

    /**
     * @var \Doctrine\ODM\MongoDB\DocumentManager
     */
    private $dm;

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
     * {@inheritdoc}
     */
    public function getRootObjectClass($meta)
    {
        return $meta->rootDocumentName;
    }

    /**
     * Set the document manager
     *
     * @param \Doctrine\ODM\MongoDB\DocumentManager $dm
     */
    public function setDocumentManager(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    /**
     * {@inheritdoc}
     */
    public function getObjectManager()
    {
        if (!is_null($this->dm)) {
            return $this->dm;
        }
        return $this->__call('getDocumentManager', array());
    }

    /**
     * {@inheritdoc}
     */
    public function __call($method, $args)
    {
        if (is_null($this->args)) {
            throw new RuntimeException("Event args must be set before calling its methods");
        }
        $method = str_replace('Object', $this->getDomainObjectName(), $method);
        return call_user_func_array(array($this->args, $method), $args);
    }

    /**
     * {@inheritdoc}
     */
    public function getObjectChangeSet($uow, $object)
    {
        return $uow->getDocumentChangeSet($object);
    }

    /**
     * {@inheritdoc}
     */
    public function getSingleIdentifierFieldName($meta)
    {
        return $meta->identifier;
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentifier($object, $single = true)
    {
        $identifier = null;
        $meta = $this->getObjectManager()->getClassMetadata(get_class($object));
        if ($object instanceof Proxy) {
            $uow = $this->dm->getUnitOfWork();
            if ($uow->isInIdentityMap($object)) {
                $identifier = (string)$uow->getDocumentIdentifier($object);
            } else {
                if (!$object->__isInitialized__) {
                    $persister = $uow->getDocumentPersister($meta->name);
                    $reflProperty = new \ReflectionProperty($object, 'identifier');
                    $reflProperty->setAccessible(true);
                    $identifier = $reflProperty->getValue($object);
                    $object->__isInitialized__ = true;
                    $persister->load($identifier, $object);
                }
            }
        }
        if (!$identifier) {
            $identifier = (string)$meta->getReflectionProperty($meta->identifier)->getValue($object);
        }
        return $identifier;
    }

    /**
     * {@inheritdoc}
     */
    public function recomputeSingleObjectChangeSet($uow, $meta, $object)
    {
        $uow->recomputeSingleDocumentChangeSet($meta, $object);
    }

    /**
     * {@inheritdoc}
     */
    public function getScheduledObjectUpdates($uow)
    {
        return $uow->getScheduledDocumentUpdates();
    }

    /**
     * {@inheritdoc}
     */
    public function getScheduledObjectInsertions($uow)
    {
        return $uow->getScheduledDocumentInsertions();
    }

    /**
     * {@inheritdoc}
     */
    public function getScheduledObjectDeletions($uow)
    {
        return $uow->getScheduledDocumentDeletions();
    }

    /**
     * {@inheritdoc}
     */
    public function setOriginalObjectProperty($uow, $oid, $property, $value)
    {
        $uow->setOriginalDocumentProperty($oid, $property, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function clearObjectChangeSet($uow, $oid)
    {
        $uow->clearDocumentChangeSet($oid);
    }

    /**
     * Creates a ODM specific LifecycleEventArgs.
     *
     * @param $document
     * @param \Doctrine\ODM\MongoDB\DocumentManager $documentManager
     * @return \Doctrine\ODM\MongoDB\Event\LifecycleEventArgs
     */
    public function createLifecycleEventArgsInstance($document, $documentManager)
    {
        return new LifecycleEventArgs($document, $documentManager);
    }
}
