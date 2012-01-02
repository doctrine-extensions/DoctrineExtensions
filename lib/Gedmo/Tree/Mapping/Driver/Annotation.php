<?php

namespace Gedmo\Tree\Mapping\Driver;

use Gedmo\Mapping\Driver\AnnotationDriverInterface,
    Gedmo\Exception\InvalidMappingException;

/**
 * This is an annotation mapping driver for Tree
 * behavioral extension. Used for extraction of extended
 * metadata from Annotations specificaly for Tree
 * extension.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Tree.Mapping.Driver
 * @subpackage Annotation
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Annotation implements AnnotationDriverInterface
{
    /**
     * Annotation to define the tree type
     */
    const TREE = 'Gedmo\\Mapping\\Annotation\\Tree';

    /**
     * Annotation to mark field as one which will store left value
     */
    const LEFT = 'Gedmo\\Mapping\\Annotation\\TreeLeft';

    /**
     * Annotation to mark field as one which will store right value
     */
    const RIGHT = 'Gedmo\\Mapping\\Annotation\\TreeRight';

    /**
     * Annotation to mark relative parent field
     */
    const PARENT = 'Gedmo\\Mapping\\Annotation\\TreeParent';

    /**
     * Annotation to mark node level
     */
    const LEVEL = 'Gedmo\\Mapping\\Annotation\\TreeLevel';

    /**
     * Annotation to mark field as tree root
     */
    const ROOT = 'Gedmo\\Mapping\\Annotation\\TreeRoot';

    /**
     * Annotation to specify closure tree class
     */
    const CLOSURE = 'Gedmo\\Mapping\\Annotation\\TreeClosure';

    /**
     * List of types which are valid for tree fields
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
     * Annotation reader instance
     *
     * @var object
     */
    private $reader;

    /**
     * original driver if it is available
     */
    protected $_originalDriver = null;

    /**
     * {@inheritDoc}
     */
    public function setAnnotationReader($reader)
    {
        $this->reader = $reader;
    }

    /**
     * {@inheritDoc}
     */
    public function readExtendedMetadata($meta, array &$config) {
        $class = $meta->getReflectionClass();
        // class annotations
        if ($annot = $this->reader->getClassAnnotation($class, self::TREE)) {
            if (!in_array($annot->type, $this->strategies)) {
                throw new InvalidMappingException("Tree type: {$annot->type} is not available.");
            }
            $config['strategy'] = $annot->type;
        }
        if ($annot = $this->reader->getClassAnnotation($class, self::CLOSURE)) {
            if (!class_exists($annot->class)) {
                throw new InvalidMappingException("Tree closure class: {$annot->class} does not exist.");
            }
            $config['closure'] = $annot->class;
        }

        // property annotations
        foreach ($class->getProperties() as $property) {
            if ($meta->isMappedSuperclass && !$property->isPrivate() ||
                $meta->isInheritedField($property->name) ||
                isset($meta->associationMappings[$property->name]['inherited'])
            ) {
                continue;
            }
            // left
            if ($left = $this->reader->getPropertyAnnotation($property, self::LEFT)) {
                $field = $property->getName();
                if (!$meta->hasField($field)) {
                    throw new InvalidMappingException("Unable to find 'left' - [{$field}] as mapped property in entity - {$meta->name}");
                }
                if (!$this->isValidField($meta, $field)) {
                    throw new InvalidMappingException("Tree left field - [{$field}] type is not valid and must be 'integer' in class - {$meta->name}");
                }
                $config['left'] = $field;
            }
            // right
            if ($right = $this->reader->getPropertyAnnotation($property, self::RIGHT)) {
                $field = $property->getName();
                if (!$meta->hasField($field)) {
                    throw new InvalidMappingException("Unable to find 'right' - [{$field}] as mapped property in entity - {$meta->name}");
                }
                if (!$this->isValidField($meta, $field)) {
                    throw new InvalidMappingException("Tree right field - [{$field}] type is not valid and must be 'integer' in class - {$meta->name}");
                }
                $config['right'] = $field;
            }
            // ancestor/parent
            if ($parent = $this->reader->getPropertyAnnotation($property, self::PARENT)) {
                $field = $property->getName();
                if (!$meta->isSingleValuedAssociation($field)) {
                    throw new InvalidMappingException("Unable to find ancestor/parent child relation through ancestor field - [{$field}] in class - {$meta->name}");
                }
                $config['parent'] = $field;
            }
            // root
            if ($root = $this->reader->getPropertyAnnotation($property, self::ROOT)) {
                $field = $property->getName();
                if (!$meta->hasField($field)) {
                    throw new InvalidMappingException("Unable to find 'root' - [{$field}] as mapped property in entity - {$meta->name}");
                }
                if (!$this->isValidField($meta, $field)) {
                    throw new InvalidMappingException("Tree root field - [{$field}] type is not valid and must be 'integer' in class - {$meta->name}");
                }
                $config['root'] = $field;
            }
            // level
            if ($parent = $this->reader->getPropertyAnnotation($property, self::LEVEL)) {
                $field = $property->getName();
                if (!$meta->hasField($field)) {
                    throw new InvalidMappingException("Unable to find 'level' - [{$field}] as mapped property in entity - {$meta->name}");
                }
                if (!$this->isValidField($meta, $field)) {
                    throw new InvalidMappingException("Tree level field - [{$field}] type is not valid and must be 'integer' in class - {$meta->name}");
                }
                $config['level'] = $field;
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
     * Passes in the mapping read by original driver
     *
     * @param $driver
     * @return void
     */
    public function setOriginalDriver($driver)
    {
        $this->_originalDriver = $driver;
    }
}