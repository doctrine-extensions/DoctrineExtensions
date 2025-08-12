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
use Gedmo\Mapping\Driver\Xml as BaseXml;

/**
 * Mapping driver for the revisionable extension which reads extended metadata from an XML mapping document for a revisionable class.
 *
 * @author Boussekeyt Jules <jules.boussekeyt@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Miha Vrhovnik <miha.vrhovnik@gmail.com>
 *
 * @internal
 */
final class Xml extends BaseXml
{
    public function readExtendedMetadata($meta, array &$config)
    {
        // Skip embedded classes for the ORM, they will be handled inline while processing classes using embeds
        if ($meta instanceof ORMClassMetadata && $meta->isEmbeddedClass) {
            return $config;
        }

        $xmlDoctrine = $this->_getMapping($meta->getName());

        assert($xmlDoctrine instanceof \SimpleXMLElement);

        $xml = $xmlDoctrine->children(self::GEDMO_NAMESPACE_URI);

        $isEmbed = $this->isEmbed($meta);

        // Determine if the object is revisionable by inspecting the revisionable element if present
        if (isset($xml->revisionable)) {
            $data = $xml->revisionable;
            $config['revisionable'] = true;

            if ($this->_isAttributeSet($data, 'revision-class')) {
                // Embedded models cannot have a revision class defined, their data is logged to the owning model
                if ($isEmbed) {
                    throw new InvalidMappingException(sprintf("Class '%s' is mapped as an embedded object and cannot specify a revision-class attribute.", $meta->getName()));
                }

                $class = $this->_getAttribute($data, 'revision-class');

                if (!$cl = $this->getRelatedClassName($meta, $class)) {
                    throw new InvalidMappingException(sprintf("The revision class '%s' configured for '%s' does not exist.", $class, $meta->getName()));
                }

                $config['revisionClass'] = $cl;
            }
        }

        $config = $this->inspectDocument($xmlDoctrine, $config, $meta);

        // Validate configuration
        if (!$meta->isMappedSuperclass && $config) {
            // The revisionable flag must be set, except for embedded models, and the versioned config should be a non-empty array
            if (isset($config['versionedFields']) && (!$this->isEmbed($meta) && !isset($config['revisionable']))) {
                throw new InvalidMappingException(sprintf("Class '%s' has fields with the 'gedmo:versioned' element but the class does not have the 'gedmo:revisionable' element.", $meta->getName()));
            }
        }

        return $config;
    }

    /**
     * Searches a document for versioned fields
     *
     * @param array<string, mixed>  $config
     * @param ClassMetadata<object> $meta
     *
     * @return array<string, mixed>
     */
    private function inspectDocument(\SimpleXMLElement $xmlRoot, array $config, ClassMetadata $meta, string $prepend = ''): array
    {
        // Inspect for versioned fields
        if (isset($xmlRoot->field)) {
            $config = $this->inspectElementForVersioned($xmlRoot->field, $config, $meta, $prepend);
        }

        // Inspect for versioned embeds
        foreach (['embedded', 'embed-one', 'embed-many'] as $embedType) {
            if (isset($xmlRoot->$embedType)) {
                $config = $this->inspectElementForVersioned($xmlRoot->$embedType, $config, $meta, $prepend);
            }
        }

        // Inspect for versioned relationships
        foreach (['many-to-one', 'one-to-one', 'reference-one'] as $relationshipType) {
            if (isset($xmlRoot->$relationshipType)) {
                $config = $this->inspectElementForVersioned($xmlRoot->$relationshipType, $config, $meta, $prepend);
            }
        }

        // Inspect attribute overrides
        if (isset($xmlRoot->{'attribute-overrides'})) {
            foreach ($xmlRoot->{'attribute-overrides'}->{'attribute-override'} ?? [] as $overrideMapping) {
                $config = $this->inspectElementForVersioned($overrideMapping, $config, $meta, $prepend);
            }
        }

        return $config;
    }

    /**
     * Searches direct child nodes of the given element for versioned fields
     *
     * @param array<string, mixed>  $config
     * @param ClassMetadata<object> $meta
     *
     * @return array<string, mixed>
     */
    private function inspectElementForVersioned(\SimpleXMLElement $element, array $config, ClassMetadata $meta, string $prepend = ''): array
    {
        foreach ($element as $mappingDoctrine) {
            $mapping = $mappingDoctrine->children(self::GEDMO_NAMESPACE_URI);

            if (!isset($mapping->versioned)) {
                continue;
            }

            $isRelationship = !in_array($mappingDoctrine->getName(), ['field', 'embedded', 'embed-one', 'embed-many'], true);

            $field = $this->_getAttribute(
                $mappingDoctrine,
                $isRelationship ? 'field' : 'name'
            );

            if ($meta->isCollectionValuedAssociation($field)) {
                throw new InvalidMappingException(sprintf('Cannot version field %s::$%s, collection valued associations are not supported.', $meta->getName(), $field));
            }

            // The MongoDB ODM's @EmbedMany is not supported
            if ('embed-many' === $mappingDoctrine->getName()) {
                throw new InvalidMappingException(sprintf('Cannot version field %s::$%s, an embedded many collection is not supported.', $meta->getName(), $field));
            }

            // To version a field with a relationship, it must be the owning side
            if ($isRelationship) {
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

            $config['versionedFields'][] = $prepend
                ? $prepend.'.'.$field
                : $field;
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
        $xmlDoctrine = $this->_getMapping($meta->embeddedClasses[$field]['class']);

        assert($xmlDoctrine instanceof \SimpleXMLElement);

        return $this->inspectDocument($xmlDoctrine, $config, $meta, $field);
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
