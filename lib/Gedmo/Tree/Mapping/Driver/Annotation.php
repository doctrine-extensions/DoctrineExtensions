<?php

namespace Gedmo\Tree\Mapping\Driver;

use Gedmo\Mapping\Driver,
    Doctrine\Common\Annotations\AnnotationReader,
    Doctrine\Common\Persistence\Mapping\ClassMetadata,
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
class Annotation implements Driver
{
    /**
     * Annotation to define the tree type
     */
    const ANNOTATION_TREE = 'Gedmo\Tree\Mapping\Tree';
    
    /**
     * Annotation to mark field as one which will store left value
     */
    const ANNOTATION_LEFT = 'Gedmo\Tree\Mapping\TreeLeft';
    
    /**
     * Annotation to mark field as one which will store right value
     */
    const ANNOTATION_RIGHT = 'Gedmo\Tree\Mapping\TreeRight';
    
    /**
     * Annotation to mark relative parent field
     */
    const ANNOTATION_PARENT = 'Gedmo\Tree\Mapping\TreeParent';
    
    /**
     * Annotation to mark node level
     */
    const ANNOTATION_LEVEL = 'Gedmo\Tree\Mapping\TreeLevel';
    
    /**
     * Annotation to mark field as tree root
     */
    const ANNOTATION_ROOT = 'Gedmo\Tree\Mapping\TreeRoot';
    
    /**
     * Annotation to specify closure tree class
     */
    const ANNOTATION_CLOSURE = 'Gedmo\Tree\Mapping\TreeClosure';
    
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
     * {@inheritDoc}
     */
    public function validateFullMetadata(ClassMetadata $meta, array $config)
    {        
        if (isset($config['strategy'])) {
            $method = 'validate' . ucfirst($config['strategy']) . 'TreeMetadata';
            $this->$method($meta, $config);
        } elseif ($config) {
            throw new InvalidMappingException("Cannot find Tree type for class: {$meta->name}");
        }
    }
    
    /**
     * {@inheritDoc}
     */
    public function readExtendedMetadata(ClassMetadata $meta, array &$config) {
        require_once __DIR__ . '/../Annotations.php';
        $reader = new AnnotationReader();
        $reader->setAnnotationNamespaceAlias('Gedmo\Tree\Mapping\\', 'gedmo');
        
        $class = $meta->getReflectionClass();
        // class annotations
        $classAnnotations = $reader->getClassAnnotations($class);
        if (isset($classAnnotations[self::ANNOTATION_TREE])) {
            $annot = $classAnnotations[self::ANNOTATION_TREE];
            if (!in_array($annot->type, $this->strategies)) {
                throw new InvalidMappingException("Tree type: {$annot->type} is not available.");
            }
            $config['strategy'] = $annot->type;
        }
        if (isset($classAnnotations[self::ANNOTATION_CLOSURE])) {
            $annot = $classAnnotations[self::ANNOTATION_CLOSURE];
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
            if ($left = $reader->getPropertyAnnotation($property, self::ANNOTATION_LEFT)) {
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
            if ($right = $reader->getPropertyAnnotation($property, self::ANNOTATION_RIGHT)) {
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
            if ($parent = $reader->getPropertyAnnotation($property, self::ANNOTATION_PARENT)) {
                $field = $property->getName();
                if (!$meta->isSingleValuedAssociation($field)) {
                    throw new InvalidMappingException("Unable to find ancestor/parent child relation through ancestor field - [{$field}] in class - {$meta->name}");
                }
                $config['parent'] = $field;
            }
            // root
            if ($root = $reader->getPropertyAnnotation($property, self::ANNOTATION_ROOT)) {
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
            if ($parent = $reader->getPropertyAnnotation($property, self::ANNOTATION_LEVEL)) {
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
        if (is_array($meta->identifier) && count($meta->identifier) > 1) {
            throw new InvalidMappingException("Tree does not support composite indentifiers in class - {$meta->name}");
        }
        
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