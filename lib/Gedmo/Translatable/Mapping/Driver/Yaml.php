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
                    if (in_array('translatable', $fieldMapping['gedmo']) || isset($fieldMapping['gedmo']['translatable'])) {
                        // fields cannot be overrided and throws mapping exception
                        $config['fields'][$field] = array();
                    }
                }
            }
        }

        if (isset($mapping['gedmo'])) {
            $classMapping = $mapping['gedmo'];
            if (isset($classMapping['translation']['entity'])) {
                if (!class_exists($name = $classMapping['translationClass']['name'])) {
                    $ns = substr($meta->name, 0, strrpos($meta->name, '\\'));
                    if (!class_exists($name = $ns.'\\'.$name)) {
                        $config['translationClass'] = $classMapping['translationClass']['name'];
                        throw new InvalidMappingException("Translation class: {$classMapping['translationClass']['name']} does not exist."
                            . " If you haven't generated it yet, use TranslatableCommand to do so", $config);
                    }
                }
                $config['translationClass'] = $name;
            }
        }

        if (!$meta->isMappedSuperclass && $config && !isset($config['translationClass'])) {
            // try to guess translation class
            ($parts = explode('\\', $meta->name)) && ($name = array_pop($parts));
            if (class_exists($fullname = implode('\\', $parts).'\\'.$name.'Translation')) {
                $config['translationClass'] = $fullname;
            } elseif (class_exists($fullname2 = implode('\\', $parts).'\\Translation\\'.$name)) {
                $config['translationClass'] = $fullname2;
            } else {
                throw new InvalidMappingException("Tried to guess translation class as {$fullname} or {$fullname2}"
                    . ", but could not locate it. If you haven't generated it yet, use TranslatableCommand to do so"
                    . ", if it is available elsewhere, specify it in configuration with 'translationClass'", $config);
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
