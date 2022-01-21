<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Uploadable\Mapping;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Gedmo\Exception\InvalidMappingException;
use Gedmo\Exception\UploadableCantWriteException;
use Gedmo\Exception\UploadableInvalidPathException;
use Gedmo\Uploadable\FilenameGenerator\FilenameGeneratorInterface;

/**
 * This class is used to validate mapping information
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
class Validator
{
    public const UPLOADABLE_FILE_MIME_TYPE = 'UploadableFileMimeType';
    public const UPLOADABLE_FILE_NAME = 'UploadableFileName';
    public const UPLOADABLE_FILE_PATH = 'UploadableFilePath';
    public const UPLOADABLE_FILE_SIZE = 'UploadableFileSize';
    public const FILENAME_GENERATOR_SHA1 = 'SHA1';
    public const FILENAME_GENERATOR_ALPHANUMERIC = 'ALPHANUMERIC';
    public const FILENAME_GENERATOR_NONE = 'NONE';

    /**
     * Determines if we should throw an exception in the case the "allowedTypes" and
     * "disallowedTypes" options are BOTH set. Useful for testing purposes
     *
     * @var bool
     */
    public static $enableMimeTypesConfigException = true;

    /**
     * List of types which are valid for UploadableFileMimeType field
     *
     * @var array
     */
    public static $validFileMimeTypeTypes = [
        'string',
    ];

    /**
     * List of types which are valid for UploadableFileName field
     *
     * @var array
     */
    public static $validFileNameTypes = [
        'string',
    ];

    /**
     * List of types which are valid for UploadableFilePath field
     *
     * @var array
     */
    public static $validFilePathTypes = [
        'string',
    ];

    /**
     * List of types which are valid for UploadableFileSize field for ORM
     *
     * @var array
     */
    public static $validFileSizeTypes = [
        'decimal',
    ];

    /**
     * List of types which are valid for UploadableFileSize field for ODM
     *
     * @var array
     */
    public static $validFileSizeTypesODM = [
        'float',
    ];

    /**
     * Whether to validate if the directory of the file exists and is writable, useful to disable it when using
     * stream wrappers which don't support is_dir (like Gaufrette)
     *
     * @var bool
     */
    public static $validateWritableDirectory = true;

    /**
     * @param string $field
     *
     * @return void
     */
    public static function validateFileNameField(ClassMetadata $meta, $field)
    {
        self::validateField($meta, $field, self::UPLOADABLE_FILE_NAME, self::$validFileNameTypes);
    }

    /**
     * @param string $field
     *
     * @return void
     */
    public static function validateFileMimeTypeField(ClassMetadata $meta, $field)
    {
        self::validateField($meta, $field, self::UPLOADABLE_FILE_MIME_TYPE, self::$validFileMimeTypeTypes);
    }

    /**
     * @param string $field
     *
     * @return void
     */
    public static function validateFilePathField(ClassMetadata $meta, $field)
    {
        self::validateField($meta, $field, self::UPLOADABLE_FILE_PATH, self::$validFilePathTypes);
    }

    /**
     * @param string $field
     *
     * @return void
     */
    public static function validateFileSizeField(ClassMetadata $meta, $field)
    {
        self::validateField($meta, $field, self::UPLOADABLE_FILE_SIZE, self::$validFileSizeTypes);
    }

    /**
     * @param ClassMetadata $meta
     * @param string        $field
     * @param string        $uploadableField
     * @param string[]      $validFieldTypes
     *
     * @return void
     */
    public static function validateField($meta, $field, $uploadableField, $validFieldTypes)
    {
        if ($meta->isMappedSuperclass) {
            return;
        }

        $fieldMapping = $meta->getFieldMapping($field);

        if (!in_array($fieldMapping['type'], $validFieldTypes, true)) {
            $msg = 'Field "%s" to work as an "%s" field must be of one of the following types: "%s".';

            throw new InvalidMappingException(sprintf($msg, $field, $uploadableField, implode(', ', $validFieldTypes)));
        }
    }

    /**
     * @param string $path
     *
     * @return void
     */
    public static function validatePath($path)
    {
        if (!is_string($path) || '' === $path) {
            throw new UploadableInvalidPathException('Path must be a string containing the path to a valid directory.');
        }

        if (!self::$validateWritableDirectory) {
            return;
        }

        if (!is_dir($path) && !@mkdir($path, 0777, true)) {
            throw new UploadableInvalidPathException(sprintf('Unable to create "%s" directory.', $path));
        }

        if (!is_writable($path)) {
            throw new UploadableCantWriteException(sprintf('Directory "%s" is not writable.', $path));
        }
    }

    /**
     * @return void
     */
    public static function validateConfiguration(ClassMetadata $meta, array &$config)
    {
        if (!$config['filePathField'] && !$config['fileNameField']) {
            throw new InvalidMappingException(sprintf('Class "%s" must have an UploadableFilePath or UploadableFileName field.', $meta->getName()));
        }

        $refl = $meta->getReflectionClass();

        if ('' !== $config['pathMethod'] && !$refl->hasMethod($config['pathMethod'])) {
            throw new InvalidMappingException(sprintf('Class "%s" doesn\'t have method "%s"!', $meta->getName(), $config['pathMethod']));
        }

        if ('' !== $config['callback'] && !$refl->hasMethod($config['callback'])) {
            throw new InvalidMappingException(sprintf('Class "%s" doesn\'t have method "%s"!', $meta->getName(), $config['callback']));
        }

        $config['maxSize'] = (float) $config['maxSize'];

        if ($config['maxSize'] < 0) {
            throw new InvalidMappingException(sprintf('Option "maxSize" must be a number >= 0 for class "%s".', $meta->getName()));
        }

        if (self::$enableMimeTypesConfigException && '' !== $config['allowedTypes'] && '' !== $config['disallowedTypes']) {
            $msg = 'You\'ve set "allowedTypes" and "disallowedTypes" options. You must set only one in class "%s".';

            throw new InvalidMappingException(sprintf($msg, $meta->getName()));
        }

        $config['allowedTypes'] = $config['allowedTypes'] ? (false !== strpos($config['allowedTypes'], ',') ?
            explode(',', $config['allowedTypes']) : [$config['allowedTypes']]) : false;
        $config['disallowedTypes'] = $config['disallowedTypes'] ? (false !== strpos($config['disallowedTypes'], ',') ?
            explode(',', $config['disallowedTypes']) : [$config['disallowedTypes']]) : false;

        if ($config['fileNameField']) {
            self::validateFileNameField($meta, $config['fileNameField']);
        }

        if ($config['filePathField']) {
            self::validateFilePathField($meta, $config['filePathField']);
        }

        if ($config['fileMimeTypeField']) {
            self::validateFileMimeTypeField($meta, $config['fileMimeTypeField']);
        }

        if ($config['fileSizeField']) {
            self::validateFileSizeField($meta, $config['fileSizeField']);
        }

        switch ((string) $config['filenameGenerator']) {
            case self::FILENAME_GENERATOR_ALPHANUMERIC:
            case self::FILENAME_GENERATOR_SHA1:
            case self::FILENAME_GENERATOR_NONE:
                break;
            default:
                if (!class_exists($config['filenameGenerator']) || !is_subclass_of($config['filenameGenerator'], FilenameGeneratorInterface::class)) {
                    throw new InvalidMappingException(sprintf('Class "%s" needs a valid value for filenameGenerator. It can be: SHA1, ALPHANUMERIC, NONE or a class implementing %s.', $meta->getName(), FilenameGeneratorInterface::class));
                }
        }
    }
}
