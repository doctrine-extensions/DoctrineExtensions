<?php

namespace Gedmo\Tree\Mapping\Driver;

use Gedmo\Mapping\Driver\Xml as BaseXml,
    Gedmo\Exception\InvalidMappingException;

/**
 * This is a xml mapping driver for Tree
 * behavioral extension. Used for extraction of extended
 * metadata from xml specificaly for Tree
 * extension.
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Miha Vrhovnik <miha.vrhovnik@gmail.com>
 * @package Gedmo.Tree.Mapping.Driver
 * @subpackage Xml
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Xml extends BaseXml
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
    public function readExtendedMetadata($meta, array &$config) {
        /**
         * @var \SimpleXmlElement $xml
         */
        $xml = $this->_getMapping($meta->name);
        $xmlDoctrine = $xml;
        $xml = $xml->children(self::GEDMO_NAMESPACE_URI);

        if ($xmlDoctrine->getName() == 'entity') {
            if (isset($xml->tree) && $this->_isAttributeSet($xml->tree, 'type')) {
                $strategy = $this->_getAttribute($xml->tree, 'type');
                if (!in_array($strategy, $this->strategies)) {
                    throw new InvalidMappingException("Tree type: $strategy is not available.");
                }
                $config['strategy'] = $strategy;
            }
            if (isset($xml->{'tree-closure'}) && $this->_isAttributeSet($xml->{'tree-closure'}, 'class')) {
                $class = $this->_getAttribute($xml->{'tree-closure'}, 'class');
                if (!class_exists($class)) {
                    throw new InvalidMappingException("Tree closure class: {$class} does not exist.");
                }
                $config['closure'] = $class;
            }
        }
        if (isset($xmlDoctrine->field)) {
            foreach ($xmlDoctrine->field as $mapping) {
                $mappingDoctrine = $mapping;
                $mapping = $mapping->children(self::GEDMO_NAMESPACE_URI);

                $field = $this->_getAttribute($mappingDoctrine, 'name');
                if (isset($mapping->{'tree-left'})) {
                    if (!$this->isValidField($meta, $field)) {
                        throw new InvalidMappingException("Tree left field - [{$field}] type is not valid and must be 'integer' in class - {$meta->name}");
                    }
                    $config['left'] = $field;
                } elseif (isset($mapping->{'tree-right'})) {
                    if (!$this->isValidField($meta, $field)) {
                        throw new InvalidMappingException("Tree right field - [{$field}] type is not valid and must be 'integer' in class - {$meta->name}");
                    }
                    $config['right'] = $field;
                } elseif (isset($mapping->{'tree-root'})) {
                    if (!$meta->getFieldMapping($field)) {
                        throw new InvalidMappingException("Tree root field - [{$field}] type is not valid in class - {$meta->name}");
                    }
                    $config['root'] = $field;
                } elseif (isset($mapping->{'tree-level'})) {
                    if (!$this->isValidField($meta, $field)) {
                        throw new InvalidMappingException("Tree level field - [{$field}] type is not valid and must be 'integer' in class - {$meta->name}");
                    }
                    $config['level'] = $field;
                } elseif (isset($mapping->{'tree-path'})) {
                    if (!$this->isValidFieldForPath($meta, $field)) {
                        throw new InvalidMappingException("Tree Path field - [{$field}] type is not valid. It must be string or text in class - {$meta->name}");
                    }

                    $separator = $this->_getAttribute($mapping->{'tree-path'}, 'separator');

                    if (strlen($separator) > 1) {
                        throw new InvalidMappingException("Tree Path field - [{$field}] Separator {$separator} is invalid. It must be only one character long.");
                    }

                    $config['path'] = $field;
                    $config['path_separator'] = $separator;
                } elseif (isset($mapping->{'tree-path-source'})) {
                    if (!$this->isValidFieldForPathSource($meta, $field)) {
                        throw new InvalidMappingException("Tree PathSource field - [{$field}] type is not valid. It can be any of the integer variants, double, float or string in class - {$meta->name}");
                    }
                    $config['path_source'] = $field;
                }
            }
        }

        if (isset($xmlDoctrine->{'many-to-one'})) {
            foreach ($xmlDoctrine->{'many-to-one'} as $manyToOneMapping)  {
                /**
                 * @var \SimpleXMLElement $manyToOneMapping
                 */
                $manyToOneMappingDoctrine = $manyToOneMapping;
                $manyToOneMapping = $manyToOneMapping->children(self::GEDMO_NAMESPACE_URI);;
                if (isset($manyToOneMapping->{'tree-parent'})) {
                    $field = $this->_getAttribute($manyToOneMappingDoctrine, 'field');
                    if ($meta->associationMappings[$field]['targetEntity'] != $meta->name) {
                        throw new InvalidMappingException("Unable to find ancestor/parent child relation through ancestor field - [{$field}] in class - {$meta->name}");
                    }
                    $config['parent'] = $field;
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
