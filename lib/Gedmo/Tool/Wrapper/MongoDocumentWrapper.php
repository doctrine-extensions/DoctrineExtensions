<?php

namespace Gedmo\Tool\Wrapper;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Proxy\Proxy;

/**
 * Wraps document or proxy for more convenient
 * manipulation
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Tool.Wrapper
 * @subpackage MongoDocumentWrapper
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class MongoDocumentWrapper
{
    /**
     * Wrapped Document
     *
     * @var object
     */
    protected $document;

    /**
     * DocumentManager instance
     *
     * @var \Doctrine\ODM\MongoDB\DocumentManager
     */
    protected $dm;

    /**
     * Document metadata
     *
     * @var \Doctrine\ODM\MongoDB\Mapping\ClassMetadata
     */
    protected $meta;

    /**
     * Document identifier
     *
     * @var mixed
     */
    private $identifier = false;

    /**
     * True if document or proxy is loaded
     *
     * @var boolean
     */
    private $initialized = false;

    /**
     * Wrapp document
     *
     * @param object $document
     * @param \Doctrine\ODM\MongoDB\DocumentManager $dm
     */
    public function __construct($document, DocumentManager $dm)
    {
        $this->dm = $dm;
        $this->document = $document;
        $this->meta = $dm->getClassMetadata(get_class($this->document));
    }

    /**
     * Get property value
     *
     * @param string $property
     * @return mixed
     */
    public function getPropertyValue($property)
    {
        $this->initialize();
        return $this->meta->getReflectionProperty($property)->getValue($this->document);
    }

    /**
     * Populates the document with given property values
     *
     * @param array $data
     * @return \Gedmo\Tool\Wrapper\MongoDocumentWrapper
     */
    public function populate(array $data)
    {
        foreach ($data as $field => $value) {
            $this->setPropertyValue($field, $value);
        }
        return $this;
    }

    /**
     * Set the property
     *
     * @param string $property
     * @param mixed $value
     * @return \Gedmo\Tool\Wrapper\MongoDocumentWrapper
     */
    public function setPropertyValue($property, $value)
    {
        $this->initialize();
        $this->meta->getReflectionProperty($property)->setValue($this->document, $value);
        return $this;
    }

    /**
     * Checks if identifier is valid
     *
     * @return boolean
     */
    public function hasValidIdentifier()
    {
        return (bool)$this->getIdentifier();
    }

    /**
     * Get document class name
     *
     * @return string
     */
    public function getClassName()
    {
        return $this->meta->name;
    }

    /**
     * Get the document identifier, single or composite
     *
     * @param boolean $single
     * @return array|mixed
     */
    public function getIdentifier($single = true)
    {
        if (false === $this->identifier) {
            if ($this->document instanceof Proxy) {
                $uow = $this->dm->getUnitOfWork();
                if ($uow->isInIdentityMap($this->document)) {
                    $this->identifier = (string)$uow->getDocumentIdentifier($this->document);
                } else {
                    $this->initialize();
                }
            }
            if (false === $this->identifier) {
                $this->identifier = (string)$this->getPropertyValue($this->meta->identifier);
            }
        }
        return $this->identifier;
    }

    /**
     * Initialize the document if it is proxy
     * required when is detached or not initialized
     */
    protected function initialize()
    {
        if (!$this->initialized) {
            if ($this->document instanceof Proxy) {
                $uow = $this->dm->getUnitOfWork();
                if (!$this->document->__isInitialized__) {
                    $persister = $uow->getDocumentPersister($this->meta->name);
                    $identifier = null;
                    if ($uow->isInIdentityMap($this->document)) {
                        $identifier = $this->getIdentifier();
                    } else {
                        // this may not happen but in case
                        $reflProperty = new \ReflectionProperty($this->document, 'identifier');
                        $reflProperty->setAccessible(true);
                        $identifier = $reflProperty->getValue($this->document);
                    }
                    $this->document->__isInitialized__ = true;
                    $persister->load($identifier, $this->document);
                }
            }
        }
    }
}