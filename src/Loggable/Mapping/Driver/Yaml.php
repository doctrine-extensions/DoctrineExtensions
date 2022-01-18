<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Loggable\Mapping\Driver;

use Gedmo\Exception\InvalidMappingException;
use Gedmo\Mapping\Driver;
use Gedmo\Mapping\Driver\File;

/**
 * This is a yaml mapping driver for Loggable
 * behavioral extension. Used for extraction of extended
 * metadata from yaml specifically for Loggable
 * extension.
 *
 * @author Boussekeyt Jules <jules.boussekeyt@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @deprecated since gedmo/doctrine-extensions 3.5, will be removed in version 4.0.
 */
class Yaml extends File implements Driver
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
            if (isset($classMapping['loggable'])) {
                $config['loggable'] = true;
                if (isset($classMapping['loggable']['logEntryClass'])) {
                    if (!$cl = $this->getRelatedClassName($meta, $classMapping['loggable']['logEntryClass'])) {
                        throw new InvalidMappingException("LogEntry class: {$classMapping['loggable']['logEntryClass']} does not exist.");
                    }
                    $config['logEntryClass'] = $cl;
                }
            }
        }

        if (isset($mapping['fields'])) {
            foreach ($mapping['fields'] as $field => $fieldMapping) {
                if (isset($fieldMapping['gedmo'])) {
                    if (in_array('versioned', $fieldMapping['gedmo'], true)) {
                        if ($meta->isCollectionValuedAssociation($field)) {
                            throw new InvalidMappingException("Cannot apply versioning to field [{$field}] as it is collection in object - {$meta->getName()}");
                        }
                        // fields cannot be overrided and throws mapping exception
                        $config['versioned'][] = $field;
                    }
                }
            }
        }

        if (isset($mapping['attributeOverride'])) {
            foreach ($mapping['attributeOverride'] as $field => $fieldMapping) {
                if (isset($fieldMapping['gedmo'])) {
                    if (in_array('versioned', $fieldMapping['gedmo'], true)) {
                        if ($meta->isCollectionValuedAssociation($field)) {
                            throw new InvalidMappingException("Cannot apply versioning to field [{$field}] as it is collection in object - {$meta->getName()}");
                        }
                        // fields cannot be overrided and throws mapping exception
                        $config['versioned'][] = $field;
                    }
                }
            }
        }

        if (isset($mapping['manyToOne'])) {
            foreach ($mapping['manyToOne'] as $field => $fieldMapping) {
                if (isset($fieldMapping['gedmo'])) {
                    if (in_array('versioned', $fieldMapping['gedmo'], true)) {
                        if ($meta->isCollectionValuedAssociation($field)) {
                            throw new InvalidMappingException("Cannot apply versioning to field [{$field}] as it is collection in object - {$meta->getName()}");
                        }
                        // fields cannot be overrided and throws mapping exception
                        $config['versioned'][] = $field;
                    }
                }
            }
        }

        if (isset($mapping['oneToOne'])) {
            foreach ($mapping['oneToOne'] as $field => $fieldMapping) {
                if (isset($fieldMapping['gedmo'])) {
                    if (in_array('versioned', $fieldMapping['gedmo'], true)) {
                        if ($meta->isCollectionValuedAssociation($field)) {
                            throw new InvalidMappingException("Cannot apply versioning to field [{$field}] as it is collection in object - {$meta->getName()}");
                        }
                        // fields cannot be overrided and throws mapping exception
                        $config['versioned'][] = $field;
                    }
                }
            }
        }

        if (isset($mapping['embedded'])) {
            foreach ($mapping['embedded'] as $field => $fieldMapping) {
                if (isset($fieldMapping['gedmo'])) {
                    if (in_array('versioned', $fieldMapping['gedmo'], true)) {
                        if ($meta->isCollectionValuedAssociation($field)) {
                            throw new InvalidMappingException("Cannot apply versioning to field [{$field}] as it is collection in object - {$meta->getName()}");
                        }
                        // fields cannot be overrided and throws mapping exception
                        $mapping = $this->_getMapping($fieldMapping['class']);
                        $this->inspectEmbeddedForVersioned($field, $mapping, $config);
                    }
                }
            }
        }

        if (!$meta->isMappedSuperclass && $config) {
            if (is_array($meta->getIdentifier()) && count($meta->getIdentifier()) > 1) {
                throw new InvalidMappingException("Loggable does not support composite identifiers in class - {$meta->getName()}");
            }
            if (isset($config['versioned']) && !isset($config['loggable'])) {
                throw new InvalidMappingException("Class must be annoted with Loggable annotation in order to track versioned fields in class - {$meta->getName()}");
            }
        }
    }

    protected function _loadMappingFile($file)
    {
        return \Symfony\Component\Yaml\Yaml::parse(file_get_contents($file));
    }

    private function inspectEmbeddedForVersioned(string $field, array $mapping, array &$config): void
    {
        if (isset($mapping['fields'])) {
            foreach ($mapping['fields'] as $property => $fieldMapping) {
                $config['versioned'][] = $field.'.'.$property;
            }
        }
    }
}
