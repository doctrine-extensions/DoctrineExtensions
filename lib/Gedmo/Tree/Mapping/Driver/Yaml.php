<?php

namespace Gedmo\Tree\Mapping\Driver;

use Gedmo\Mapping\Driver\File,
    Gedmo\Mapping\Driver,
    Doctrine\ORM\Mapping\ClassMetadataInfo,
    Gedmo\Tree\Mapping\MappingException;

/**
 * This is a yaml mapping driver for Tree
 * behavioral extension. Used for extraction of extended
 * metadata from yaml specificaly for Tree
 * extension.
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Tree.Mapping.Driver
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
     * List of types which are valid for timestamp
     * 
     * @var array
     */
    private $_validTypes = array(
        'integer',
        'smallint',
        'bigint'
    );
    
    /**
     * (non-PHPdoc)
     * @see Gedmo\Mapping.Driver::validateFullMetadata()
     */
    public function validateFullMetadata(ClassMetadataInfo $meta, array $config)
    {
        if ($config) {
            $missingFields = array();
            if (!isset($config['parent'])) {
                $missingFields[] = 'ancestor';
            }
            if (!isset($config['left'])) {
                $missingFields[] = 'left';
            }
            if (!isset($config['right'])) {
                $missingFields[] = 'right';
            }
            if ($missingFields) {
                throw MappingException::missingMetaProperties($missingFields, $meta->name);
            }
        }
    }
    
    /**
     * (non-PHPdoc)
     * @see Gedmo\Mapping.Driver::readExtendedMetadata()
     */
    public function readExtendedMetadata(ClassMetadataInfo $meta, array &$config) {
        $yaml = $this->_loadMappingFile($this->_findMappingFile($meta->name));
        $mapping = $yaml[$meta->name];
        if (isset($mapping['fields'])) {
            foreach ($mapping['fields'] as $field => $fieldMapping) {
                if (isset($fieldMapping['gedmo']['tree'])) {
                    $mappingProperty = $fieldMapping['gedmo']['tree'];
                    if ($mappingProperty == 'left') {
                        if (!$this->_isValidField($meta, $field)) {
                            throw MappingException::notValidFieldType($field, $meta->name);
                        }
                        $config['left'] = $field;
                    } elseif ($mappingProperty == 'right') {
                        if (!$this->_isValidField($meta, $field)) {
                            throw MappingException::notValidFieldType($field, $meta->name);
                        }
                        $config['right'] = $field;
                    } elseif ($mappingProperty == 'level') {
                        if (!$this->_isValidField($meta, $field)) {
                            throw MappingException::notValidFieldType($field, $meta->name);
                        }
                        $config['level'] = $field;
                    }
                }
            }
        }
        if (isset($mapping['manyToOne'])) {
            foreach ($mapping['manyToOne'] as $field => $relationMapping) {
                if (isset($relationMapping['gedmo']['tree'])) {
                    $mappingProperty = $relationMapping['gedmo']['tree'];
                    if ($mappingProperty == 'parent') {
                        if ($relationMapping['targetEntity'] != $meta->name) {
                            throw MappingException::parentFieldNotMappedOrRelated($field, $meta->name);
                        }
                        $config['parent'] = $field;
                    }
                }
            }
        }
    }
    
    /**
     * (non-PHPdoc)
     * @see Gedmo\Mapping\Driver.File::_loadMappingFile()
     */
    protected function _loadMappingFile($file)
    {
        return \Symfony\Component\Yaml\Yaml::load($file);
    }
    
    /**
     * Checks if $field type is valid
     * 
     * @param ClassMetadataInfo $meta
     * @param string $field
     * @return boolean
     */
    protected function _isValidField(ClassMetadataInfo $meta, $field)
    {
        return in_array($meta->getTypeOfField($field), $this->_validTypes);
    }
}
