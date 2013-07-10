<?php

namespace Gedmo\Translatable\Mapping\Driver;

use Gedmo\Mapping\Driver\File;
use Gedmo\Mapping\Driver;
use Gedmo\Exception\InvalidMappingException;

/**
 * This is a yaml mapping driver for Translatable
 * behavioral extension. Used for extraction of extended
 * metadata from yaml specificaly for Translatable
 * extension.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
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
    public function readExtendedMetadata($meta, array &$config)
    {
        $mapping = $this->_getMapping($meta->name);

        if (isset($mapping['fields'])) {
            foreach ($mapping['fields'] as $field => $fieldMapping) {
                if (isset($fieldMapping['gedmo'])) {
                    if (array_key_exists('translatable', $fieldMapping['gedmo'])) {
                        // fields cannot be overrided and throws mapping exception
                        $config['fields'][$field] = array();
                    }
                }
            }
        }

        if (!$meta->isMappedSuperclass && $config && !isset($config['translationClass'])) {
            // ensure identifier is singular
            if (is_array($meta->identifier) && count($meta->identifier) > 1) {
                throw new InvalidMappingException("Translatable does not support composite identifiers in class - {$meta->name}");
            }
            // try to guess translation class
            $config['translationClass'] = $meta->name . 'Translation';
            if (!class_exists($config['translationClass'])) {
                throw new InvalidMappingException("Translation class {$config['translationClass']} for domain object {$meta->name}"
                    . ", was not found or could not be autoloaded. If it was not generated yet, use GenerateTranslationsCommand", $config);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function _loadMappingFile($file)
    {
        return \Symfony\Component\Yaml\Yaml::parse($file);
    }
}
