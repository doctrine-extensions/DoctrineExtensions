<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tool\Wrapper;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Proxy\Proxy;
use Doctrine\Persistence\Proxy as PersistenceProxy;

/**
 * Wraps entity or proxy for more convenient
 * manipulation
 *
 * @phpstan-extends AbstractWrapper<ClassMetadata>
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @final since gedmo/doctrine-extensions 3.11
 */
class EntityWrapper extends AbstractWrapper
{
    /**
     * Entity identifier
     *
     * @var array|null
     */
    private $identifier;

    /**
     * True if entity or proxy is loaded
     *
     * @var bool
     */
    private $initialized = false;

    /**
     * Wrap entity
     *
     * @param object $entity
     */
    public function __construct($entity, EntityManagerInterface $em)
    {
        $this->om = $em;
        $this->object = $entity;
        $this->meta = $em->getClassMetadata(get_class($this->object));
    }

    public function getPropertyValue($property)
    {
        $this->initialize();

        return $this->meta->getReflectionProperty($property)->getValue($this->object);
    }

    public function setPropertyValue($property, $value)
    {
        $this->initialize();
        $this->meta->getReflectionProperty($property)->setValue($this->object, $value);

        return $this;
    }

    public function hasValidIdentifier()
    {
        return null !== $this->getIdentifier();
    }

    public function getRootObjectName()
    {
        return $this->meta->rootEntityName;
    }

    public function getIdentifier($single = true)
    {
        if (null === $this->identifier) {
            if ($this->object instanceof Proxy) {
                $uow = $this->om->getUnitOfWork();
                if ($uow->isInIdentityMap($this->object)) {
                    $this->identifier = $uow->getEntityIdentifier($this->object);
                } else {
                    $this->initialize();
                }
            }
            if (null === $this->identifier) {
                $this->identifier = [];
                $incomplete = false;
                foreach ($this->meta->identifier as $name) {
                    $this->identifier[$name] = $this->getPropertyValue($name);
                    if (null === $this->identifier[$name]) {
                        $incomplete = true;
                    }
                }
                if ($incomplete) {
                    $this->identifier = null;
                }
            }
        }
        if ($single && is_array($this->identifier)) {
            return reset($this->identifier);
        }

        return $this->identifier;
    }

    public function isEmbeddedAssociation($field)
    {
        return false;
    }

    /**
     * Initialize the entity if it is proxy
     * required when is detached or not initialized
     *
     * @return void
     */
    protected function initialize()
    {
        if (!$this->initialized) {
            if ($this->object instanceof PersistenceProxy) {
                if (!$this->object->__isInitialized()) {
                    $this->object->__load();
                }
            }
        }
    }
}
