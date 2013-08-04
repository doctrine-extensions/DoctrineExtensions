<?php

namespace Gedmo\SoftDeleteable\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Gedmo\Mapping\Driver\AnnotationDriver;
use Gedmo\Mapping\ExtensionMetadataInterface;

/**
 * This is an annotation mapping driver for SoftDeleteable
 * behavioral extension. Used for extraction of extended
 * metadata from Annotations specifically for SoftDeleteable
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
    const SOFT_DELETEABLE = 'Gedmo\Mapping\Annotation\SoftDeleteable';

    /**
     * {@inheritDoc}
     */
    public function loadExtensionMetadata(ClassMetadata $meta, ExtensionMetadataInterface $exm)
    {
        $class = $meta->reflClass;
        // class annotations
        if ($annot = $this->reader->getClassAnnotation($class, self::SOFT_DELETEABLE)) {
            $exm->map($annot->fieldName, $annot->timeAware);
        }
    }
}
