<?php

namespace Gedmo\Sluggable\Mapping;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Gedmo\Mapping\ExtensionMetadataInterface;
use Gedmo\Mapping\ObjectManagerHelper as OMH;
use Gedmo\Exception\InvalidMappingException;

/**
 * Extension metadata for sluggable behavioral extension.
 * Used to map and validate all metadata collection from
 * extension metadata drivers.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class SluggableMetadata implements ExtensionMetadataInterface
{
    /**
     * List of slugs and their options
     * in pairs - $slugField => $options
     *
     * @var array
     */
    private $slugs = array();

    /**
     * List of types which are valid for slug and sluggable fields
     *
     * @var array
     */
    private $validFieldTypes = array(
        'string',
        'text',
        'integer',
        'int',
    );

    /**
     * Map a sluggable field
     *
     * @param string $field - sluggable field
     * @param array $options - slug options
     */
    public function map($field, array $options)
    {
        $this->slugs[$field] = $options;
    }

    /**
     * Get all available slug field names
     *
     * @return array
     */
    public function getFields()
    {
        return array_keys($this->slugs);
    }

    /**
     * Get mapping options for slug $field
     *
     * @param string $field - slug field
     * @return array - list of options
     */
    public function getOptions($field)
    {
        return isset($this->slugs[$field]) ? $this->slugs[$field] : null;
    }

    /**
     * {@inheritDoc}
     */
    public function validate(ObjectManager $om, ClassMetadata $meta)
    {
        if ($this->isEmpty()) {
            return true;
        }
        foreach ($this->slugs as $field => $slug) {
            if (!$meta->hasField($field)) {
                throw new InvalidMappingException("Unable to find slug [{$field}] as mapped property in class - {$meta->name}");
            }
            $mapping = $meta->getFieldMapping($field);
            if (!in_array($mapping['type'], $this->validFieldTypes)) {
                $valid = implode(', ', $this->validFieldTypes);
                throw new InvalidMappingException("Cannot use field - [{$field}] for slug storage, type is not valid and must be one of: {$valid} in class - {$meta->name}");
            }
            if (empty($slug['fields']) || !is_array($slug['fields'])) {
                throw new InvalidMappingException("Slug must contain at least one field for slug generation in class - {$meta->name}");
            }
            // validate slug fields
            foreach ($slug['fields'] as $slugField) {
                if (!$meta->hasField($slugField)) {
                    throw new InvalidMappingException("Unable to find slug [{$slugField}] as mapped property in class - {$meta->name}");
                }
                $mapping = $meta->getFieldMapping($slugField);
                if (!in_array($mapping['type'], $this->validFieldTypes)) {
                    $valid = implode(', ', $this->validFieldTypes);
                    throw new InvalidMappingException("Cannot use field - [{$slugField}] to slug, type is not valid and must be one of: {$valid} in class - {$meta->name}");
                }
            }
            // validate options
            if (!is_bool($slug['updatable'])) {
                throw new InvalidMappingException("Slug annotation [updatable], type is not valid and must be 'boolean' in class - {$meta->name}");
            }
            if (!is_bool($slug['unique'])) {
                throw new InvalidMappingException("Slug annotation [unique], type is not valid and must be 'boolean' in class - {$meta->name}");
            }
            if (!empty($meta->identifier) && $meta->isIdentifier($field) && !$slug['unique']) {
                throw new InvalidMappingException("Identifier field - [{$field}] slug must be unique in order to maintain primary key in class - {$meta->name}");
            }
            if ($slug['unique'] === false && $slug['unique_base']) {
                throw new InvalidMappingException("Slug annotation [unique_base] can not be set if unique is unset or 'false'");
            }
            if ($slug['unique_base'] && !$meta->hasField($slug['unique_base']) && !$meta->hasAssociation($slug['unique_base'])) {
                throw new InvalidMappingException("Unable to find [{$slug['unique_base']}] as mapped property in entity - {$meta->name}");
            }
            // if slug was available in mapped superclass, need to set the rootClass
            if (null === $slug['rootClass']) {
                $this->slugs[$field]['rootClass'] = OMH::getRootObjectClass($meta);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function isEmpty()
    {
        return count($this->slugs) === 0;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray()
    {
        return $this->slugs;
    }

    /**
     * {@inheritDoc}
     */
    public function fromArray(array $data)
    {
        $this->slugs = $data;
    }
}
