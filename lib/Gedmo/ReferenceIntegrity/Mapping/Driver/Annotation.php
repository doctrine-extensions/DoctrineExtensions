<?php

namespace Gedmo\ReferenceIntegrity\Mapping\Driver;

use Gedmo\Mapping\Driver\AnnotationDriver;
use Gedmo\Mapping\ExtensionMetadataInterface;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;

/**
 * This is an annotation mapping driver for ReferenceIntegrity
 * behavioral extension. Used for extraction of extended
 * metadata from Annotations specifically for ReferenceIntegrity
 * extension.
 *
 * @author Evert Harmeling <evert.harmeling@freshheads.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Annotation extends AnnotationDriver
{
    /**
     * Annotation to identify the fields which manages the reference integrity
     */
    const REFERENCE_INTEGRITY = 'Gedmo\Mapping\Annotation\ReferenceIntegrity';

    /**
     * {@inheritDoc}
     */
    public function loadExtensionMetadata(ClassMetadata $meta, ExtensionMetadataInterface $exm)
    {
        $class = $meta->reflClass;
        foreach ($class->getProperties() as $reflProperty) {
            if ($referenceIntegrity = $this->reader->getPropertyAnnotation($reflProperty, self::REFERENCE_INTEGRITY)) {
                $exm->map($reflProperty->getName(), $referenceIntegrity->value);
            }
        }
    }
}
