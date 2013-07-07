<?php

namespace Gedmo\Translatable\Mapping\Driver;

use Gedmo\Mapping\Driver\AbstractAnnotationDriver;
use Gedmo\Exception\InvalidMappingException;

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
    const TRANSLATION_CLASS = 'Gedmo\\Mapping\\Annotation\\TranslationClass';

    /**
     * Annotation to identify field as translatable
     */
    const TRANSLATABLE = 'Gedmo\\Mapping\\Annotation\\Translatable';

    /**
     * {@inheritDoc}
     */
    public function readExtendedMetadata($meta, array &$config)
    {
        $class = $this->getMetaReflectionClass($meta);
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
                // fields cannot be overriden and throws mapping exception
                $config['fields'][$field] = array(); // can be some options in future
            }
        }

        // class annotations
        if ($annot = $this->reader->getClassAnnotation($class, self::TRANSLATION_CLASS)) {
            if (!class_exists($name = $annot->name)) {
                if (!class_exists($name = $class->getNamespaceName().'\\'.$name)) {
                    $config['translationClass'] = $annot->name;
                    throw new InvalidMappingException("Translation class: {$annot->name} does not exist."
                        . " If you haven't generated it yet, use TranslatableCommand to do so", $config);
                }
            }
            $config['translationClass'] = $name;
        }

        if (!$meta->isMappedSuperclass && $config && !isset($config['translationClass'])) {
            // try to guess translation class
            ($parts = explode('\\', $meta->name)) && ($name = array_pop($parts));
            if (class_exists($fullname = implode('\\', $parts).'\\'.$name.'Translation')) {
                $config['translationClass'] = $fullname;
            } elseif (class_exists($fullname2 = implode('\\', $parts).'\\Translation\\'.$name)) {
                $config['translationClass'] = $fullname2;
            } else {
                throw new InvalidMappingException("Tried to guess translation class as {$fullname} or {$fullname2}"
                    . ", but could not locate it. If you haven't generated it yet, use TranslatableCommand to do so"
                    . ", if it is available elsewhere, specify it in configuration with 'translationClass'", $config);
            }
        }
    }
}
