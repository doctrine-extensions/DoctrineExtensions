<?php

namespace Gedmo\Translatable\Mapping\Driver;

use Gedmo\Mapping\Driver,
    Doctrine\Common\Annotations\AnnotationReader,
    Doctrine\ORM\Mapping\ClassMetadataInfo,
    Gedmo\Translatable\Mapping\MappingException;

/**
 * This is an annotation mapping driver for Translatable
 * behavioral extension. Used for extraction of extended
 * metadata from Annotations specificaly for Translatable
 * extension.
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Translatable.Mapping.Driver
 * @subpackage Annotation
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Annotation implements Driver
{
    /**
     * Annotation to identity translation entity to be used for translation storage
     */
    const ANNOTATION_ENTITY_CLASS = 'Gedmo\Translatable\Mapping\TranslationEntity';
    
    /**
     * Annotation to identify field as translatable 
     */
    const ANNOTATION_TRANSLATABLE = 'Gedmo\Translatable\Mapping\Translatable';
    
    /**
     * Annotation to identify field which can store used locale or language
     * alias is ANNOTATION_LANGUAGE
     */
    const ANNOTATION_LOCALE = 'Gedmo\Translatable\Mapping\Locale';
    
    /**
     * Annotation to identify field which can store used locale or language
     * alias is ANNOTATION_LOCALE
     */
    const ANNOTATION_LANGUAGE = 'Gedmo\Translatable\Mapping\Language';
    
    /**
     * List of types which are valid for translation,
     * this property is public and you can add some
     * other types in case it needs translation
     * 
     * @var array
     */
    public $validTypes = array(
        'string',
        'text'
    );
    
    /**
     * (non-PHPdoc)
     * @see Gedmo\Mapping.Driver::validateFullMetadata()
     */
    public function validateFullMetadata(ClassMetadataInfo $meta, array $config)
    {

    }
    
    /**
     * (non-PHPdoc)
     * @see Gedmo\Mapping.Driver::readExtendedMetadata()
     */
    public function readExtendedMetadata(ClassMetadataInfo $meta, array &$config) {
        require_once __DIR__ . '/../Annotations.php';
        $reader = new AnnotationReader();
        $reader->setAnnotationNamespaceAlias('Gedmo\Translatable\Mapping\\', 'gedmo');
        
        $class = $meta->getReflectionClass();
        // class annotations
        $classAnnotations = $reader->getClassAnnotations($class);
        if (isset($classAnnotations[self::ANNOTATION_ENTITY_CLASS])) {
            $annot = $classAnnotations[self::ANNOTATION_ENTITY_CLASS];
            if (!class_exists($annot->class)) {
                throw MappingException::translationClassNotFound($annot->class);
            }
            $config['translationClass'] = $annot->class;
        }
        
        // property annotations
        foreach ($class->getProperties() as $property) {
            if ($meta->isMappedSuperclass && !$property->isPrivate() ||
                $meta->isInheritedField($property->name) ||
                $meta->isInheritedAssociation($property->name)
            ) {
                continue;
            }
            // translatable property
            if ($translatable = $reader->getPropertyAnnotation($property, self::ANNOTATION_TRANSLATABLE)) {
                $field = $property->getName();
                if (!$meta->hasField($field)) {
                    throw MappingException::fieldMustBeMapped($field, $meta->name);
                }
                if (!$this->_isValidField($meta, $field)) {
                    throw MappingException::notValidFieldType($field, $meta->name);
                }
                // fields cannot be overrided and throws mapping exception
                $config['fields'][] = $field;
            }
            // locale property
            if ($locale = $reader->getPropertyAnnotation($property, self::ANNOTATION_LOCALE)) {
                $field = $property->getName();
                if ($meta->hasField($field)) {
                    throw MappingException::fieldMustNotBeMapped($field, $meta->name);
                }
                $config['locale'] = $field;
            } elseif ($language = $reader->getPropertyAnnotation($property, self::ANNOTATION_LANGUAGE)) {
                $field = $property->getName();
                if ($meta->hasField($field)) {
                    throw MappingException::fieldMustNotBeMapped($field, $meta->name);
                }
                $config['locale'] = $field;
            }
        }
    }
    
    /**
     * Checks if $field type is valid as Translatable field
     * 
     * @param ClassMetadataInfo $meta
     * @param string $field
     * @return boolean
     */
    protected function _isValidField(ClassMetadataInfo $meta, $field)
    {
        return in_array($meta->getTypeOfField($field), $this->validTypes);
    }
}