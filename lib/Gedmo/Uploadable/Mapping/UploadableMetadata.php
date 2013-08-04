<?php

namespace Gedmo\Uploadable\Mapping;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Gedmo\Mapping\ExtensionMetadataInterface;
use Gedmo\Mapping\ObjectManagerHelper as OMH;
use Gedmo\Exception\InvalidMappingException;

/**
 * Extension metadata for uploadable behavioral extension.
 * Used to map and validate all metadata collection from
 * extension metadata drivers.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class UploadableMetadata implements ExtensionMetadataInterface
{
    /**
     * List of uploadable versionedFields
     *
     * @var array
     */
    private $versionedFields = array();

    /**
     * log class name
     *
     * @var string
     */
    private $logClass;

    /**
     * Map a uploadable field
     *
     * @param string $field - uploadable field
     */
    public function map($field)
    {
        $this->versionedFields[] = $field;
    }

    /**
     * Get all available slug field names
     *
     * @return array
     */
    public function getVersionedFields()
    {
        return $this->versionedFields;
    }

    /**
     * Get log class name
     *
     * @return string
     */
    public function getlogClass()
    {
        return $this->logClass;
    }

    /**
     * Get log class name
     *
     * @param string $class
     * @return string
     */
    public function setlogClass($class)
    {
        $this->logClass = $class;
    }

    /**
     * {@inheritDoc}
     */
    public function validate(ObjectManager $om, ClassMetadata $meta)
    {
        if ($this->isEmpty()) {
            return;
        }
        foreach ($this->versionedFields as $field) {
            if ($meta->isCollectionValuedAssociation($field)) {
                throw new InvalidMappingException("Cannot version [{$field}] as it is collection in class - {$meta->name}");
            } elseif (!$meta->isSingleValuedAssociation($field) && !$meta->hasField($field)) {
                throw new InvalidMappingException("Unable to find versioned [{$field}] as mapped property in class - {$meta->name}");
            }
        }
        if (is_array($meta->identifier) && count($meta->identifier) > 1) {
            throw new InvalidMappingException("Uploadable does not support composite identifiers in class - {$meta->name}");
        }
        if ($this->logClass) {
            if (!class_exists($this->logClass)) {
                throw new InvalidMappingException("log class {$this->logClass} for domain object {$meta->name}"
                    . ", was not found or could not be autoloaded.", $this);
            }
        } else {
            $this->logClass = $om instanceof DocumentManager ? self::DEFAULT_LOG_CLASS_ODM_MONGODB : self::DEFAULT_LOG_CLASS_ORM;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function isEmpty()
    {
        return count($this->versionedFields) === 0;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray()
    {
        return array($this->logClass => $this->versionedFields);
    }

    /**
     * {@inheritDoc}
     */
    public function fromArray(array $data)
    {
        $this->logClass = key($data);
        $this->versionedFields = current($data);
    }
}
