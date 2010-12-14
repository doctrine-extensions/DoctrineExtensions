<?php

namespace Gedmo\Sluggable\Mapping\Driver;

use Gedmo\Mapping\Driver,
    Doctrine\Common\Annotations\AnnotationReader,
    Doctrine\ORM\Mapping\ClassMetadataInfo,
    Gedmo\Sluggable\Mapping\MappingException;

/**
 * This is an annotation mapping driver for Sluggable
 * behavioral extension. Used for extraction of extended
 * metadata from Annotations specificaly for Sluggable
 * extension.
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Sluggable.Mapping.Driver
 * @subpackage Annotation
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Annotation implements Driver
{
    /**
     * Annotation to mark field as sluggable and include it in slug building
     */
    const ANNOTATION_SLUGGABLE = 'Gedmo\Sluggable\Mapping\Sluggable';
    
    /**
     * Annotation to identify field as one which holds the slug
     * together with slug options
     */
    const ANNOTATION_SLUG = 'Gedmo\Sluggable\Mapping\Slug';
    
    /**
     * List of types which are valid for slug and sluggable fields
     * 
     * @var array
     */
    private $_validTypes = array(
        'string'
    );
    
    /**
     * (non-PHPdoc)
     * @see Gedmo\Mapping.Driver::validateFullMetadata()
     */
    public function validateFullMetadata(ClassMetadataInfo $meta, array $config)
    {
        if ($config && !isset($config['fields'])) {
            throw MappingException::noFieldsToSlug($meta->name);
        }
    }
    
    /**
     * (non-PHPdoc)
     * @see Gedmo\Mapping.Driver::readExtendedMetadata()
     */
    public function readExtendedMetadata(ClassMetadataInfo $meta, array &$config) {
        require_once __DIR__ . '/../Annotations.php';
        $reader = new AnnotationReader();
        $reader->setAnnotationNamespaceAlias('Gedmo\Sluggable\Mapping\\', 'gedmo');
        
        $class = $meta->getReflectionClass();        
        // property annotations
        foreach ($class->getProperties() as $property) {
            if ($meta->isMappedSuperclass && !$property->isPrivate() ||
                $meta->isInheritedField($property->name) ||
                $meta->isInheritedAssociation($property->name)
            ) {
                continue;
            }
            // sluggable property
            if ($sluggable = $reader->getPropertyAnnotation($property, self::ANNOTATION_SLUGGABLE)) {
                $field = $property->getName();
                if (!$meta->hasField($field)) {
                    throw MappingException::fieldMustBeMapped($field, $meta->name);
                }
                if (!$this->_isValidField($meta, $field)) {
                    throw MappingException::notValidFieldType($field, $meta->name);
                }
                $config['fields'][] = $field;
            }
            // slug property
            if ($slug = $reader->getPropertyAnnotation($property, self::ANNOTATION_SLUG)) {
                $field = $property->getName();
                if (!$meta->hasField($field)) {
                    throw MappingException::slugFieldMustBeMapped($field, $meta->name);
                }
                if (!$this->_isValidField($meta, $field)) {
                    throw MappingException::notValidFieldType($field, $meta->name);
                } 
                if (isset($config['slug'])) {
                    throw MappingException::slugFieldIsDuplicate($field, $meta->name);
                }
                
                $config['slug'] = $field;
                $config['style'] = $slug->style;
                $config['updatable'] = $slug->updatable;
                $config['unique'] = $slug->unique;
                $config['separator'] = $slug->separator;
            }
        }
    }
    
    /**
     * Checks if $field type is valid as Sluggable field
     * 
     * @param ClassMetadataInfo $meta
     * @param string $field
     * @return boolean
     */
    protected function _isValidField(ClassMetadataInfo $meta, $field)
    {
        return in_array($meta->getTypeOfField($field), $this->_validTypes);
    }
}