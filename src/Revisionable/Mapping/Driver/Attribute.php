<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Revisionable\Mapping\Driver;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata as MongoDBDOMClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadata as ORMClassMetadata;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Gedmo\Exception\InvalidMappingException;
use Gedmo\Mapping\Annotation\Revisionable;
use Gedmo\Mapping\Annotation\Versioned;
use Gedmo\Mapping\Driver\AbstractAnnotationDriver;

/**
 * Mapping driver for the revisionable extension which reads extended metadata from attributes on a revisionable class.
 *
 * @author Boussekeyt Jules <jules.boussekeyt@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @internal
 */
class Attribute extends AbstractAnnotationDriver
{
    public function readExtendedMetadata($meta, array &$config)
    {
        // Skip embedded classes for the ORM, they will be handled inline while processing classes using embeds
        if ($meta instanceof ORMClassMetadata && $meta->isEmbeddedClass) {
            return $config;
        }

        $class = $this->getMetaReflectionClass($meta);

        // Determine if the object is revisionable by inspecting the class attributes
        if ($annot = $this->reader->getClassAnnotation($class, Revisionable::class)) {
            \assert($annot instanceof Revisionable);

            $config['revisionable'] = true;

            if ($annot->revisionClass) {
                // Embedded models cannot have a revision class defined, their data is logged to the owning model
                if ($this->isEmbed($meta)) {
                    throw new InvalidMappingException(sprintf("Class '%s' is mapped as an embedded object and cannot specify the revision class property.", $meta->getName()));
                }

                if (!$cl = $this->getRelatedClassName($meta, $annot->revisionClass)) {
                    throw new InvalidMappingException(sprintf("The revision class '%s' configured for '%s' does not exist.", $class, $meta->getName()));
                }

                $config['revisionClass'] = $cl;
            }
        }

        // Inspect properties for versioned fields
        foreach ($class->getProperties() as $property) {
            $field = $property->getName();

            if ($this->reader->getPropertyAnnotation($property, Versioned::class)) {
                if ($meta->isCollectionValuedAssociation($field)) {
                    throw new InvalidMappingException(sprintf('Cannot version field %s::$%s, collection valued associations are not supported.', $meta->getName(), $field));
                }

                // The MongoDB ODM's @EmbedMany is not supported
                if ($meta instanceof MongoDBDOMClassMetadata && $meta->isCollectionValuedEmbed($field)) {
                    throw new InvalidMappingException(sprintf('Cannot version field %s::$%s, an embedded many collection is not supported.', $meta->getName(), $field));
                }

                // To version a field with a relationship, it must be the owning side
                if ($this->isRelationship($meta, $field)) {
                    $associationMapping = $meta->associationMappings[$field];

                    if (!$associationMapping['isOwningSide']) {
                        throw new InvalidMappingException(sprintf('Cannot version field %s::$%s, it is not the owning side of the relationship.', $meta->getName(), $field));
                    }
                }

                /*
                 * Due to differences in the UoW's for each object manager, embedded models need to be handled differently.
                 *
                 * The MongoDB ODM tracks embedded documents within the UoW and the listener can recursively inspect the change set
                 * for changes to these objects.
                 *
                 * The ORM inlines embedded field mappings to the root entity, so the list of versioned fields needs to be added to
                 * the extension metadata now.
                 */

                if ($meta instanceof ORMClassMetadata && isset($meta->embeddedClasses[$field])) {
                    $config = $this->inspectEmbeddedForVersioned($field, $config, $meta);

                    continue;
                }

                $config['versioned'][] = $field;
            }
        }

        // Validate configuration
        if (!$meta->isMappedSuperclass && $config) {
            // The revisionable flag must be set, except for embedded models, and the versioned config should be a non-empty array
            if (isset($config['versioned']) && (!$this->isEmbed($meta) && !isset($config['revisionable']))) {
                throw new InvalidMappingException(sprintf('Class "%s" has "%s" annotated fields but is missing the "%s" class annotation.', $meta->getName(), Versioned::class, Revisionable::class));
            }
        }

        return $config;
    }

    /**
     * Recursively searches properties of an embedded object for versioned fields.
     *
     * @param array<string, mixed>     $config
     * @param ORMClassMetadata<object> $meta
     *
     * @return array<string, mixed>
     */
    private function inspectEmbeddedForVersioned(string $field, array $config, ORMClassMetadata $meta): array
    {
        foreach ((new \ReflectionClass($meta->embeddedClasses[$field]['class']))->getProperties() as $property) {
            if ($this->reader->getPropertyAnnotation($property, Versioned::class)) {
                $embeddedField = $field.'.'.$property->getName();

                if (isset($meta->embeddedClasses[$embeddedField])) {
                    $config = $this->inspectEmbeddedForVersioned($embeddedField, $config, $meta);

                    continue;
                }

                $config['versioned'][] = $embeddedField;
            }
        }

        return $config;
    }

    /**
     * @param ClassMetadata<object> $meta
     * @param non-empty-string      $field
     */
    private function isRelationship(ClassMetadata $meta, string $field): bool
    {
        if ($meta instanceof MongoDBDOMClassMetadata) {
            return $meta->hasReference($field);
        }

        if ($meta instanceof ORMClassMetadata) {
            return $meta->hasAssociation($field);
        }

        return false;
    }

    /**
     * @param ClassMetadata<object> $meta
     */
    private function isEmbed(ClassMetadata $meta): bool
    {
        if ($meta instanceof MongoDBDOMClassMetadata) {
            return $meta->isEmbeddedDocument;
        }

        if ($meta instanceof ORMClassMetadata) {
            return $meta->isEmbeddedClass;
        }

        return false;
    }
}
