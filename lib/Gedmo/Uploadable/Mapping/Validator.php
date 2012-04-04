<?php

namespace Gedmo\Uploadable\Mapping;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Gedmo\Exception\InvalidMappingException;

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
}
