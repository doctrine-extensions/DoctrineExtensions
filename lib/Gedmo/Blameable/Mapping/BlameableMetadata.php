<?php

namespace Gedmo\Blameable\Mapping;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Gedmo\Mapping\ExtensionMetadataInterface;
use Gedmo\Exception\InvalidMappingException;

/**
 * Extension metadata for blameable behavioral extension.
 * Used to map and validate all metadata collection from
 * extension metadata drivers.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class BlameableMetadata implements ExtensionMetadataInterface
{
    /**
     * List of blames and their options
     * in pairs - $field => $options
     *
     * @var array
     */
    private $blames = array();

    /**
     * List of types which are valid for slug and sluggable fields
     *
     * @var array
     */
    private $validFieldTypes = array(
        'one',
        'string',
        'int',
    );

    /**
     * Map a blameable field
     *
     * @param string $field
     * @param array $options
     */
    public function map($field, array $options)
    {
        $this->blames[$field] = $options;
    }

    /**
     * Get all available field names
     *
     * @return array
     */
    public function getFields()
    {
        return array_keys($this->blames);
    }

    /**
     * Get mapping options for blameable $field
     *
     * @param string $field
     * @return array - list of options
     */
    public function getOptions($field)
    {
        return isset($this->blames[$field]) ? $this->blames[$field] : null;
    }

    /**
     * {@inheritDoc}
     */
    public function validate(ObjectManager $om, ClassMetadata $meta)
    {
        if ($this->isEmpty()) {
            return true;
        }
        foreach ($this->blames as $field => $options) {
           if (!$meta->hasField($field) && !$meta->hasAssociation($field)) {
                throw new InvalidMappingException("Unable to find blameable [{$field}] as mapped property in entity - {$meta->name}");
            }
            if ($meta->hasField($field)) {
                $mapping = $meta->getFieldMapping($field);
                if (!in_array($mapping['type'], $this->validFieldTypes)) {
                    $valid = implode(', ', $this->validFieldTypes);
                    throw new InvalidMappingException("Field - [{$field}] type is not valid and must be one of: {$valid} in class - {$meta->name}");
                }
            } elseif (!$meta->isSingleValuedAssociation($field)) {
                throw new InvalidMappingException("Association - [{$field}] is not valid, it must be a many-to-one relation - {$meta->name}");
            }
            if (!in_array($options['on'], $valid = array('update', 'create', 'change'))) {
                $valid = implode(', ', $valid);
                throw new InvalidMappingException("Field - [{$field}] trigger 'on' must be one of [{$valid}] in class - {$meta->name}");
            }
            if ($options['on'] === 'change') {
                if (!isset($options['field'])) {
                    throw new InvalidMappingException("Missing parameters on property - {$field}, 'field' must be set on [change] trigger in class - {$meta->name}");
                }
                if (is_array($options['field']) && isset($options['value'])) {
                    throw new InvalidMappingException("Blameable extension does not support multiple value changeset detection, in class - {$meta->name}.");
                }
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function isEmpty()
    {
        return count($this->blames) === 0;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray()
    {
        return $this->blames;
    }

    /**
     * {@inheritDoc}
     */
    public function fromArray(array $data)
    {
        $this->blames = $data;
    }
}
