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

        if (!$meta->isMappedSuperclass && $config && !isset($config['translationClass'])) {
            // ensure identifier is singular
            if (is_array($meta->identifier) && count($meta->identifier) > 1) {
                throw new InvalidMappingException("Translatable does not support composite identifiers in class - {$meta->name}");
            }
            // try to guess translation class
            $config['translationClass'] = $meta->name . 'Translation';
            if (!class_exists($config['translationClass'])) {
                throw new InvalidMappingException("Translation class {$config['translationClass']} for domain object {$meta->name}"
                    . ", was not found or could not be autoloaded. If it was not generated yet, use GenerateTranslationsCommand", $config);
            }
        }
    }
}
