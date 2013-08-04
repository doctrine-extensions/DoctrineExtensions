<?php

namespace Gedmo\Translatable\Mapping;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Gedmo\Mapping\ExtensionMetadataInterface;
use Gedmo\Mapping\ObjectManagerHelper as OMH;
use Gedmo\Exception\InvalidMappingException;

/**
 * Extension metadata for translatable behavioral extension.
 * Used to map and validate all metadata collection from
 * extension metadata drivers.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class TranslatableMetadata implements ExtensionMetadataInterface
{
    /**
     * List of translatable fields
     *
     * @var array
     */
    private $fields = array();

    /**
     * Translation class name
     *
     * @var string
     */
    private $translationClass;

    /**
     * Map a translatable field
     *
     * @param string $field - translatable field
     */
    public function map($field)
    {
        $this->fields[] = $field;
    }

    /**
     * Get all available slug field names
     *
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Get translation class name
     *
     * @return string
     */
    public function getTranslationClass()
    {
        return $this->translationClass;
    }

    /**
     * {@inheritDoc}
     */
    public function validate(ObjectManager $om, ClassMetadata $meta)
    {
        if ($this->isEmpty()) {
            return;
        }
        foreach ($this->fields as $field) {
            if (!$meta->hasField($field)) {
                throw new InvalidMappingException("Unable to find translatable [{$field}] as mapped property in class - {$meta->name}");
            }
        }
        if (is_array($meta->identifier) && count($meta->identifier) > 1) {
            throw new InvalidMappingException("Translatable does not support composite identifiers in class - {$meta->name}");
        }
        // try to guess translation class
        $this->translationClass = OMH::getRootObjectClass($meta) . 'Translation';
        if (!class_exists($this->translationClass)) {
            // this exception may be cought by translation generator command, it passes an instance of metadata to it
            throw new InvalidMappingException("Translation class {$this->translationClass} for domain object {$meta->name}"
                . ", was not found or could not be autoloaded. If it was not generated yet, use GenerateTranslationsCommand", $this);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function isEmpty()
    {
        return count($this->fields) === 0;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray()
    {
        return array($this->translationClass => $this->fields);
    }

    /**
     * {@inheritDoc}
     */
    public function fromArray(array $data)
    {
        $this->translationClass = key($data);
        $this->fields = current($data);
    }
}
