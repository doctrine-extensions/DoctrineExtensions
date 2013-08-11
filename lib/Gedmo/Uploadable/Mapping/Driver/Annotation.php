<?php

namespace Gedmo\Uploadable\Mapping\Driver;

use Gedmo\Mapping\Driver\AnnotationDriver;
use Gedmo\Mapping\ExtensionMetadataInterface;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;

/**
 * This is an annotation mapping driver for Uploadable
 * behavioral extension. Used for extraction of extended
 * metadata from Annotations specificaly for Uploadable
 * extension.
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Annotation extends AnnotationDriver
{
    /**
     * Annotation to define that this object is loggable
     */
    const UPLOADABLE = 'Gedmo\Mapping\Annotation\Uploadable';
    const UPLOADABLE_FILE_MIME_TYPE = 'Gedmo\Mapping\Annotation\UploadableFileMimeType';
    const UPLOADABLE_FILE_PATH = 'Gedmo\Mapping\Annotation\UploadableFilePath';
    const UPLOADABLE_FILE_SIZE = 'Gedmo\Mapping\Annotation\UploadableFileSize';

    /**
     * {@inheritDoc}
     */
    public function loadExtensionMetadata(ClassMetadata $meta, ExtensionMetadataInterface $exm)
    {
        $class = $meta->reflClass;

        // class annotations
        if ($annot = $this->reader->getClassAnnotation($class, self::UPLOADABLE)) {
            $options = array(
                'allowOverwrite' => $annot->allowOverwrite,
                'appendNumber' => $annot->appendNumber,
                'path' => $annot->path,
                'pathMethod' => $annot->pathMethod,
                'fileMimeTypeField' => false,
                'filePathField' => false,
                'fileSizeField' => false,
                'callback' => $annot->callback,
                'filenameGenerator' => $annot->filenameGenerator,
                'maxSize' => (double)$annot->maxSize,
                'allowedTypes' => $annot->allowedTypes,
                'disallowedTypes' => $annot->disallowedTypes,
            );

            foreach ($class->getProperties() as $prop) {
                if ($this->reader->getPropertyAnnotation($prop, self::UPLOADABLE_FILE_MIME_TYPE)) {
                    $options['fileMimeTypeField'] = $prop->getName();
                }

                if ($this->reader->getPropertyAnnotation($prop, self::UPLOADABLE_FILE_PATH)) {
                    $options['filePathField'] = $prop->getName();
                }

                if ($this->reader->getPropertyAnnotation($prop, self::UPLOADABLE_FILE_SIZE)) {
                    $options['fileSizeField'] = $prop->getName();
                }
            }

            $exm->map($options);
        }
    }
}
