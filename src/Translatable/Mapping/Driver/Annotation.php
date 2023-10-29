<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Translatable\Mapping\Driver;

use Gedmo\Exception\InvalidMappingException;
use Gedmo\Mapping\Annotation\Language;
use Gedmo\Mapping\Annotation\Locale;
use Gedmo\Mapping\Annotation\Translatable;
use Gedmo\Mapping\Annotation\TranslationEntity;
use Gedmo\Mapping\Driver\AbstractAnnotationDriver;

/**
 * This is an annotation mapping driver for Translatable
 * behavioral extension. Used for extraction of extended
 * metadata from Annotations specifically for Translatable
 * extension.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @internal
 */
class Annotation extends AbstractAnnotationDriver
{
    /**
     * Annotation to identity translation entity to be used for translation storage
     */
    public const ENTITY_CLASS = TranslationEntity::class;

    /**
     * Annotation to identify field as translatable
     */
    public const TRANSLATABLE = Translatable::class;

    /**
     * Annotation to identify field which can store used locale or language
     * alias is LANGUAGE
     */
    public const LOCALE = Locale::class;

    /**
     * Annotation to identify field which can store used locale or language
     * alias is LOCALE
     */
    public const LANGUAGE = Language::class;

    public function readExtendedMetadata($meta, array &$config)
    {
        $class = $this->getMetaReflectionClass($meta);
        // class annotations
        if ($annot = $this->reader->getClassAnnotation($class, self::ENTITY_CLASS)) {
            if (!$cl = $this->getRelatedClassName($meta, $annot->class)) {
                throw new InvalidMappingException("Translation class: {$annot->class} does not exist.");
            }
            $config['translationClass'] = $cl;
        }

        // property annotations
        foreach ($class->getProperties() as $property) {
            if ($meta->isMappedSuperclass && !$property->isPrivate()
                || $meta->isInheritedField($property->name)
                || isset($meta->associationMappings[$property->name]['inherited'])
            ) {
                continue;
            }
            // translatable property
            if ($translatable = $this->reader->getPropertyAnnotation($property, self::TRANSLATABLE)) {
                $field = $property->getName();
                if (!$meta->hasField($field)) {
                    throw new InvalidMappingException("Unable to find translatable [{$field}] as mapped property in entity - {$meta->getName()}");
                }
                // fields cannot be overrided and throws mapping exception
                $config['fields'][] = $field;
                if (isset($translatable->fallback)) {
                    $config['fallback'][$field] = $translatable->fallback;
                }
            }
            // locale property
            if ($this->reader->getPropertyAnnotation($property, self::LOCALE)) {
                $field = $property->getName();
                if ($meta->hasField($field)) {
                    throw new InvalidMappingException("Locale field [{$field}] should not be mapped as column property in entity - {$meta->getName()}, since it makes no sense");
                }
                $config['locale'] = $field;
            } elseif ($this->reader->getPropertyAnnotation($property, self::LANGUAGE)) {
                $field = $property->getName();
                if ($meta->hasField($field)) {
                    throw new InvalidMappingException("Language field [{$field}] should not be mapped as column property in entity - {$meta->getName()}, since it makes no sense");
                }
                $config['locale'] = $field;
            }
        }

        // Embedded entity
        if (property_exists($meta, 'embeddedClasses') && $meta->embeddedClasses) {
            foreach ($meta->embeddedClasses as $propertyName => $embeddedClassInfo) {
                if ($meta->isInheritedEmbeddedClass($propertyName)) {
                    continue;
                }
                $embeddedClass = new \ReflectionClass($embeddedClassInfo['class']);
                foreach ($embeddedClass->getProperties() as $embeddedProperty) {
                    if ($translatable = $this->reader->getPropertyAnnotation($embeddedProperty, self::TRANSLATABLE)) {
                        $field = $propertyName.'.'.$embeddedProperty->getName();

                        $config['fields'][] = $field;
                        if (isset($translatable->fallback)) {
                            $config['fallback'][$field] = $translatable->fallback;
                        }
                    }
                }
            }
        }

        if (!$meta->isMappedSuperclass && $config) {
            if (is_array($meta->getIdentifier()) && count($meta->getIdentifier()) > 1) {
                throw new InvalidMappingException("Translatable does not support composite identifiers in class - {$meta->getName()}");
            }
        }

        return $config;
    }
}
