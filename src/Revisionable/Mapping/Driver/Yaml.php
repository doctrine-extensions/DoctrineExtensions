<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Revisionable\Mapping\Driver;

use Doctrine\ORM\Mapping\ClassMetadata as ORMClassMetadata;
use Gedmo\Exception\InvalidMappingException;
use Gedmo\Mapping\Driver\File;
use Symfony\Component\Yaml\Yaml as YamlParser;

/**
 * Mapping driver for the revisionable extension which reads extended metadata from a YAML mapping document for a revisionable class.
 *
 * @author Boussekeyt Jules <jules.boussekeyt@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @deprecated since gedmo/doctrine-extensions 3.x, will be removed in version 4.0.
 *
 * @internal
 */
class Yaml extends File
{
    /**
     * File extension
     *
     * @var string
     */
    protected $_extension = '.dcm.yml';

    public function readExtendedMetadata($meta, array &$config)
    {
        \assert($meta instanceof ORMClassMetadata);

        // Skip embedded classes, they will be handled inline while processing classes using embeds
        if ($meta->isEmbeddedClass) {
            return $config;
        }

        $mapping = $this->_getMapping($meta->getName());

        // Determine if the object is revisionable by inspecting the object root for a Gedmo config node
        if (isset($mapping['gedmo'])) {
            $classMapping = $mapping['gedmo'];

            if (isset($classMapping['revisionable'])) {
                $config['revisionable'] = true;

                if (isset($classMapping['revisionable']['revisionClass'])) {
                    if (!$cl = $this->getRelatedClassName($meta, $classMapping['revisionable']['revisionClass'])) {
                        throw new InvalidMappingException(sprintf("The revision class '%s' configured for '%s' does not exist.", $classMapping['revisionable']['revisionClass'], $meta->getName()));
                    }

                    $config['revisionClass'] = $cl;
                }
            }
        }

        $config = $this->inspectConfiguration($mapping, $config, $meta);

        // Validate configuration
        if (!$meta->isMappedSuperclass && $config) {
            // The revisionable flag must be set, except for embedded models, and the versioned config should be a non-empty array
            if (isset($config['versioned']) && !isset($config['revisionable'])) {
                throw new InvalidMappingException(sprintf("Class '%s' has fields marked as versioned but the class does not have the 'revisionable' configuration.", $meta->getName()));
            }
        }

        return $config;
    }

    protected function _loadMappingFile($file)
    {
        return YamlParser::parse(file_get_contents($file));
    }

    /**
     * Searches a configuration array for versioned fields
     *
     * @param array<string, mixed>     $mapping
     * @param array<string, mixed>     $config
     * @param ORMClassMetadata<object> $meta
     *
     * @return array<string, mixed>
     */
    private function inspectConfiguration(array $mapping, array $config, ORMClassMetadata $meta, string $prepend = ''): array
    {
        // Inspect for versioned fields
        if (isset($mapping['fields'])) {
            $config = $this->inspectConfigurationForVersioned($mapping['fields'], $config, $meta, $prepend);
        }

        // Inspect for versioned embeds
        if (isset($mapping['embedded'])) {
            $config = $this->inspectConfigurationForVersioned($mapping['embedded'], $config, $meta, $prepend);
        }

        // Inspect for versioned relationships
        foreach (['manyToOne', 'oneToOne'] as $relationshipType) {
            if (isset($mapping[$relationshipType])) {
                $config = $this->inspectConfigurationForVersioned($mapping[$relationshipType], $config, $meta, $prepend);
            }
        }

        // Inspect attribute overrides
        if (isset($mapping['attributeOverride'])) {
            $config = $this->inspectConfigurationForVersioned($mapping['attributeOverride'], $config, $meta, $prepend);
        }

        return $config;
    }

    /**
     * @param array<string, mixed>     $config
     * @param ORMClassMetadata<object> $meta
     *
     * @return array<string, mixed>
     */
    private function inspectEmbeddedForVersioned(string $field, array $config, ORMClassMetadata $meta): array
    {
        return $this->inspectConfiguration($this->_getMapping($meta->embeddedClasses[$field]['class']), $config, $meta, $field);
    }

    /**
     * @param array<string, array<string, array<string, mixed>>> $mapping
     * @param array<string, mixed>                               $config
     * @param ORMClassMetadata<object>                           $meta
     *
     * @return array<string, mixed>
     */
    private function inspectConfigurationForVersioned(array $mapping, array $config, ORMClassMetadata $meta, string $prepend = ''): array
    {
        foreach ($mapping as $field => $fieldMapping) {
            if (!isset($fieldMapping['gedmo'])) {
                continue;
            }

            if (in_array('versioned', $fieldMapping['gedmo'], true)) {
                if ($meta->isCollectionValuedAssociation($field)) {
                    throw new InvalidMappingException(sprintf('Cannot version field %s::$%s, collection valued associations are not supported.', $meta->getName(), $field));
                }

                // To version a field with a relationship, it must be the owning side
                if ($meta->hasAssociation($field)) {
                    $associationMapping = $meta->associationMappings[$field];

                    if (!$associationMapping['isOwningSide']) {
                        throw new InvalidMappingException(sprintf('Cannot version field %s::$%s, it is not the owning side of the relationship.', $meta->getName(), $field));
                    }
                }

                /*
                 * Due to differences in the UoW's for each object manager, embedded models need to be handled differently.
                 *
                 * The ORM inlines embedded field mappings to the root entity, so the list of versioned fields needs to be added to
                 * the extension metadata now.
                 */

                if (isset($meta->embeddedClasses[$field])) {
                    $config = $this->inspectEmbeddedForVersioned($field, $config, $meta);

                    continue;
                }

                $config['versioned'][] = $prepend
                    ? $prepend.'.'.$field
                    : $field;
            }
        }

        return $config;
    }
}
