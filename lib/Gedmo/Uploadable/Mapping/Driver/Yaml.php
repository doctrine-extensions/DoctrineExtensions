<?php

namespace Gedmo\Uploadable\Mapping\Driver;

use Gedmo\Mapping\Driver\FileDriver;
use Gedmo\Mapping\ExtensionMetadataInterface;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Gedmo\Uploadable\Mapping\UploadableMetadata;

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

            if (isset($classMapping['uploadable'])) {
                $uploadable = $classMapping['uploadable'];

                $options = array();
                $options['allowOverwrite'] = isset($uploadable['allowOverwrite']) ?
                    (bool) $uploadable['allowOverwrite'] : false;
                $options['appendNumber'] = isset($uploadable['appendNumber']) ?
                    (bool) $uploadable['appendNumber'] : false;
                $options['path'] = isset($uploadable['path']) ? $uploadable['path'] : '';
                $options['pathMethod'] = isset($uploadable['pathMethod']) ? $uploadable['pathMethod'] : '';
                $options['callback'] = isset($uploadable['callback']) ? $uploadable['callback'] : '';
                $options['fileMimeTypeField'] = false;
                $options['filePathField'] = false;
                $options['fileSizeField'] = false;
                $options['filenameGenerator'] = isset($uploadable['filenameGenerator']) ?
                    $uploadable['filenameGenerator'] :
                    UploadableMetadata::GENERATOR_NONE;
                $options['maxSize'] = isset($uploadable['maxSize']) ?
                    (double) $uploadable['maxSize'] :
                    (double) 0;
                $options['allowedTypes'] = isset($uploadable['allowedTypes']) ?
                    $uploadable['allowedTypes'] :
                    '';
                $options['disallowedTypes'] = isset($uploadable['disallowedTypes']) ?
                    $uploadable['disallowedTypes'] :
                    '';

                if (isset($mapping['fields'])) {
                    foreach ($mapping['fields'] as $field => $info) {
                        if (isset($info['gedmo'])) {
                            if (isset($info['gedmo']['uploadableFileMimeType']) || in_array('uploadableFileMimeType', $info['gedmo'])) {
                                $options['fileMimeTypeField'] = $field;
                            } elseif (isset($info['gedmo']['fileSizeField']) || in_array('fileSizeField', $info['gedmo'])) {
                                $options['fileSizeField'] = $field;
                            } elseif (isset($info['gedmo']['filePathField']) || in_array('filePathField', $info['gedmo'])) {
                                $options['filePathField'] = $field;
                            }
                        }
                    }
                }
                $exm->map($options);
            }
        }
    }
}
