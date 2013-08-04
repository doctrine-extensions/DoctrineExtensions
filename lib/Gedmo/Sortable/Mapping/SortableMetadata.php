<?php

namespace Gedmo\Sortable\Mapping;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Gedmo\Mapping\ExtensionMetadataInterface;
use Gedmo\Mapping\ObjectManagerHelper as OMH;
use Gedmo\Exception\InvalidMappingException;

/**
 * Extension metadata for sortable behavioral extension.
 * Used to map and validate all metadata collection from
 * extension metadata drivers.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class SortableMetadata implements ExtensionMetadataInterface
{
    /**
     * List of positions and their options
     * in pairs - $field => $options
     *
     * @var array
     */
    private $positions = array();

    /**
     * @var array
     */
    private $validFieldTypes = array(
        'int',
        'integer',
        'smallint',
        'bigint'
    );

    /**
     * Map a sortable field
     *
     * @param string $field
     * @param array $options
     */
    public function map($field, array $options)
    {
        $this->positions[$field] = $options;
    }

    /**
     * Get all available field names
     *
     * @return array
     */
    public function getFields()
    {
        return array_keys($this->positions);
    }

    /**
     * Get mapping options for sortable $field
     *
     * @param string $field
     * @return array - list of options
     */
    public function getOptions($field)
    {
        return isset($this->positions[$field]) ? $this->positions[$field] : null;
    }

    /**
     * {@inheritDoc}
     */
    public function validate(ObjectManager $om, ClassMetadata $meta)
    {
        if ($this->isEmpty()) {
            return true;
        }
        foreach ($this->positions as $field => $options) {
            if (!$meta->hasField($field)) {
                throw new InvalidMappingException("Sortable field: '{$field}' - is not a mapped property in class {$meta->name}");
            }
            $mapping = $meta->getFieldMapping($field);
            if (!in_array($mapping['type'], $this->validFieldTypes)) {
                $valid = implode(', ', $this->validFieldTypes);
                throw new InvalidMappingException("Sortable field: '{$field}' - type is not valid and must be one of: {$valid} in class - {$meta->name}");
            }
            if ($meta->isNullable($field)) {
                throw new InvalidMappingException("Sortable field: '$field' - cannot be nullable in class - {$meta->name}");
            }
            // validate slug fields
            foreach ($options['groups'] as $group) {
                if (!$meta->hasField($group) && !$meta->isSingleValuedAssociation($group)) {
                    throw new InvalidMappingException("Sortable field: '{$field}' group: {$group} - is not a mapped field
                        or single valued association property in class {$meta->name}");
                }
            }
            // handle mapped superclass, need to set the rootClass
            if (null === $options['rootClass']) {
                $this->positions[$field]['rootClass'] = OMH::getRootObjectClass($meta);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function isEmpty()
    {
        return count($this->positions) === 0;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray()
    {
        return $this->positions;
    }

    /**
     * {@inheritDoc}
     */
    public function fromArray(array $data)
    {
        $this->positions = $data;
    }
}
