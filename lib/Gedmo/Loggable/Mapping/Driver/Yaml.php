<?php

namespace Gedmo\Loggable\Mapping\Driver;

use Gedmo\Mapping\Driver\File,
    Gedmo\Mapping\Driver,
    Doctrine\Common\Persistence\Mapping\ClassMetadata,
    Gedmo\Exception\InvalidMappingException;

/**
 * This is a yaml mapping driver for Loggable
 * behavioral extension. Used for extraction of extended
 * metadata from yaml specificaly for Loggable
 * extension.
 *
 * @author Boussekeyt Jules <jules.boussekeyt@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Loggable.Mapping.Driver
 * @subpackage Yaml
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Yaml extends File implements Driver
{
    /**
     * File extension
     * @var string
     */
    protected $_extension = '.dcm.yml';

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
    public function readExtendedMetadata(ClassMetadata $meta, array &$config)
    {
        $mapping = $this->_getMapping($meta->name);

        if (isset($mapping['gedmo'])) {
            $classMapping = $mapping['gedmo'];
            if (isset($classMapping['loggable'])) {
                $config['loggable'] = true;
                if (isset ($classMapping['loggable']['logEntryClass'])) {
                    if (!class_exists($classMapping['loggable']['logEntryClass'])) {
                        throw new InvalidMappingException("LogEntry class: {$classMapping['loggable']['logEntryClass']} does not exist.");
                    }
                    $config['logEntryClass'] = $classMapping['loggable']['logEntryClass'];
                }
            }
        }

        if (isset($mapping['fields'])) {
            foreach ($mapping['fields'] as $field => $fieldMapping) {
                if (isset($fieldMapping['gedmo'])) {
                    if (in_array('versioned', $fieldMapping['gedmo'])) {
                        if ($meta->isCollectionValuedAssociation($field)) {
                            throw new InvalidMappingException("Cannot versioned [{$field}] as it is collection in object - {$meta->name}");
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
                    if (in_array('versioned', $fieldMapping['gedmo'])) {
                        if ($meta->isCollectionValuedAssociation($field)) {
                            throw new InvalidMappingException("Cannot versioned [{$field}] as it is collection in object - {$meta->name}");
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
                    if (in_array('versioned', $fieldMapping['gedmo'])) {
                        if ($meta->isCollectionValuedAssociation($field)) {
                            throw new InvalidMappingException("Cannot versioned [{$field}] as it is collection in object - {$meta->name}");
                        }
                        // fields cannot be overrided and throws mapping exception
                        $config['versioned'][] = $field;
                    }
                }
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function _loadMappingFile($file)
    {
        return \Symfony\Component\Yaml\Yaml::load($file);
    }
}
