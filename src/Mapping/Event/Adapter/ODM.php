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
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Gedmo\Exception\RuntimeException;
use Gedmo\Mapping\Event\AdapterInterface;

/**
 * Doctrine event adapter for ODM specific
 * event arguments
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
class ODM implements AdapterInterface
{
    private ?EventArgs $args = null;

    private ?DocumentManager $dm = null;

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
        return 'Document';
    }

    public function getManagerName()
    {
        return 'ODM';
    }

    /**
     * @param ClassMetadata<object> $meta
     */
    public function getRootObjectClass($meta)
    {
        return $meta->rootDocumentName;
    }

    /**
     * Set the document manager
     *
     * @return void
     */
    public function setDocumentManager(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    /**
     * @return DocumentManager
     */
    public function getObjectManager()
    {
        if (null !== $this->dm) {
            return $this->dm;
        }

        if (null === $this->args) {
            throw new \LogicException(sprintf('Event args must be set before calling "%s()".', __METHOD__));
        }

        return $this->args->getDocumentManager();
    }

    public function getObject(): object
    {
        if (null === $this->args) {
            throw new \LogicException(sprintf('Event args must be set before calling "%s()".', __METHOD__));
        }

        return $this->args->getDocument();
    }

    public function getObjectState($uow, $object)
    {
        return $uow->getDocumentState($object);
    }

    public function getObjectChangeSet($uow, $object)
    {
        return $uow->getDocumentChangeSet($object);
    }

    /**
     * @param ClassMetadata<object> $meta
     */
    public function getSingleIdentifierFieldName($meta)
    {
        return $meta->getIdentifier()[0];
    }

    /**
     * @param ClassMetadata<object> $meta
     */
    public function recomputeSingleObjectChangeSet($uow, $meta, $object)
    {
        $uow->recomputeSingleDocumentChangeSet($meta, $object);
    }

    public function getScheduledObjectUpdates($uow)
    {
        $updates = $uow->getScheduledDocumentUpdates();
        $upserts = $uow->getScheduledDocumentUpserts();

        return array_merge($updates, $upserts);
    }

    public function getScheduledObjectInsertions($uow)
    {
        return $uow->getScheduledDocumentInsertions();
    }

    public function getScheduledObjectDeletions($uow)
    {
        return $uow->getScheduledDocumentDeletions();
    }

    public function setOriginalObjectProperty($uow, $object, $property, $value)
    {
        $uow->setOriginalDocumentProperty(spl_object_hash($object), $property, $value);
    }

    public function clearObjectChangeSet($uow, $object)
    {
        $uow->clearDocumentChangeSet(spl_object_hash($object));
    }

    /**
     * @deprecated to be removed in 4.0, use custom lifecycle event classes instead.
     *
     * Creates a ODM specific LifecycleEventArgs.
     *
     * @param object          $document
     * @param DocumentManager $documentManager
     *
     * @return LifecycleEventArgs
     */
    public function createLifecycleEventArgsInstance($document, $documentManager)
    {
        Deprecation::trigger(
            'gedmo/doctrine-extensions',
            'https://github.com/doctrine-extensions/DoctrineExtensions/pull/2649',
            'Using "%s()" method is deprecated since gedmo/doctrine-extensions 3.15 and will be removed in version 4.0.',
            __METHOD__
        );

        return new LifecycleEventArgs($document, $documentManager);
    }
}
