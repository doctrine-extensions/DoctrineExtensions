<?php

namespace Gedmo\Loggable\Mapping\Driver;

use Gedmo\Mapping\Driver,
    Doctrine\Common\Annotations\AnnotationReader,
    Doctrine\Common\Persistence\Mapping\ClassMetadata,
    Gedmo\Exception\InvalidMappingException;

/**
 * This is an annotation mapping driver for Loggable
 * behavioral extension. Used for extraction of extended
 * metadata from Annotations specificaly for Loggable
 * extension.
 *
 * @author Boussekeyt Jules <jules.boussekeyt@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Loggable.Mapping.Driver
 * @subpackage Annotation
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Annotation implements Driver
{
    /**
     * Annotation to define that this object is loggable
     */
    const ANNOTATION_LOGGABLE = 'Gedmo\Loggable\Mapping\Loggable';

    /**
     * {@inheritDoc}
     */
    public function validateFullMetadata(ClassMetadata $meta, array $config)
    {
        
    }

    /**
     * {@inheritDoc}
     */
    public function readExtendedMetadata(ClassMetadata $meta, array &$config)
    {
        require_once __DIR__ . '/../Annotations.php';
        $reader = new AnnotationReader();
        $reader->setAnnotationNamespaceAlias('Gedmo\Loggable\Mapping\\', 'gedmo');

        $class = $meta->getReflectionClass();
        // class annotations
        $classAnnotations = $reader->getClassAnnotations($class);
        if (isset($classAnnotations[self::ANNOTATION_LOGGABLE])) {
            $config['loggable'] = true;
            $annot = $classAnnotations[self::ANNOTATION_LOGGABLE];
            if ($annot->logEntryClass) {
                if (!class_exists($annot->logEntryClass)) {
                    throw new InvalidMappingException("LogEntry class: {$annot->logEntryClass} does not exist.");
                }
                $config['logEntryClass'] = $annot->logEntryClass;
            }
        }
    }
}