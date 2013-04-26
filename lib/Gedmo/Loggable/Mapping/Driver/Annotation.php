<?php

namespace Gedmo\Loggable\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\ClassMetadata,
    Gedmo\Mapping\Driver\AbstractAnnotationDriver,
    Gedmo\Exception\InvalidMappingException;

/**
 * This is an annotation mapping driver for Loggable
 * behavioral extension. Used for extraction of extended
 * metadata from Annotations specificaly for Loggable
 * extension.
 *
 * @author Boussekeyt Jules <jules.boussekeyt@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Annotation extends AbstractAnnotationDriver
{
    /**
     * Annotation to define that this object is loggable
     */
    const LOGGABLE = 'Gedmo\\Mapping\\Annotation\\Loggable';

    /**
     * Annotation to define that this property is versioned
     */
    const VERSIONED = 'Gedmo\\Mapping\\Annotation\\Versioned';

    /**
     * {@inheritDoc}
     */
    public function validateFullMetadata(ClassMetadata $meta, array $config)
    {
        if ($config && is_array($meta->identifier) && count($meta->identifier) > 1) {
            throw new InvalidMappingException("Loggable does not support composite identifiers in class - {$meta->name}");
        }
        if (isset($config['versioned']) && !isset($config['loggable'])) {
            throw new InvalidMappingException("Class must be annoted with Loggable annotation in order to track versioned fields in class - {$meta->name}");
        }
    }

    /**
     * {@inheritDoc}
     */
    public function readExtendedMetadata($meta, array &$config)
    {
        $class = $this->getMetaReflectionClass($meta);
        // class annotations
        if ($annot = $this->reader->getClassAnnotation($class, self::LOGGABLE)) {
            $config['loggable'] = true;
            if ($annot->logEntryClass) {
                if (!class_exists($annot->logEntryClass)) {
                    throw new InvalidMappingException("LogEntry class: {$annot->logEntryClass} does not exist.");
                }
                $config['logEntryClass'] = $annot->logEntryClass;
            }
        }
        // property annotations
        foreach ($class->getProperties() as $property) {
            if ($meta->isMappedSuperclass && !$property->isPrivate() ||
                $meta->isInheritedField($property->name) ||
                isset($meta->associationMappings[$property->name]['inherited'])
            ) {
                continue;
            }
            // versioned property
            if ($versioned = $this->reader->getPropertyAnnotation($property, self::VERSIONED)) {
                $field = $property->getName();
                if ($meta->isCollectionValuedAssociation($field)) {
                    throw new InvalidMappingException("Cannot versioned [{$field}] as it is collection in object - {$meta->name}");
                }
                // fields cannot be overrided and throws mapping exception
                $config['versioned'][] = $field;
            }
        }

        if (!$meta->isMappedSuperclass && $config) {
            if (is_array($meta->identifier) && count($meta->identifier) > 1) {
                throw new InvalidMappingException("Loggable does not support composite identifiers in class - {$meta->name}");
            }
            if (isset($config['versioned']) && !isset($config['loggable'])) {
                throw new InvalidMappingException("Class must be annoted with Loggable annotation in order to track versioned fields in class - {$meta->name}");
            }
        }
    }
}
