<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tool\Wrapper;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use ProxyManager\Proxy\GhostObjectInterface;

/**
 * Wraps document or proxy for more convenient
 * manipulation
 *
 * @template TObject of object
 *
 * @template-extends AbstractWrapper<ClassMetadata<TObject>, TObject, DocumentManager>
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @final since gedmo/doctrine-extensions 3.11
 */
class MongoDocumentWrapper extends AbstractWrapper
{
    /**
     * Document identifier
     */
    private ?string $identifier = null;

    /**
     * Wrap document
     *
     * @param TObject $document
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

        return $this->meta->getFieldValue($this->object, $property);
    }

    public function getRootObjectName()
    {
        return $this->meta->rootDocumentName;
    }

    public function setPropertyValue($property, $value)
    {
        $this->initialize();
        $this->meta->setFieldValue($this->object, $property, $value);

        return $this;
    }

    public function hasValidIdentifier()
    {
        return (bool) $this->getIdentifier();
    }

    /**
     * @param bool $flatten
     *
     * @return string
     */
    public function getIdentifier($single = true, $flatten = false)
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
        if (method_exists($this->om, 'isUninitializedObject') && $this->om->isUninitializedObject($this->object)) {
            $this->om->initializeObject($this->object);

            return;
        }

        // @todo: Drop support for this fallback when requiring `doctrine/mongodb-odm:^2.6 as a minimum`
        if ($this->object instanceof GhostObjectInterface && !$this->object->isProxyInitialized()) {
            $this->om->initializeObject($this->object);
        }
    }
}
