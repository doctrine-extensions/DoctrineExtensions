<?php

namespace Gedmo\Loggable\Mapping\Driver;

use Gedmo\Mapping\Driver\File,
    Doctrine\Common\Persistence\Mapping\ClassMetadata,
    Gedmo\Exception\InvalidMappingException;

/**
 * This is a xml mapping driver for Loggable
 * behavioral extension. Used for extraction of extended
 * metadata from xml specificaly for Loggable
 * extension.
 *
 * @author Boussekeyt Jules <jules.boussekeyt@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Loggable.Mapping.Driver
 * @subpackage Xml
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Xml extends File
{
    /**
     * File extension
     * @var string
     */
    protected $_extension = '.dcm.xml';

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
        $xml = $this->_getMapping($meta->name);

        if (($xml->getName() == 'entity' || $xml->getName() == 'mapped-superclass') && isset($xml->gedmo)) {
            if (isset($xml->gedmo->loggable)) {
                $data = $xml->gedmo->loggable;
                $config['loggable'] = true;
                if (isset($data['log-entry-class'])) {
                    $class = (string)$data['log-entry-class'];
                    if (!class_exists($class)) {
                        throw new InvalidMappingException("LogEntry class: {$class} does not exist.");
                    }
                    $config['logEntryClass'] = $class;
                }
            }
        }

        if (isset($xml->field)) {
            $this->inspectElementForVersioned($xml->field, $config, $meta);
        }
        if (isset($xml->{'many-to-one'})) {
            $this->inspectElementForVersioned($xml->{'many-to-one'}, $config, $meta);
        }
        if (isset($xml->{'one-to-one'})) {
            $this->inspectElementForVersioned($xml->{'one-to-one'}, $config, $meta);
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function _loadMappingFile($file)
    {
        $result = array();
        $xmlElement = simplexml_load_file($file);

        if (isset($xmlElement->entity)) {
            foreach ($xmlElement->entity as $entityElement) {
                $entityName = (string)$entityElement['name'];
                $result[$entityName] = $entityElement;
            }
        } else if (isset($xmlElement->{'mapped-superclass'})) {
            foreach ($xmlElement->{'mapped-superclass'} as $mappedSuperClass) {
                $className = (string)$mappedSuperClass['name'];
                $result[$className] = $mappedSuperClass;
            }
        }
        return $result;
    }

    /**
     * Searches mappings on element for versioned fields
     *
     * @param SimpleXMLElement $element
     * @param array $config
     * @param ClassMetadata $meta
     */
    private function inspectElementForVersioned(\SimpleXMLElement $element, array &$config, ClassMetadata $meta)
    {
        foreach ($element as $mapping) {
            $isAssoc = isset($mapping['field']);
            $field = (string)$mapping[$isAssoc ? 'field' : 'name'];
            if (isset($mapping->gedmo)) {
                if (isset($mapping->gedmo->versioned)) {
                    if ($isAssoc && !$meta->associationMappings[$field]['isOwningSide']) {
                        throw new InvalidMappingException("Cannot version [{$field}] as it is not the owning side in object - {$meta->name}");
                    }
                    $config['versioned'][] = $field;
                }
            }
        }
    }
}
