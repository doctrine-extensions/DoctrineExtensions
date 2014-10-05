<?php

namespace Gedmo\Tree\Mapping;

use Gedmo\Exception\InvalidMappingException;

/**
 * This is a validator for all mapping drivers for Tree
 * behavioral extension, containing methods to validate
 * mapping information
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author <rocco@roccosportal.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Validator
{
    /**
     * List of types which are valid for tree fields
     *
     * @var array
     */
    private $validTypes = array(
        'integer',
        'smallint',
        'bigint',
        'int',
    );

    /**
     * List of types which are valid for the path (materialized path strategy)
     *
     * @var array
     */
    private $validPathTypes = array(
        'string',
        'text',
    );

    /**
     * List of types which are valid for the path source (materialized path strategy)
     *
     * @var array
     */
    private $validPathSourceTypes = array(
        'id',
        'integer',
        'smallint',
        'bigint',
        'string',
        'int',
        'float',
    );

    /**
     * List of types which are valid for the path hash (materialized path strategy)
     *
     * @var array
     */
    private $validPathHashTypes = array(
        'string',
    );

    /**
     * List of types which are valid for the path source (materialized path strategy)
     *
     * @var array
     */
    private $validRootTypes = array(
        'integer',
        'smallint',
        'bigint',
        'int',
        'string',
    );

    /**
     * Checks if $field type is valid
     *
     * @param object $meta
     * @param string $field
     *
     * @return boolean
     */
    public function isValidField($meta, $field)
    {
        $mapping = $meta->getFieldMapping($field);

        return $mapping && in_array($mapping['type'], $this->validTypes);
    }

    /**
     * Checks if $field type is valid for Path field
     *
     * @param object $meta
     * @param string $field
     *
     * @return boolean
     */
    public function isValidFieldForPath($meta, $field)
    {
        $mapping = $meta->getFieldMapping($field);

        return $mapping && in_array($mapping['type'], $this->validPathTypes);
    }

    /**
     * Checks if $field type is valid for PathSource field
     *
     * @param object $meta
     * @param string $field
     *
     * @return boolean
     */
    public function isValidFieldForPathSource($meta, $field)
    {
        $mapping = $meta->getFieldMapping($field);

        return $mapping && in_array($mapping['type'], $this->validPathSourceTypes);
    }

    /**
     * Checks if $field type is valid for PathHash field
     *
     * @param object $meta
     * @param string $field
     *
     * @return boolean
     */
    public function isValidFieldForPathHash($meta, $field)
    {
        $mapping = $meta->getFieldMapping($field);

        return $mapping && in_array($mapping['type'], $this->validPathHashTypes);
    }

    /**
     * Checks if $field type is valid for LockTime field
     *
     * @param object $meta
     * @param string $field
     *
     * @return boolean
     */
    public function isValidFieldForLockTime($meta, $field)
    {
        $mapping = $meta->getFieldMapping($field);

        return $mapping && ($mapping['type'] === 'date' || $mapping['type'] === 'datetime' || $mapping['type'] === 'timestamp');
    }

    /**
     * Checks if $field type is valid for Root field
     *
     * @param object $meta
     * @param string $field
     *
     * @return boolean
     */
    public function isValidFieldForRoot($meta, $field)
    {
        $mapping = $meta->getFieldMapping($field);

        return $mapping && in_array($mapping['type'], $this->validRootTypes);
    }

    /**
     * Validates metadata for nested type tree
     *
     * @param object $meta
     * @param array  $config
     *
     * @throws InvalidMappingException
     */
    public function validateNestedTreeMetadata($meta, array $config)
    {
        $missingFields = array();
        if (!isset($config['parent'])) {
            $missingFields[] = 'ancestor';
        }
        if (!isset($config['left'])) {
            $missingFields[] = 'left';
        }
        if (!isset($config['right'])) {
            $missingFields[] = 'right';
        }
        if ($missingFields) {
            throw new InvalidMappingException("Missing properties: ".implode(', ', $missingFields)." in class - {$meta->name}");
        }
    }

    /**
     * Validates metadata for closure type tree
     *
     * @param object $meta
     * @param array  $config
     *
     * @throws InvalidMappingException
     */
    public function validateClosureTreeMetadata($meta, array $config)
    {
        $missingFields = array();
        if (!isset($config['parent'])) {
            $missingFields[] = 'ancestor';
        }
        if (!isset($config['closure'])) {
            $missingFields[] = 'closure class';
        }
        if ($missingFields) {
            throw new InvalidMappingException("Missing properties: ".implode(', ', $missingFields)." in class - {$meta->name}");
        }
    }

    /**
     * Validates metadata for materialized path type tree
     *
     * @param object $meta
     * @param array  $config
     *
     * @throws InvalidMappingException
     */
    public function validateMaterializedPathTreeMetadata($meta, array $config)
    {
        $missingFields = array();
        if (!isset($config['parent'])) {
            $missingFields[] = 'ancestor';
        }
        if (!isset($config['path'])) {
            $missingFields[] = 'path';
        }
        if (!isset($config['path_source'])) {
            $missingFields[] = 'path_source';
        }
        if ($missingFields) {
            throw new InvalidMappingException("Missing properties: ".implode(', ', $missingFields)." in class - {$meta->name}");
        }
    }
}
