<?php

namespace Fixture\EncoderExtension\Mapping\Driver;

use Gedmo\Mapping\Driver\AnnotationDriver;
use Gedmo\Mapping\ExtensionMetadataInterface;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;

class Annotation extends AnnotationDriver
{
    public function loadExtensionMetadata(ClassMetadata $meta, ExtensionMetadataInterface $exm)
    {
        $class = $meta->reflClass;
        // check only property annotations
        foreach ($class->getProperties() as $property) {
            // skip inherited properties
            if ($meta->isMappedSuperclass && !$property->isPrivate() ||
                $meta->isInheritedField($property->name) ||
                isset($meta->associationMappings[$property->name]['inherited'])
            ) {
                continue;
            }
            // now lets check if property has our annotation
            if ($encode = $this->reader->getPropertyAnnotation($property, 'Fixture\EncoderExtension\Mapping\Encode')) {
                $field = $property->getName();
                // store the metadata
                $exm->mapEncoderField($field, array(
                    'type' => $encode->type,
                    'secret' => $encode->secret
                ));
            }
        }
    }
}
