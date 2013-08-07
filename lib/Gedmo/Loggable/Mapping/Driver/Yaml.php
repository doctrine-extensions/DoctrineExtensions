<?php

namespace Gedmo\Loggable\Mapping\Driver;

use Gedmo\Mapping\Driver\FileDriver;
use Gedmo\Mapping\ExtensionMetadataInterface;
use Gedmo\Exception\InvalidMappingException;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;

/**
 * This is a yaml mapping driver for Loggable
 * behavioral extension. Used for extraction of extended
 * metadata from yaml specificaly for Loggable
 * extension.
 *
 * @author Boussekeyt Jules <jules.boussekeyt@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Yaml extends FileDriver
{
    /**
     * {@inheritDoc}
     */
    public function loadExtensionMetadata(ClassMetadata $meta, ExtensionMetadataInterface $exm)
    {
        $mapping = $this->getMapping($meta->name);

        if (isset($mapping['gedmo'])) {
            $classMapping = $mapping['gedmo'];
            if (isset($classMapping['loggable'])) {
                $config['loggable'] = true;
                if (isset ($classMapping['loggable']['logEntryClass'])) {
                    if (!class_exists($name = $classMapping['loggable']['logEntryClass'])) {
                        $ns = substr($meta->name, 0, strrpos($meta->name, '\\'));
                        if (!class_exists($name = $ns.'\\'.$name)) {
                            throw new InvalidMappingException("LogEntry class: {$classMapping['loggable']['logEntryClass']} does not exist.");
                        }
                    }
                    $exm->setLogClass($name);
                }
            }
        }

        if (isset($mapping['fields'])) {
            foreach ($mapping['fields'] as $field => $fieldMapping) {
                if (isset($fieldMapping['gedmo'])) {
                    if (in_array('versioned', $fieldMapping['gedmo']) || isset($fieldMapping['gedmo']['versioned'])) {
                        $exm->map($field);
                    }
                }
            }
        }

        if (isset($mapping['manyToOne'])) {
            foreach ($mapping['manyToOne'] as $field => $fieldMapping) {
                if (isset($fieldMapping['gedmo'])) {
                    if (in_array('versioned', $fieldMapping['gedmo']) || isset($fieldMapping['gedmo']['versioned'])) {
                        $exm->map($field);
                    }
                }
            }
        }

        if (isset($mapping['oneToOne'])) {
            foreach ($mapping['oneToOne'] as $field => $fieldMapping) {
                if (isset($fieldMapping['gedmo'])) {
                    if (in_array('versioned', $fieldMapping['gedmo']) || isset($fieldMapping['gedmo']['versioned'])) {
                        $exm->map($field);
                    }
                }
            }
        }
    }
}
