<?php

namespace Gedmo\Tree\Mapping\Driver;

use Gedmo\Mapping\Driver\File,
    Gedmo\Mapping\Driver,
    Doctrine\Common\Persistence\Mapping\ClassMetadata,
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
     * List of types which are valid for timestamp
     *
     * @var array
     */
    private $validTypes = array(
        'integer',
        'smallint',
        'bigint'
    );

    /**
     * List of tree strategies available
     *
     * @var array
     */
    private $strategies = array(
        'nested',
        'closure'
    );

    /**
     * {@inheritDoc}
     */
    public function validateFullMetadata(ClassMetadata $meta, array $config)
    {
        if (isset($config['strategy'])) {
            if (is_array($meta->identifier) && count($meta->identifier) > 1) {
                throw new InvalidMappingException("Tree does not support composite identifiers in class - {$meta->name}");
            }
            $method = 'validate' . ucfirst($config['strategy']) . 'TreeMetadata';
            $this->$method($meta, $config);
        } elseif ($config) {
            throw new InvalidMappingException("Cannot find Tree type for class: {$meta->name}");
        }
    }

    /**
     * {@inheritDoc}
     */
    public function readExtendedMetadata(ClassMetadata $meta, array &$config)
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
                        if (!$this->isValidField($meta, $field)) {
                            throw new InvalidMappingException("Tree root field - [{$field}] type is not valid and must be 'integer' in class - {$meta->name}");
                        }
                        $config['root'] = $field;
                    }
                }
            }
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
     * @param ClassMetadata $meta
     * @param string $field
     * @return boolean
     */
    protected function isValidField(ClassMetadata $meta, $field)
    {
        $mapping = $meta->getFieldMapping($field);
        return $mapping && in_array($mapping['type'], $this->validTypes);
    }

    /**
     * Validates metadata for nested type tree
     *
     * @param ClassMetadata $meta
     * @param array $config
     * @throws InvalidMappingException
     * @return void
     */
    private function validateNestedTreeMetadata(ClassMetadata $meta, array $config)
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
     * @param ClassMetadata $meta
     * @param array $config
     * @throws InvalidMappingException
     * @return void
     */
    private function validateClosureTreeMetadata(ClassMetadata $meta, array $config)
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
}
