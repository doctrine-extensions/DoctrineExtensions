<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tree\Mapping;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Gedmo\Exception\InvalidMappingException;

/**
 * This is a validator for all mapping drivers for Tree
 * behavioral extension, containing methods to validate
 * mapping information
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author <rocco@roccosportal.com>
 *
 * @final since gedmo/doctrine-extensions 3.11
 */
class Validator
{
    /**
     * List of types which are valid for tree fields
     *
     * @var string[]
     */
    private const VALID_TYPES = [
        'integer',
        'smallint',
        'bigint',
        'int',
    ];

    /**
     * List of types which are valid for the path (materialized path strategy)
     *
     * @var string[]
     */
    private $validPathTypes = [
        'string',
        'text',
    ];

    /**
     * List of types which are valid for the path source (materialized path strategy)
     *
     * @var string[]
     */
    private $validPathSourceTypes = [
        'id',
        'integer',
        'smallint',
        'bigint',
        'string',
        'int',
        'float',
    ];

    /**
     * List of types which are valid for the path hash (materialized path strategy)
     *
     * @var string[]
     */
    private $validPathHashTypes = [
        'string',
    ];

    /**
     * List of types which are valid for the path source (materialized path strategy)
     *
     * @var string[]
     */
    private $validRootTypes = [
        'integer',
        'smallint',
        'bigint',
        'int',
        'string',
        'guid',
    ];

    /**
     * Checks if $field type is valid
     *
     * @param ClassMetadata $meta
     * @param string        $field
     *
     * @return bool
     */
    public function isValidField($meta, $field)
    {
        $mapping = $meta->getFieldMapping($field);

        return $mapping && in_array($mapping['type'], self::VALID_TYPES, true);
    }

    /**
     * Checks if $field type is valid for Path field
     *
     * @param ClassMetadata $meta
     * @param string        $field
     *
     * @return bool
     */
    public function isValidFieldForPath($meta, $field)
    {
        $mapping = $meta->getFieldMapping($field);

        return $mapping && in_array($mapping['type'], $this->validPathTypes, true);
    }

    /**
     * Checks if $field type is valid for PathSource field
     *
     * @param ClassMetadata $meta
     * @param string        $field
     *
     * @return bool
     */
    public function isValidFieldForPathSource($meta, $field)
    {
        $mapping = $meta->getFieldMapping($field);

        return $mapping && in_array($mapping['type'], $this->validPathSourceTypes, true);
    }

    /**
     * Checks if $field type is valid for PathHash field
     *
     * @param ClassMetadata $meta
     * @param string        $field
     *
     * @return bool
     */
    public function isValidFieldForPathHash($meta, $field)
    {
        $mapping = $meta->getFieldMapping($field);

        return $mapping && in_array($mapping['type'], $this->validPathHashTypes, true);
    }

    /**
     * Checks if $field type is valid for LockTime field
     *
     * @param ClassMetadata $meta
     * @param string        $field
     *
     * @return bool
     */
    public function isValidFieldForLockTime($meta, $field)
    {
        $mapping = $meta->getFieldMapping($field);

        return $mapping && ('date' === $mapping['type'] || 'datetime' === $mapping['type'] || 'timestamp' === $mapping['type']);
    }

    /**
     * Checks if $field type is valid for Root field
     *
     * @param ClassMetadata $meta
     * @param string        $field
     *
     * @return bool
     */
    public function isValidFieldForRoot($meta, $field)
    {
        $mapping = $meta->getFieldMapping($field);

        return $mapping && in_array($mapping['type'], $this->validRootTypes, true);
    }

    /**
     * Validates metadata for nested type tree
     *
     * @param ClassMetadata $meta
     *
     * @throws InvalidMappingException
     *
     * @return void
     */
    public function validateNestedTreeMetadata($meta, array $config)
    {
        $missingFields = [];
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
            throw new InvalidMappingException('Missing properties: '.implode(', ', $missingFields)." in class - {$meta->getName()}");
        }
    }

    /**
     * Validates metadata for closure type tree
     *
     * @param ClassMetadata $meta
     *
     * @throws InvalidMappingException
     *
     * @return void
     */
    public function validateClosureTreeMetadata($meta, array $config)
    {
        $missingFields = [];
        if (!isset($config['parent'])) {
            $missingFields[] = 'ancestor';
        }
        if (!isset($config['closure'])) {
            $missingFields[] = 'closure class';
        }
        if ($missingFields) {
            throw new InvalidMappingException('Missing properties: '.implode(', ', $missingFields)." in class - {$meta->getName()}");
        }
    }

    /**
     * Validates metadata for materialized path type tree
     *
     * @param ClassMetadata $meta
     *
     * @throws InvalidMappingException
     *
     * @return void
     */
    public function validateMaterializedPathTreeMetadata($meta, array $config)
    {
        $missingFields = [];
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
            throw new InvalidMappingException('Missing properties: '.implode(', ', $missingFields)." in class - {$meta->getName()}");
        }
    }
}
