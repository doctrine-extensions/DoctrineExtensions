<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Loggable\Mapping\Driver;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata as ClassMetadataODM;
use Doctrine\ORM\Mapping\ClassMetadata as ClassMetadataORM;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Gedmo\Exception\InvalidMappingException;
use Gedmo\Mapping\Annotation\Loggable;
use Gedmo\Mapping\Annotation\Versioned;
use Gedmo\Mapping\Driver\AbstractAnnotationDriver;

/**
 * Mapping driver for the loggable extension which reads extended metadata from attributes on a loggable class.
 *
 * @author Boussekeyt Jules <jules.boussekeyt@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @internal
 */
class Attribute extends AbstractAnnotationDriver
{
    /**
     * Mapping object defining a loggable class.
     */
    public const LOGGABLE = Loggable::class;

    /**
     * Mapping object defining a versioned property from a loggable class.
     */
    public const VERSIONED = Versioned::class;

    public function validateFullMetadata(ClassMetadata $meta, array $config)
    {
        if ($config && $meta instanceof ClassMetadataODM && count($meta->getIdentifier()) > 1) {
            throw new InvalidMappingException("Loggable does not support composite identifiers in class - {$meta->getName()}");
        }

        if (isset($config['versioned']) && !isset($config['loggable'])) {
            throw new InvalidMappingException("Class must be annotated with Loggable annotation in order to track versioned fields in class - {$meta->getName()}");
        }
    }

    public function readExtendedMetadata($meta, array &$config)
    {
        $class = $this->getMetaReflectionClass($meta);

        // class annotations
        if ($annot = $this->reader->getClassAnnotation($class, self::LOGGABLE)) {
            \assert($annot instanceof Loggable);

            $config['loggable'] = true;

            if ($annot->logEntryClass) {
                if (!$cl = $this->getRelatedClassName($meta, $annot->logEntryClass)) {
                    throw new InvalidMappingException("LogEntry class: {$annot->logEntryClass} does not exist.");
                }

                $config['logEntryClass'] = $cl;
            }
        }

        // property annotations
        foreach ($class->getProperties() as $property) {
            $field = $property->getName();

            if ($meta->isMappedSuperclass && !$property->isPrivate()) {
                continue;
            }

            // versioned property
            if ($this->reader->getPropertyAnnotation($property, self::VERSIONED)) {
                if (!$this->isMappingValid($meta, $field)) {
                    throw new InvalidMappingException("Cannot apply versioning to field [{$field}] as it is collection in object - {$meta->getName()}");
                }

                if (isset($meta->embeddedClasses[$field])) {
                    $this->inspectEmbeddedForVersioned($field, $config, $meta);

                    continue;
                }

                // fields cannot be overridden and throws mapping exception
                if (!in_array($field, $config['versioned'] ?? [], true)) {
                    $config['versioned'][] = $field;
                }
            }
        }

        if (!$meta->isMappedSuperclass && $config) {
            if ($meta instanceof ClassMetadataODM && count($meta->getIdentifier()) > 1) {
                throw new InvalidMappingException("Loggable does not support composite identifiers in class - {$meta->getName()}");
            }

            if ($this->isClassAnnotationInValid($meta, $config)) {
                throw new InvalidMappingException("Class must be annotated with Loggable annotation in order to track versioned fields in class - {$meta->getName()}");
            }
        }

        return $config;
    }

    /**
     * @param ClassMetadata<object> $meta
     * @param string                $field
     *
     * @return bool
     */
    protected function isMappingValid(ClassMetadata $meta, $field)
    {
        return false == $meta->isCollectionValuedAssociation($field);
    }

    /**
     * @param ClassMetadata<object> $meta
     * @param array<string, mixed>  $config
     *
     * @return bool
     */
    protected function isClassAnnotationInValid(ClassMetadata $meta, array &$config)
    {
        return isset($config['versioned']) && !isset($config['loggable']) && (!isset($meta->isEmbeddedClass) || !$meta->isEmbeddedClass);
    }

    /**
     * Searches properties of embedded objects for versioned fields
     *
     * @param array<string, mixed>     $config
     * @param ClassMetadataORM<object> $meta
     */
    private function inspectEmbeddedForVersioned(string $field, array &$config, ClassMetadataORM $meta): void
    {
        $class = new \ReflectionClass($meta->embeddedClasses[$field]['class']);

        // property annotations
        foreach ($class->getProperties() as $property) {
            // versioned property
            if ($this->reader->getPropertyAnnotation($property, self::VERSIONED)) {
                $embeddedField = $field.'.'.$property->getName();
                $config['versioned'][] = $embeddedField;

                if (isset($meta->embeddedClasses[$embeddedField])) {
                    $this->inspectEmbeddedForVersioned($embeddedField, $config, $meta);
                }
            }
        }
    }
}
