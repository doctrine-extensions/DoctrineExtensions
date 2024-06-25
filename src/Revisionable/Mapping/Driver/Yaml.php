<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Revisionable\Mapping\Driver;

use Doctrine\Common\Collections\ReadableCollection;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata as MongoDBDOMClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadata as ORMClassMetadata;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Gedmo\Exception\InvalidMappingException;
use Gedmo\Mapping\Driver\File;
use Symfony\Component\Yaml\Yaml as YamlParser;

/**
 * YAML mapping driver for the revisionable extension.
 *
 * @author Boussekeyt Jules <jules.boussekeyt@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @deprecated since gedmo/doctrine-extensions 3.16, will be removed in version 4.0.
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
        $mapping = $this->_getMapping($meta->getName());

        if (isset($mapping['gedmo'])) {
            $classMapping = $mapping['gedmo'];

            if (isset($classMapping['revisionable'])) {
                $config['revisionable'] = true;

                if (isset($classMapping['revisionable']['revisionClass'])) {
                    if (!$cl = $this->getRelatedClassName($meta, $classMapping['revisionable']['revisionClass'])) {
                        throw new InvalidMappingException(sprintf("Class '%s' does not exist.", $classMapping['revisionable']['revisionClass']));
                    }

                    $config['revisionClass'] = $cl;
                }
            }
        }

        if (isset($mapping['fields'])) {
            foreach ($mapping['fields'] as $field => $fieldMapping) {
                $config = $this->inspectMappingForVersioned($meta, $field, $fieldMapping, $config);
            }
        }

        if (isset($mapping['attributeOverride'])) {
            foreach ($mapping['attributeOverride'] as $field => $fieldMapping) {
                $config = $this->inspectMappingForVersioned($meta, $field, $fieldMapping, $config);
            }
        }

        if (isset($mapping['manyToOne'])) {
            foreach ($mapping['manyToOne'] as $field => $fieldMapping) {
                $config = $this->inspectMappingForVersioned($meta, $field, $fieldMapping, $config);
            }
        }

        if (isset($mapping['oneToOne'])) {
            foreach ($mapping['oneToOne'] as $field => $fieldMapping) {
                $config = $this->inspectMappingForVersioned($meta, $field, $fieldMapping, $config);
            }
        }

        if (isset($mapping['embedded'])) {
            foreach ($mapping['embedded'] as $field => $fieldMapping) {
                if (isset($fieldMapping['gedmo'])) {
                    if (in_array('versioned', $fieldMapping['gedmo'], true)) {
                        if ($meta->isCollectionValuedAssociation($field)) {
                            throw new InvalidMappingException(sprintf('Cannot version field %s::$%s, %s implementations are not supported.', $meta->getName(), $field, ReadableCollection::class));
                        }

                        // fields cannot be overridden and throws mapping exception
                        $mapping = $this->_getMapping($fieldMapping['class']);
                        $config = $this->inspectEmbeddedForVersioned($field, $mapping, $config);
                    }
                }
            }
        }

        // Validate extension mapping
        if (!$meta->isMappedSuperclass && $config) {
            if ($meta instanceof MongoDBDOMClassMetadata && is_array($meta->getIdentifier()) && count($meta->getIdentifier()) > 1) {
                throw new InvalidMappingException(sprintf("Composite identifiers are not supported by the revisionable extension when using doctrine/mongodb-odm, cannot keep revisions of '%s'.", $meta->getName()));
            }

            // Invalid when the versioned config is set and the revisionable flag has not been set
            if (isset($config['versioned']) && !isset($config['revisionable'])) {
                throw new InvalidMappingException(sprintf("Class '%s' has fields marked as versioned but the class does not have the 'revisionable' configuration.", $meta->getName()));
            }

            // Invalid when using the ORM and the object is an embedded class
            if ($meta instanceof ORMClassMetadata && isset($config['revisionable']) && $meta->isEmbeddedClass) {
                throw new InvalidMappingException(sprintf("Class '%s' is an embedded class and cannot have the 'revisionable' configuration.", $meta->getName()));
            }
        }

        return $config;
    }

    protected function _loadMappingFile($file)
    {
        return YamlParser::parse(file_get_contents($file));
    }

    /**
     * @param array<string, array<string, array<string, mixed>>> $mapping
     * @param array<string, mixed>                               $config
     *
     * @return array<string, mixed>
     */
    private function inspectEmbeddedForVersioned(string $field, array $mapping, array $config): array
    {
        if (isset($mapping['fields'])) {
            foreach ($mapping['fields'] as $property => $fieldMapping) {
                $config['versioned'][] = $field.'.'.$property;
            }
        }

        return $config;
    }

    /**
     * @param array<string, array<string, array<string, mixed>>> $mapping
     * @param array<string, mixed>                               $config
     *
     * @return array<string, mixed>
     */
    private function inspectMappingForVersioned(ClassMetadata $meta, string $field, array $mapping, array $config): array
    {
        if (isset($mapping['gedmo'])) {
            if (in_array('versioned', $mapping['gedmo'], true)) {
                if ($meta->isCollectionValuedAssociation($field)) {
                    throw new InvalidMappingException(sprintf('Cannot version field %s::$%s, %s implementations are not supported.', $meta->getName(), $field, ReadableCollection::class));
                }

                // fields cannot be overridden and throws mapping exception
                $config['versioned'][] = $field;
            }
        }

        return $config;
    }
}
