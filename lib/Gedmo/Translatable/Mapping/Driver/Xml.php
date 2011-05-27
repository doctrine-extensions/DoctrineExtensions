<?php

namespace Gedmo\Translatable\Mapping\Driver;

use Gedmo\Mapping\Driver\File,
    Doctrine\Common\Persistence\Mapping\ClassMetadata,
    Gedmo\Exception\InvalidMappingException;

/**
 * This is a yaml mapping driver for Translatable
 * behavioral extension. Used for extraction of extended
 * metadata from yaml specificaly for Translatable
 * extension.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Translatable.Mapping.Driver
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
            throw new InvalidMappingException("Translatable does not support composite identifiers in class - {$meta->name}");
        }
    }

    /**
     * {@inheritDoc}
     */
    public function readExtendedMetadata(ClassMetadata $meta, array &$config) {
        $xml = $this->_getMapping($meta->name);

        if (($xml->getName() == 'entity' || $xml->getName() == 'mapped-superclass') && isset($xml->gedmo)) {
            if (isset($xml->gedmo->translation)) {
                $data = $xml->gedmo->translation;
                if (isset($data['locale'])) {
                    $config['locale'] = (string)$data['locale'];
                } elseif (isset($data['language'])) {
                    $config['locale'] = (string)$data['language'];
                }
                if (isset($data['entity'])) {
                    $entity = (string)$data['entity'];
                    if (!class_exists($entity)) {
                        throw new InvalidMappingException("Translation entity class: {$entity} does not exist.");
                    }
                    $config['translationClass'] = $entity;
                }
            }
        }
        if (isset($xml->field)) {
            foreach ($xml->field as $mapping) {
                $field = (string)$mapping['name'];
                if (isset($mapping->gedmo)) {
                    if (isset($mapping->gedmo->translatable)) {
                        $config['fields'][] = $field;
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
}
