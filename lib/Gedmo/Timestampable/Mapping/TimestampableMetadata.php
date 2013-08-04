<?php

namespace Gedmo\Timestampable\Mapping;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Gedmo\Mapping\ExtensionMetadataInterface;
use Gedmo\Exception\InvalidMappingException;

/**
 * Extension metadata for timestampable behavioral extension.
 * Used to map and validate all metadata collection from
 * extension metadata drivers.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class TimestampableMetadata implements ExtensionMetadataInterface
{
    /**
     * List of stamps and their options
     * in pairs - $field => $options
     *
     * @var array
     */
    private $stamps = array();

    /**
     * List of types which are valid for slug and sluggable fields
     *
     * @var array
     */
    private $validFieldTypes = array(
        'date',
        'time',
        'datetime',
        'datetimetz',
        'timestamp',
        'zenddate',
        'vardatetime',
        'integer'
    );

    /**
     * Map a timestampable field
     *
     * @param string $field
     * @param array $options
     */
    public function map($field, array $options)
    {
        $this->stamps[$field] = $options;
    }

    /**
     * Get all available field names
     *
     * @return array
     */
    public function getFields()
    {
        return array_keys($this->stamps);
    }

    /**
     * Get mapping options for timestampable $field
     *
     * @param string $field
     * @return array - list of options
     */
    public function getOptions($field)
    {
        return isset($this->stamps[$field]) ? $this->stamps[$field] : null;
    }

    /**
     * {@inheritDoc}
     */
    public function validate(ObjectManager $om, ClassMetadata $meta)
    {
        if ($this->isEmpty()) {
            return true;
        }
        foreach ($this->stamps as $field => $options) {
            if (!$meta->hasField($field)) {
                throw new InvalidMappingException("Unable to find [{$field}] as mapped property in class - {$meta->name}");
            }
            $mapping = $meta->getFieldMapping($field);
            if (!in_array($mapping['type'], $this->validFieldTypes)) {
                $valid = implode(', ', $this->validFieldTypes);
                throw new InvalidMappingException("Cannot use field - [{$field}] as timestampable, type is not valid and must be on of: {$valid} in class - {$meta->name}");
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
                    throw new InvalidMappingException("Timestampable extension does not support multiple value changeset detection, in class - {$meta->name}.");
                }
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function isEmpty()
    {
        return count($this->stamps) === 0;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray()
    {
        return $this->stamps;
    }

    /**
     * {@inheritDoc}
     */
    public function fromArray(array $data)
    {
        $this->stamps = $data;
    }
}
