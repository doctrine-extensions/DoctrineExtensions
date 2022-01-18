<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Uploadable\Mapping\Driver;

use Gedmo\Mapping\Annotation\Uploadable;
use Gedmo\Mapping\Annotation\Uploadables;
use Gedmo\Mapping\Annotation\UploadableFileMimeType;
use Gedmo\Mapping\Annotation\UploadableFileName;
use Gedmo\Mapping\Annotation\UploadableFilePath;
use Gedmo\Mapping\Annotation\UploadableFileSize;
use Gedmo\Mapping\Driver\AbstractAnnotationDriver;
use Gedmo\Uploadable\Mapping\Validator;

/**
 * This is an annotation mapping driver for Uploadable
 * behavioral extension. Used for extraction of extended
 * metadata from Annotations specifically for Uploadable
 * extension.
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
class Annotation extends AbstractAnnotationDriver
{
    /**
     * Annotation to define that this object is uploadable
     */
    public const UPLOADABLES = Uploadables::class;
    public const UPLOADABLE = Uploadable::class;
    public const UPLOADABLE_FILE_MIME_TYPE = UploadableFileMimeType::class;
    public const UPLOADABLE_FILE_NAME = UploadableFileName::class;
    public const UPLOADABLE_FILE_PATH = UploadableFilePath::class;
    public const UPLOADABLE_FILE_SIZE = UploadableFileSize::class;

    public function readExtendedMetadata($meta, array &$config)
    {
        $class = $this->getMetaReflectionClass($meta);

        // class annotations
        if ($annot = $this->reader->getClassAnnotation($class, self::UPLOADABLES)) {
            foreach ($annot->configurations as $uploadable) {
                /* @var $uploadable \Gedmo\Mapping\Annotation\Uploadable */
                $config[$uploadable->identifier] = $this->readUploadableMetadata($class, $uploadable, $meta);
            }
        } else if ($annot = $this->reader->getClassAnnotation($class, self::UPLOADABLE)) {
            $config[$annot->identifier] = $this->readUploadableMetadata($class, $annot, $meta);
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
    }

    protected function readUploadableMetadata(
        \ReflectionClass $class,
        Uploadable $annot,
        $meta)
    {
        $config = array();
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
            if ($propAnnot = $this->reader->getPropertyAnnotation($prop, self::UPLOADABLE_FILE_MIME_TYPE)) {
                if ($propAnnot->identifier == $annot->identifier) {
                    $config['fileMimeTypeField'] = $prop->getName();
                }
            }

            if ($propAnnot = $this->reader->getPropertyAnnotation($prop, self::UPLOADABLE_FILE_NAME)) {
                if ($propAnnot->identifier == $annot->identifier) {
                    $config['fileNameField'] = $prop->getName();
                }
            }

            if ($propAnnot = $this->reader->getPropertyAnnotation($prop, self::UPLOADABLE_FILE_PATH)) {
                if ($propAnnot->identifier == $annot->identifier) {
                    $config['filePathField'] = $prop->getName();
                }
            }

            if ($propAnnot = $this->reader->getPropertyAnnotation($prop, self::UPLOADABLE_FILE_SIZE)) {
                if ($propAnnot->identifier == $annot->identifier) {
                    $config['fileSizeField'] = $prop->getName();
                }
            }
        }

        Validator::validateConfiguration($meta, $config);
        return $config;
    }
}
