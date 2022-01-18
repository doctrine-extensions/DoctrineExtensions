<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tool\Wrapper;

use Doctrine\ODM\MongoDB\DocumentManager;
use ProxyManager\Proxy\GhostObjectInterface;

/**
 * Wraps document or proxy for more convenient
 * manipulation
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
class MongoDocumentWrapper extends AbstractWrapper
{
    /**
     * Document identifier
     *
     * @var mixed
     */
    private $identifier;

    /**
     * True if document or proxy is loaded
     *
     * @var bool
     */
    private $initialized = false;

    /**
     * Wrap document
     *
     * @param object $document
     */
    public function __construct($document, DocumentManager $dm)
    {
        $this->om = $dm;
        $this->object = $document;
        $this->meta = $dm->getClassMetadata(get_class($this->object));
    }

    public function getPropertyValue($property)
    {
        $this->initialize();

        return $this->meta->getReflectionProperty($property)->getValue($this->object);
    }

    public function getRootObjectName()
    {
        return $this->meta->rootDocumentName;
    }

    public function setPropertyValue($property, $value)
    {
        $this->initialize();
        $this->meta->getReflectionProperty($property)->setValue($this->object, $value);

        return $this;
    }

    public function hasValidIdentifier()
    {
        return (bool) $this->getIdentifier();
    }

    public function getIdentifier($single = true)
    {
        if (!$this->identifier) {
            if ($this->object instanceof GhostObjectInterface) {
                $uow = $this->om->getUnitOfWork();
                if ($uow->isInIdentityMap($this->object)) {
                    $this->identifier = (string) $uow->getDocumentIdentifier($this->object);
                } else {
                    $this->initialize();
                }
            }
            if (!$this->identifier) {
                $this->identifier = (string) $this->getPropertyValue($this->meta->identifier);
            }
        }

        return $this->identifier;
    }

    public function isEmbeddedAssociation($field)
    {
        return $this->getMetadata()->isSingleValuedEmbed($field);
    }

    /**
     * Initialize the document if it is proxy
     * required when is detached or not initialized
     *
     * @return void
     */
    protected function initialize()
    {
        if (!$this->initialized) {
            if ($this->object instanceof GhostObjectInterface) {
                $uow = $this->om->getUnitOfWork();
                if (!$this->object->isProxyInitialized()) {
                    $persister = $uow->getDocumentPersister($this->meta->getName());
                    $identifier = null;
                    if ($uow->isInIdentityMap($this->object)) {
                        $identifier = $this->getIdentifier();
                    } else {
                        // this may not happen but in case
                        $getIdentifier = \Closure::bind(function () {
                            return $this->identifier;
                        }, $this->object, get_class($this->object));

                        $identifier = $getIdentifier();
                    }
                    $this->object->initializeProxy();
                    $persister->load($identifier, $this->object);
                }
            }
        }
    }
}
