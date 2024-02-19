<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Uploadable\Mapping\Driver;

use Gedmo\Mapping\Annotation\Uploadable;
use Gedmo\Mapping\Annotation\UploadableFileMimeType;
use Gedmo\Mapping\Annotation\UploadableFileName;
use Gedmo\Mapping\Annotation\UploadableFilePath;
use Gedmo\Mapping\Annotation\UploadableFileSize;
use Gedmo\Mapping\Driver\AbstractAnnotationDriver;
use Gedmo\Uploadable\Mapping\Validator;

/**
 * Mapping driver for the uploaded extension which reads extended metadata from attributes on an uploadable class.
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @internal
 */
class Attribute extends AbstractAnnotationDriver
{
    /**
     * Mapping object for the uploadable extension.
     */
    public const UPLOADABLE = Uploadable::class;

    /**
     * Mapping object to mark the field which will store the MIME type for an upload.
     */
    public const UPLOADABLE_FILE_MIME_TYPE = UploadableFileMimeType::class;

    /**
     * Mapping object to mark the field which will store the file name for an upload.
     */
    public const UPLOADABLE_FILE_NAME = UploadableFileName::class;

    /**
     * Mapping object to mark the field which will store the filesystem path for an upload.
     */
    public const UPLOADABLE_FILE_PATH = UploadableFilePath::class;

    /**
     * Mapping object to mark the field which will store the file size for an upload.
     */
    public const UPLOADABLE_FILE_SIZE = UploadableFileSize::class;

    public function readExtendedMetadata($meta, array &$config)
    {
        $class = $this->getMetaReflectionClass($meta);

        // class annotations
        if ($annot = $this->reader->getClassAnnotation($class, self::UPLOADABLE)) {
            \assert($annot instanceof Uploadable);

            $config['uploadable'] = true;
            $config['allowOverwrite'] = $annot->allowOverwrite;
            $config['appendNumber'] = $annot->appendNumber;
            $config['path'] = $annot->path;
            $config['pathMethod'] = $annot->pathMethod;
            $config['fileMimeTypeField'] = false;
            $config['fileNameField'] = false;
            $config['filePathField'] = false;
            $config['fileSizeField'] = false;
            $config['callback'] = $annot->callback;
            $config['filenameGenerator'] = $annot->filenameGenerator;
            $config['maxSize'] = (float) $annot->maxSize;
            $config['allowedTypes'] = $annot->allowedTypes;
            $config['disallowedTypes'] = $annot->disallowedTypes;

            foreach ($class->getProperties() as $prop) {
                if ($this->reader->getPropertyAnnotation($prop, self::UPLOADABLE_FILE_MIME_TYPE)) {
                    $config['fileMimeTypeField'] = $prop->getName();
                }

                if ($this->reader->getPropertyAnnotation($prop, self::UPLOADABLE_FILE_NAME)) {
                    $config['fileNameField'] = $prop->getName();
                }

                if ($this->reader->getPropertyAnnotation($prop, self::UPLOADABLE_FILE_PATH)) {
                    $config['filePathField'] = $prop->getName();
                }

                if ($this->reader->getPropertyAnnotation($prop, self::UPLOADABLE_FILE_SIZE)) {
                    $config['fileSizeField'] = $prop->getName();
                }
            }

            $config = Validator::validateConfiguration($meta, $config);
        }

        /*
        // Code in case we need to identify entities which are not Uploadables, but have associations
        // with other Uploadable entities

        } else {
            // We need to check if this class has a relation with Uploadable entities
            $associations = $meta->getAssociationMappings();

            foreach ($associations as $field => $association) {
                $refl = new \ReflectionClass($association['targetEntity']);

                if ($annot = $this->reader->getClassAnnotation($refl, self::UPLOADABLE)) {
                    \assert($annot instanceof Uploadable);

                    $config['hasUploadables'] = true;

                    if (!isset($config['uploadables'])) {
                        $config['uploadables'] = array();
                    }

                    $config['uploadables'][] = array(
                        'class'         => $association['targetEntity'],
                        'property'      => $association['fieldName']
                    );
                }
            }
        }*/

        $this->validateFullMetadata($meta, $config);

        return $config;
    }
}
