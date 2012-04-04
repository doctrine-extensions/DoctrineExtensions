<?php

namespace Gedmo\Uploadable\Mapping;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Gedmo\Exception\InvalidMappingException;
use Gedmo\Exception\UploadableCantWriteException;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * This class is used to validate mapping information
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Uploadable.Mapping
 * @subpackage Validator
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

class Validator 
{
    const UPLOADABLE_FILE_MIME_TYPE = 'UploadableFileMimeType';
    const UPLOADABLE_FILE_PATH = 'UploadableFilePath';
    const UPLOADABLE_FILE_SIZE = 'UploadableFileSize';

    /**
     * List of types which are valid for UploadableFileMimeType field
     *
     * @var array
     */
    public static $validFileMimeTypeTypes = array(
        'string'
    );

    /**
     * List of types which are valid for UploadableFilePath field
     *
     * @var array
     */
    public static $validFilePathTypes = array(
        'string'
    );

    /**
     * List of types which are valid for UploadableFileSize field
     *
     * @var array
     */
    public static $validFileSizeTypes = array(
        'decimal'
    );


    public static function validateFileMimeTypeField(ClassMetadataInfo $meta, $field)
    {
        self::validateField($meta, $field, self::UPLOADABLE_FILE_MIME_TYPE, self::$validFileMimeTypeTypes);
    }

    public static function validateFilePathField(ClassMetadataInfo $meta, $field)
    {
        self::validateField($meta, $field, self::UPLOADABLE_FILE_PATH, self::$validFilePathTypes);
    }

    public static function validateFileSizeField(ClassMetadataInfo $meta, $field)
    {
        self::validateField($meta, $field, self::UPLOADABLE_FILE_SIZE, self::$validFileSizeTypes);
    }

    public static function validateField($meta, $field, $uploadableField, $validFieldTypes)
    {
        $fieldMapping = $meta->getFieldMapping($field);

        if (!in_array($fieldMapping['type'], $validFieldTypes)) {
            $msg = 'Field "%s" to work as an "%s" field must be of one of the following types: "%s".';

            throw new InvalidMappingException(sprintf($msg,
                $field,
                $uploadableField,
                explode(', ', $validFieldTypes)
            ));
        }
    }

    public static function validatePath($path)
    {
        if (!is_dir($path) || !is_writable($path)) {
            throw new UploadableCantWriteException(sprintf('Directory "%s" does not exist or is not writable',
                $path
            ));
        }
    }

    public static function validateConfiguration(ClassMetadata $meta, array $config)
    {
        $refl = $meta->getReflectionClass();

        if (!$config['filePathField']) {
            throw new InvalidMappingException(sprintf('Class "%s" must have an UploadableFilePath field.',
                $meta->name
            ));
        }

        if (!$config['fileInfoProperty']) {
            throw new InvalidMappingException(sprintf('Class "%s" must define a "fileInfoProperty".',
                $meta->name
            ));
        } else {
            if (!$refl->hasProperty($config['fileInfoProperty'])) {
                throw new InvalidMappingException(sprintf('Class "%s" doesn\'t have property "%s"!',
                    $meta->name,
                    $config['fileInfoProperty']
                ));
            }
        }

        if ($config['path'] === '' && $config['pathMethod'] === '') {
            $msg = 'You need to define the path in the %s annotation, or add a method with %s annotation.';

            throw new InvalidMappingException(sprintf($msg,
                self::UPLOADABLE,
                self::UPLOADABLE_PATH
            ));
        } else if ($config['pathMethod'] !== '') {
            if (!$refl->hasMethod($config['pathMethod'])) {
                throw new InvalidMappingException(sprintf('Class "%s" doesn\'t have method "%s"!',
                    $meta->name,
                    $config['pathMethod']
                ));
            }
        }

        if ($config['fileMimeTypeField']) {
            self::validateFileMimeTypeField($meta, $config['fileMimeTypeField']);
        }

        if ($config['fileSizeField']) {
            self::validateFileSizeField($meta, $config['fileSizeField']);
        }

        self::validateFilePathField($meta, $config['filePathField']);
    }
}
