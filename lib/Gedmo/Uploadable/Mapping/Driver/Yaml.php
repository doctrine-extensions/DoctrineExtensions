<?php

namespace Gedmo\Uploadable\Mapping\Driver;

use Gedmo\Mapping\Driver\File,
    Gedmo\Mapping\Driver,
    Gedmo\Exception\InvalidMappingException,
    Gedmo\Uploadable\Mapping\Validator;

/**
 * This is a yaml mapping driver for Uploadable
 * behavioral extension. Used for extraction of extended
 * metadata from yaml specificaly for Uploadable
 * extension.
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
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

        if (isset($mapping['gedmo'])) {
            $classMapping = $mapping['gedmo'];

            if (isset($classMapping['uploadable'])) {
                $uploadable = $classMapping['uploadable'];

                $config['uploadable'] = true;
                $config['allowOverwrite'] = isset($uploadable['allowOverwrite']) ?
                    (bool) $uploadable['allowOverwrite'] : false;
                $config['appendNumber'] = isset($uploadable['appendNumber']) ?
                    (bool) $uploadable['appendNumber'] : false;
                $config['path'] = isset($uploadable['path']) ? $uploadable['path'] : '';
                $config['pathMethod'] = isset($uploadable['pathMethod']) ? $uploadable['pathMethod'] : '';
                $config['callback'] = isset($uploadable['callback']) ? $uploadable['callback'] : '';
                $config['fileMimeTypeField'] = false;
                $config['filePathField'] = false;
                $config['fileSizeField'] = false;
                $config['filenameGenerator'] = isset($uploadable['filenameGenerator']) ?
                    $uploadable['filenameGenerator'] :
                    Validator::FILENAME_GENERATOR_NONE;
                $config['maxSize'] = isset($uploadable['maxSize']) ?
                    (double) $uploadable['maxSize'] :
                    (double) 0;
                $config['allowedTypes'] = isset($uploadable['allowedTypes']) ?
                    $uploadable['allowedTypes'] :
                    '';
                $config['disallowedTypes'] = isset($uploadable['disallowedTypes']) ?
                    $uploadable['disallowedTypes'] :
                    '';

                if (isset($mapping['fields'])) {
                    foreach ($mapping['fields'] as $field => $info) {
                        if (isset($info['gedmo']) && array_key_exists(0, $info['gedmo'])) {
                            if ($info['gedmo'][0] === 'uploadableFileMimeType') {
                                $config['fileMimeTypeField'] = $field;
                            } else if ($info['gedmo'][0] === 'uploadableFileSize') {
                                $config['fileSizeField'] = $field;
                            } else if ($info['gedmo'][0] === 'uploadableFilePath') {
                                $config['filePathField'] = $field;
                            }
                        }
                    }
                }

                Validator::validateConfiguration($meta, $config);
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
