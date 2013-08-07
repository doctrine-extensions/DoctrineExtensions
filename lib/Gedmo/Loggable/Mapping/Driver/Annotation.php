<?php

namespace Gedmo\Loggable\Mapping\Driver;

use Gedmo\Mapping\Driver\AnnotationDriver;
use Gedmo\Mapping\ExtensionMetadataInterface;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Gedmo\Exception\InvalidMappingException;

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
class Annotation extends AnnotationDriver
{
    /**
     * Annotation to define that this object is loggable
     */
    const LOGGABLE = 'Gedmo\Mapping\Annotation\Loggable';

    /**
     * Annotation to define that this property is versioned
     */
    const VERSIONED = 'Gedmo\Mapping\Annotation\Versioned';

    /**
     * {@inheritDoc}
     */
    public function loadExtensionMetadata(ClassMetadata $meta, ExtensionMetadataInterface $exm)
    {
        $class = $meta->reflClass;
        // class annotations
        if ($annot = $this->reader->getClassAnnotation($class, self::LOGGABLE)) {
            if ($annot->logEntryClass) {
                if (!class_exists($name = $annot->logEntryClass)) {
                    if (!class_exists($name = $class->getNamespaceName().'\\'.$name)) {
                        throw new InvalidMappingException("LogEntry class: {$annot->logEntryClass} does not exist. Or could not be autoloaded.");
                    }
                }
                $exm->setLogClass($name);
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
                $exm->map($property->getName());
            }
        }
    }
}
