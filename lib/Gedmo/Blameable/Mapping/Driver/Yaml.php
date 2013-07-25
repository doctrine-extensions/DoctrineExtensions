<?php

namespace Gedmo\Blameable\Mapping\Driver;

use Gedmo\Mapping\Driver\File,
    Gedmo\Mapping\Driver,
    Gedmo\Exception\InvalidMappingException;

/**
 * This is a yaml mapping driver for Blameable
 * behavioral extension. Used for extraction of extended
 * metadata from yaml specifically for Blameable
 * extension.
 *
 * @author David Buchmann <mail@davidbu.ch>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Yaml extends File implements Driver
{
    /**
     * File extension
     * @var string
     */
    protected $_extension = '.dcm.yml';

    /**
     * List of types which are valid for blameable
     *
     * @var array
     */
    private $validTypes = array(
        'one',
        'string',
        'int',
    );

    /**
     * {@inheritDoc}
     */
    public function readExtendedMetadata($meta, array &$config)
    {
        $mapping = $this->_getMapping($meta->name);

        if (isset($mapping['fields'])) {
            foreach ($mapping['fields'] as $field => $fieldMapping) {
                if (isset($fieldMapping['gedmo']['blameable'])) {
                    $mappingProperty = $fieldMapping['gedmo']['blameable'];
                    if (!$this->isValidField($meta, $field)) {
                        throw new InvalidMappingException("Field - [{$field}] type is not valid and must be 'string' or a reference in class - {$meta->name}");
                    }
                    if (!isset($mappingProperty['on']) || !in_array($mappingProperty['on'], array('update', 'create', 'change'))) {
                        throw new InvalidMappingException("Field - [{$field}] trigger 'on' is not one of [update, create, change] in class - {$meta->name}");
                    }

                    if ($mappingProperty['on'] == 'change') {
                        if (!isset($mappingProperty['field'])) {
                            throw new InvalidMappingException("Missing parameters on property - {$field}, field must be set on [change] trigger in class - {$meta->name}");
                        }
                        $trackedFieldAttribute = $mappingProperty['field'];
                        $valueAttribute = isset($mappingProperty['value']) ? $mappingProperty['value'] : null;
                        if (is_array($trackedFieldAttribute) && null !== $valueAttribute) {
                            throw new InvalidMappingException("Timestampable extension does not support multiple value changeset detection yet.");
                        }
                        $field = array(
                            'field' => $field,
                            'trackedField' => $trackedFieldAttribute,
                            'value' => $valueAttribute,
                        );
                    }
                    $config[$mappingProperty['on']][] = $field;
                }
            }
        }

        if (isset($mapping['manyToOne'])) {
            foreach ($mapping['manyToOne'] as $field => $fieldMapping) {
                if (isset($fieldMapping['gedmo']['blameable'])) {
                    $mappingProperty = $fieldMapping['gedmo']['blameable'];
                    if (! $meta->isSingleValuedAssociation($field)) {
                        throw new InvalidMappingException("Association - [{$field}] is not valid, it must be a one-to-many relation or a string field - {$meta->name}");
                    }
                    if (!isset($mappingProperty['on']) || !in_array($mappingProperty['on'], array('update', 'create', 'change'))) {
                        throw new InvalidMappingException("Field - [{$field}] trigger 'on' is not one of [update, create, change] in class - {$meta->name}");
                    }

                    if ($mappingProperty['on'] == 'change') {
                        if (!isset($mappingProperty['field'])) {
                            throw new InvalidMappingException("Missing parameters on property - {$field}, field must be set on [change] trigger in class - {$meta->name}");
                        }
                        $trackedFieldAttribute = $mappingProperty['field'];
                        $valueAttribute = isset($mappingProperty['value']) ? $mappingProperty['value'] : null;
                        if (is_array($trackedFieldAttribute) && null !== $valueAttribute) {
                            throw new InvalidMappingException("Timestampable extension does not support multiple value changeset detection yet.");
                        }
                        $field = array(
                            'field' => $field,
                            'trackedField' => $trackedFieldAttribute,
                            'value' => $valueAttribute,
                        );
                    }
                    $config[$mappingProperty['on']][] = $field;
                }
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function _loadMappingFile($file)
    {
        return \Symfony\Component\Yaml\Yaml::parse($file);
    }

    /**
     * Checks if $field type is valid
     *
     * @param \Doctrine\ODM\MongoDB\Mapping\ClassMetadata $meta
     * @param string $field
     * @return boolean
     */
    protected function isValidField($meta, $field)
    {
        $mapping = $meta->getFieldMapping($field);
        return $mapping && in_array($mapping['type'], $this->validTypes);
    }
}
