<?php

namespace Gedmo\Tool\Wrapper;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Proxy\Proxy;

/**
 * Wraps entity or proxy for more convenient
 * manipulation
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Tool.Wrapper
 * @subpackage EntityWrapper
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class EntityWrapper
{
    /**
     * Wrapped Entity
     *
     * @var object
     */
    protected $entity;

    /**
     * EntityManager instance
     *
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * Entity metadata
     *
     * @var \Doctrine\ORM\Mapping\ClassMetadata
     */
    protected $meta;

    /**
     * Entity identifier
     *
     * @var array
     */
    private $identifier = false;

    /**
     * True if entity or proxy is loaded
     *
     * @var boolean
     */
    private $initialized = false;

    /**
     * Wrapp entity
     *
     * @param object $entity
     * @param \Doctrine\ORM\EntityManager $em
     */
    public function __construct($entity, EntityManager $em)
    {
        $this->em = $em;
        $this->entity = $entity;
        $this->meta = $em->getClassMetadata(get_class($this->entity));
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
        return $this->meta->getReflectionProperty($property)->getValue($this->entity);
    }

    /**
     * Populates the entity with given property values
     *
     * @param array $data
     * @return \Gedmo\Tool\Wrapper\EntityWrapper
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
     * @return \Gedmo\Tool\Wrapper\EntityWrapper
     */
    public function setPropertyValue($property, $value)
    {
        $this->initialize();
        $this->meta->getReflectionProperty($property)->setValue($this->entity, $value);
        return $this;
    }

    /**
     * Checks if identifier is valid
     *
     * @return boolean
     */
    public function hasValidIdentifier()
    {
        $result = true;
        foreach ($this->getIdentifier(false) as $field => $id) {
            if (!$id) {
                $result = false;
                break;
            }
        }
        return $result;
    }

    /**
     * Get entity class name
     *
     * @return string
     */
    public function getClassName()
    {
        return $this->meta->name;
    }

    /**
     * Get the entity identifier, single or composite
     *
     * @param boolean $single
     * @return array|mixed
     */
    public function getIdentifier($single = true)
    {
        if (false === $this->identifier) {
            if ($this->entity instanceof Proxy) {
                $uow = $this->em->getUnitOfWork();
                if ($uow->isInIdentityMap($this->entity)) {
                    $this->identifier = $uow->getEntityIdentifier($this->entity);
                } else {
                    $this->initialize();
                }
            }
            if (false === $this->identifier) {
                $this->identifier = array();
                foreach ($this->meta->identifier as $name) {
                    $this->identifier[$name] = $this->getPropertyValue($name);
                }
            }
        }
        if ($single) {
            return reset($this->identifier);
        }
        return $this->identifier;
    }

    /**
     * Initialize the entity if it is proxy
     * required when is detached or not initialized
     */
    protected function initialize()
    {
        if (!$this->initialized) {
            if ($this->entity instanceof Proxy) {
                $uow = $this->em->getUnitOfWork();
                if (!$this->entity->__isInitialized__) {
                    $this->entity->__load();
                }
            }
        }
    }
}