<?php

namespace Gedmo\Translatable\Mapping\Driver;

use Gedmo\Mapping\Driver\AbstractAnnotationDriver,
    Gedmo\Exception\InvalidMappingException;

/**
 * This is an annotation mapping driver for Translatable
 * behavioral extension. Used for extraction of extended
 * metadata from Annotations specificaly for Translatable
 * extension.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Annotation extends AbstractAnnotationDriver
{
    /**
     * Annotation to identity translation entity to be used for translation storage
     */
    const ENTITY_CLASS = 'Gedmo\\Mapping\\Annotation\\TranslationEntity';

    /**
     * Annotation to identify field as translatable
     */
    const TRANSLATABLE = 'Gedmo\\Mapping\\Annotation\\Translatable';

    /**
     * Annotation to identify field which can store used locale or language
     * alias is LANGUAGE
     */
    const LOCALE = 'Gedmo\\Mapping\\Annotation\\Locale';

    /**
     * Annotation to identify field which can store used locale or language
     * alias is LOCALE
     */
    const LANGUAGE = 'Gedmo\\Mapping\\Annotation\\Language';

    /**
     * {@inheritDoc}
     */
    public function readExtendedMetadata($meta, array &$config)
    {
        $class = $this->getMetaReflectionClass($meta);
        // class annotations
        if ($annot = $this->reader->getClassAnnotation($class, self::ENTITY_CLASS)) {
            if (!class_exists($annot->class)) {
                throw new InvalidMappingException("Translation class: {$annot->class} does not exist.");
            }
            $config['translationClass'] = $annot->class;
        }

        // property annotations
        foreach ($class->getProperties() as $property) {
            if ($meta->isMappedSuperclass && !$property->isPrivate() ||
                $meta->isInheritedField($property->name) ||
                isset($meta->associationMappings[$property->name]['inherited'])
            ) {
                continue;
            }
            // translatable property
            if ($translatable = $this->reader->getPropertyAnnotation($property, self::TRANSLATABLE)) {
                $field = $property->getName();
                if (!$meta->hasField($field)) {
                    throw new InvalidMappingException("Unable to find translatable [{$field}] as mapped property in entity - {$meta->name}");
                }
                // fields cannot be overrided and throws mapping exception
                $config['fields'][] = $field;
                if (isset($translatable->fallback)) {
                    $config['fallback'][$field] = $translatable->fallback;
                }
            }
            // locale property
            if ($locale = $this->reader->getPropertyAnnotation($property, self::LOCALE)) {
                $field = $property->getName();
                if ($meta->hasField($field)) {
                    throw new InvalidMappingException("Locale field [{$field}] should not be mapped as column property in entity - {$meta->name}, since it makes no sence");
                }
                $config['locale'] = $field;
            } elseif ($language = $this->reader->getPropertyAnnotation($property, self::LANGUAGE)) {
                $field = $property->getName();
                if ($meta->hasField($field)) {
                    throw new InvalidMappingException("Language field [{$field}] should not be mapped as column property in entity - {$meta->name}, since it makes no sence");
                }
                $config['locale'] = $field;
            }
        }

        if (!$meta->isMappedSuperclass && $config) {
            if (is_array($meta->identifier) && count($meta->identifier) > 1) {
                throw new InvalidMappingException("Translatable does not support composite identifiers in class - {$meta->name}");
            }
        }
    }
}
