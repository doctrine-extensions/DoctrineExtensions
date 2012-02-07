<?php

namespace Gedmo\Tree\Mapping\Driver;

use Gedmo\Mapping\Driver\File,
    Gedmo\Mapping\Driver,
    Gedmo\Exception\InvalidMappingException;

/**
 * This is a yaml mapping driver for Tree
 * behavioral extension. Used for extraction of extended
 * metadata from yaml specificaly for Tree
 * extension.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Tree.Mapping.Driver
 * @subpackage Yaml
 * @link http://www.gediminasm.org
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
     * List of types which are valid for tree fields
     *
     * @var array
     */
    private $validTypes = array(
        'integer',
        'smallint',
        'bigint',
        'int'
    );

    /**
     * List of types which are valid for the path (materialized path strategy)
     *
     * @var array
     */
    private $validPathTypes = array(
        'string',
        'text'
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
        'float'
    );

    /**
     * List of tree strategies available
     *
     * @var array
     */
    private $strategies = array(
        'nested',
        'closure',
        'materializedPath'
    );

    /**
     * {@inheritDoc}
     */
    public function readExtendedMetadata($meta, array &$config)
    {
        $mapping = $this->_getMapping($meta->name);

        if (isset($mapping['gedmo'])) {
            $classMapping = $mapping['gedmo'];
            if (isset($classMapping['tree']['type'])) {
                $strategy = $classMapping['tree']['type'];
                if (!in_array($strategy, $this->strategies)) {
                    throw new InvalidMappingException("Tree type: $strategy is not available.");
                }
                $config['strategy'] = $strategy;
                $config['activate_locking'] = isset($classMapping['tree']['activate_locking']) ?
                    $classMapping['tree']['activate_locking'] : false;
                $config['locking_timeout'] = isset($classMapping['tree']['locking_timeout']) ?
                    $classMapping['tree']['locking_timeout'] : 3;

                if (!is_int($config['locking_timeout'])) {
                    throw new InvalidMappingException("Tree Locking timeout must be an integer value.");
                }
            }
            if (isset($classMapping['tree']['closure'])) {
                $class = $classMapping['tree']['closure'];
                if (!class_exists($class)) {
                    throw new InvalidMappingException("Tree closure class: {$class} does not exist.");
                }
                $config['closure'] = $class;
            }
        }
        if (isset($mapping['fields'])) {
            foreach ($mapping['fields'] as $field => $fieldMapping) {
                if (isset($fieldMapping['gedmo'])) {
                    if (in_array('treeLeft', $fieldMapping['gedmo'])) {
                        if (!$this->isValidField($meta, $field)) {
                            throw new InvalidMappingException("Tree left field - [{$field}] type is not valid and must be 'integer' in class - {$meta->name}");
                        }
                        $config['left'] = $field;
                    } elseif (in_array('treeRight', $fieldMapping['gedmo'])) {
                        if (!$this->isValidField($meta, $field)) {
                            throw new InvalidMappingException("Tree right field - [{$field}] type is not valid and must be 'integer' in class - {$meta->name}");
                        }
                        $config['right'] = $field;
                    } elseif (in_array('treeLevel', $fieldMapping['gedmo'])) {
                        if (!$this->isValidField($meta, $field)) {
                            throw new InvalidMappingException("Tree level field - [{$field}] type is not valid and must be 'integer' in class - {$meta->name}");
                        }
                        $config['level'] = $field;
                    } elseif (in_array('treeRoot', $fieldMapping['gedmo'])) {
                        if (!$meta->getFieldMapping($field)) {
                            throw new InvalidMappingException("Tree root field - [{$field}] type is not valid in class - {$meta->name}");
                        }
                        $config['root'] = $field;
                    } elseif (in_array('treePath', $fieldMapping['gedmo'])) {
                        if (!$this->isValidFieldForPath($meta, $field)) {
                            throw new InvalidMappingException("Tree Path field - [{$field}] type is not valid. It must be string or text in class - {$meta->name}");
                        }
                        $separator = $fieldMapping['gedmo']['treePath']['separator'];
                        if (strlen($separator) > 1) {
                            throw new InvalidMappingException("Tree Path field - [{$field}] Separator {$separator} is invalid. It must be only one character long.");
                        }
                        $config['path'] = $field;
                        $config['path_separator'] = $separator;
                    } elseif (in_array('treePathSource', $fieldMapping['gedmo'])) {
                        if (!$this->isValidFieldForPathSource($meta, $field)) {
                            throw new InvalidMappingException("Tree PathSource field - [{$field}] type is not valid. It can be any of the integer variants, double, float or string in class - {$meta->name}");
                        }
                        $config['path_source'] = $field;
                    } elseif (in_array('treeLockTime', $fieldMapping['gedmo'])) {
                        if (!$this->isValidFieldForLocktime($meta, $field)) {
                            throw new InvalidMappingException("Tree LockTime field - [{$field}] type is not valid. It must be \"date\" in class - {$meta->name}");
                        }
                        $config['lock_time'] = $field;
                    }
                }
            }
        }

        if (isset($config['activate_locking']) && $config['activate_locking'] && !isset($config['lock_time'])) {
            throw new InvalidMappingException("You need to map a date field as the tree lock time field to activate locking support.");
        }

        if (isset($mapping['manyToOne'])) {
            foreach ($mapping['manyToOne'] as $field => $relationMapping) {
                if (isset($relationMapping['gedmo'])) {
                    if (in_array('treeParent', $relationMapping['gedmo'])) {
                        if ($relationMapping['targetEntity'] != $meta->name) {
                            throw new InvalidMappingException("Unable to find ancestor/parent child relation through ancestor field - [{$field}] in class - {$meta->name}");
                        }
                        $config['parent'] = $field;
                    }
                }
            }
        }

        if (!$meta->isMappedSuperclass && $config) {
            if (isset($config['strategy'])) {
                if (is_array($meta->identifier) && count($meta->identifier) > 1) {
                    throw new InvalidMappingException("Tree does not support composite identifiers in class - {$meta->name}");
                }
                $method = 'validate' . ucfirst($config['strategy']) . 'TreeMetadata';
                $this->$method($meta, $config);
            } else {
                throw new InvalidMappingException("Cannot find Tree type for class: {$meta->name}");
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function _loadMappingFile($file)
    {
        return \Symfony\Component\Yaml\Yaml::load($file);
    }

    /**
     * Checks if $field type is valid
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

    /**
     * Checks if $field type is valid for Path field
     *
     * @param object $meta
     * @param string $field
     * @return boolean
     */
    protected function isValidFieldForPath($meta, $field)
    {
        $mapping = $meta->getFieldMapping($field);
        return $mapping && in_array($mapping['type'], $this->validPathTypes);
    }

    /**
     * Checks if $field type is valid for PathSource field
     *
     * @param object $meta
     * @param string $field
     * @return boolean
     */
    protected function isValidFieldForPathSource($meta, $field)
    {
        $mapping = $meta->getFieldMapping($field);
        return $mapping && in_array($mapping['type'], $this->validPathSourceTypes);
    }

    /**
     * Checks if $field type is valid for LockTime field
     *
     * @param object $meta
     * @param string $field
     * @return boolean
     */
    protected function isValidFieldForLockTime($meta, $field)
    {
        $mapping = $meta->getFieldMapping($field);
        return $mapping && $mapping['type'] === 'date';
    }

    /**
     * Validates metadata for nested type tree
     *
     * @param object $meta
     * @param array $config
     * @throws InvalidMappingException
     * @return void
     */
    private function validateNestedTreeMetadata($meta, array $config)
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
            throw new InvalidMappingException("Missing properties: " . implode(', ', $missingFields) . " in class - {$meta->name}");
        }
    }

    /**
     * Validates metadata for closure type tree
     *
     * @param object $meta
     * @param array $config
     * @throws InvalidMappingException
     * @return void
     */
    private function validateClosureTreeMetadata($meta, array $config)
    {
        $missingFields = array();
        if (!isset($config['parent'])) {
            $missingFields[] = 'ancestor';
        }
        if (!isset($config['closure'])) {
            $missingFields[] = 'closure class';
        }
        if ($missingFields) {
            throw new InvalidMappingException("Missing properties: " . implode(', ', $missingFields) . " in class - {$meta->name}");
        }
    }

    /**
     * Validates metadata for materialized path type tree
     *
     * @param object $meta
     * @param array $config
     * @throws InvalidMappingException
     * @return void
     */
    private function validateMaterializedPathTreeMetadata($meta, array $config)
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
            throw new InvalidMappingException("Missing properties: " . implode(', ', $missingFields) . " in class - {$meta->name}");
        }
    }
}
