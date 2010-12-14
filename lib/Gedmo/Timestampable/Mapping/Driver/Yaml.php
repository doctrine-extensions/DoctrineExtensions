<?php

namespace Gedmo\Timestampable\Mapping\Driver;

use Gedmo\Mapping\Driver\File,
    Gedmo\Mapping\Driver,
    Doctrine\ORM\Mapping\ClassMetadataInfo,
    Gedmo\Timestampable\Mapping\MappingException;

/**
 * This is a yaml mapping driver for Timestampable
 * behavioral extension. Used for extraction of extended
 * metadata from yaml specificaly for Timestampable
 * extension.
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Timestampable.Mapping.Driver
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
        'date',
        'time',
        'datetime'
    );
    
    /**
     * (non-PHPdoc)
     * @see Gedmo\Mapping.Driver::validateFullMetadata()
     */
    public function validateFullMetadata(ClassMetadataInfo $meta, array $config)
    {

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
                if (isset($fieldMapping['gedmo']['timestampable'])) {
                    $mappingProperty = $fieldMapping['gedmo']['timestampable'];
                    if (!$this->_isValidField($meta, $field)) {
                        throw MappingException::notValidFieldType($field, $meta->name);
                    }
                    if (!isset($mappingProperty['on']) || !in_array($mappingProperty['on'], array('update', 'create', 'change'))) {
                        throw MappingException::triggerTypeInvalid($field, $meta->name);
                    }
                    
                    if ($mappingProperty['on'] == 'change') {
                        if (!isset($mappingProperty['field']) || !isset($mappingProperty['value'])) {
                            throw MappingException::parametersMissing($field, $meta->name);
                        }
                        $field = array(
                            'field' => $field,
                            'trackedField' => $mappingProperty['field'],
                            'value' => $mappingProperty['value'] 
                        );
                    }
                    $config[$mappingProperty['on']][] = $field;
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
