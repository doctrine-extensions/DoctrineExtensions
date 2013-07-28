<?php

namespace Gedmo\Sortable\Mapping\Driver;

use Gedmo\Mapping\Driver\File;
use Gedmo\Mapping\Driver;
use Gedmo\Exception\InvalidMappingException;

/**
 * This is a yaml mapping driver for Sortable
 * behavioral extension. Used for extraction of extended
 * metadata from yaml specificaly for Sortable
 * extension.
 *
 * @author Lukas Botsch <lukas.botsch@gmail.com>
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
     * List of types which are valid for position fields
     *
     * @var array
     */
    private $validTypes = array(
        'int',
        'integer',
        'smallint',
        'bigint'
    );

    /**
     * {@inheritDoc}
     */
    public function readExtendedMetadata($meta, array &$config)
    {
        $mapping = $this->_getMapping($meta->name);

        if (isset($mapping['fields'])) {
            foreach ($mapping['fields'] as $field => $fieldMapping) {
                if (isset($fieldMapping['gedmo']) && array_key_exists('sortable', $fieldMapping['gedmo'])) {
                    if (!$meta->hasField($field)) {
                        throw new InvalidMappingException("Sortable field: '{$field}' - is not a mapped property in class {$meta->name}");
                    }
                    if (!$this->isValidField($meta, $field)) {
                        throw new InvalidMappingException("Sortable field: '{$field}' - type is not valid and must be 'integer' in class - {$meta->name}");
                    }
                    if ($meta->isNullable($field)) {
                        throw new InvalidMappingException("Sortable field: '$field' - cannot be nullable in class - {$meta->name}");
                    }
                    $groups = array();
                    if (isset($fieldMapping['gedmo']['sortable']['groups'])) {
                        foreach ($fieldMapping['gedmo']['sortable']['groups'] as $group) {
                            if (!$meta->hasField($group) && !$meta->isSingleValuedAssociation($group)) {
                                throw new InvalidMappingException("Sortable field: '{$field}' group: {$group} - is not a mapped
                                    or single valued association property in class {$meta->name}");
                            }
                            $groups[] = $group;
                        }
                    }
                    $config[$field] = $groups;
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
     * Checks if $field type is valid as SortablePosition field
     *
     * @param object $meta
     * @param string $field
     * @return boolean
     */
    protected function isValidField($meta, $field)
    {
        $mapping = $meta->getFieldMapping($field);
        return $mapping && in_array($mapping['type'], $this->validTypes);
    }
}
