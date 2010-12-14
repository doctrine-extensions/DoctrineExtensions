<?php

namespace Gedmo\Translatable\Mapping\Driver;

use Gedmo\Mapping\Driver\File,
    Gedmo\Mapping\Driver,
    Doctrine\ORM\Mapping\ClassMetadataInfo,
    Gedmo\Translatable\Mapping\MappingException;

/**
 * This is a yaml mapping driver for Translatable
 * behavioral extension. Used for extraction of extended
 * metadata from yaml specificaly for Translatable
 * extension.
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Translatable.Mapping.Driver
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
     * List of types which are valid for translation,
     * this property is public and you can add some
     * other types in case it needs translation
     * 
     * @var array
     */
    public $validTypes = array(
        'string',
        'text'
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
        
        if (isset($mapping['gedmo'])) {
            $classMapping = $mapping['gedmo'];
            if (isset($classMapping['translatable']['entity'])) {
                $translationEntity = $classMapping['translatable']['entity'];
                if (!class_exists($translationEntity)) {
                    throw MappingException::translationClassNotFound($translationEntity);
                }
                $config['translationClass'] = $translationEntity;
            }
            if (isset($classMapping['translatable']['locale'])) {
                $config['locale'] = $classMapping['translatable']['locale'];
            } elseif (isset($classMapping['translatable']['language'])) {
                $config['locale'] = $classMapping['translatable']['language'];
            }
        }
        if (isset($mapping['fields'])) {
            foreach ($mapping['fields'] as $field => $fieldMapping) {
                if (isset($fieldMapping['gedmo'])) {
                    if (in_array('translatable', $fieldMapping['gedmo'])) {
                        if (!$this->_isValidField($meta, $field)) {
                            throw MappingException::notValidFieldType($field, $meta->name);
                        }
                        // fields cannot be overrided and throws mapping exception
                        $config['fields'][] = $field;
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
     * Checks if $field type is valid as Translatable field
     * 
     * @param ClassMetadataInfo $meta
     * @param string $field
     * @return boolean
     */
    protected function _isValidField(ClassMetadataInfo $meta, $field)
    {
        return in_array($meta->getTypeOfField($field), $this->validTypes);
    }
}
