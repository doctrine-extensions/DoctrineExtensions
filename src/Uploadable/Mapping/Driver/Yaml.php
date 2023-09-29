<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Uploadable\Mapping\Driver;

use Gedmo\Mapping\Driver;
use Gedmo\Mapping\Driver\File;
use Gedmo\Uploadable\Mapping\Validator;

/**
 * This is a yaml mapping driver for Uploadable
 * behavioral extension. Used for extraction of extended
 * metadata from yaml specifically for Uploadable
 * extension.
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @deprecated since gedmo/doctrine-extensions 3.5, will be removed in version 4.0.
 *
 * @internal
 */
class Yaml extends File implements Driver
{
    /**
     * File extension
     *
     * @var string
     */
    protected $_extension = '.dcm.yml';

    public function readExtendedMetadata($meta, array &$config)
    {
        $mapping = $this->_getMapping($meta->getName());

        if (isset($mapping['gedmo'])) {
            $classMapping = $mapping['gedmo'];

            if (isset($classMapping['uploadable'])) {
                $uploadable = $classMapping['uploadable'];

                $config['uploadable'] = true;
                $config['allowOverwrite'] = isset($uploadable['allowOverwrite']) ?
                    (bool) $uploadable['allowOverwrite'] : false;
                $config['appendNumber'] = isset($uploadable['appendNumber']) ?
                    (bool) $uploadable['appendNumber'] : false;
                $config['path'] = $uploadable['path'] ?? '';
                $config['pathMethod'] = $uploadable['pathMethod'] ?? '';
                $config['callback'] = $uploadable['callback'] ?? '';
                $config['fileMimeTypeField'] = false;
                $config['fileNameField'] = false;
                $config['filePathField'] = false;
                $config['fileSizeField'] = false;
                $config['filenameGenerator'] = $uploadable['filenameGenerator'] ?? Validator::FILENAME_GENERATOR_NONE;
                $config['maxSize'] = isset($uploadable['maxSize']) ?
                    (float) $uploadable['maxSize'] :
                    (float) 0;
                $config['allowedTypes'] = $uploadable['allowedTypes'] ?? '';
                $config['disallowedTypes'] = $uploadable['disallowedTypes'] ?? '';

                if (isset($mapping['fields'])) {
                    foreach ($mapping['fields'] as $field => $info) {
                        if (isset($info['gedmo']) && array_key_exists(0, $info['gedmo'])) {
                            if ('uploadableFileMimeType' === $info['gedmo'][0]) {
                                $config['fileMimeTypeField'] = $field;
                            } elseif ('uploadableFileSize' === $info['gedmo'][0]) {
                                $config['fileSizeField'] = $field;
                            } elseif ('uploadableFileName' === $info['gedmo'][0]) {
                                $config['fileNameField'] = $field;
                            } elseif ('uploadableFilePath' === $info['gedmo'][0]) {
                                $config['filePathField'] = $field;
                            }
                        }
                    }
                }

                $config = Validator::validateConfiguration($meta, $config);
            }
        }

        return $config;
    }

    protected function _loadMappingFile($file)
    {
        return \Symfony\Component\Yaml\Yaml::parse(file_get_contents($file));
    }
}
