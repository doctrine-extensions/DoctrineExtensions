<?php

namespace Fixture\EncoderExtension\Mapping\Driver;

use Gedmo\Mapping\Driver\AbstractAnnotationDriver;
use Gedmo\Exception\InvalidMappingException;

class Annotation extends AbstractAnnotationDriver
{
    public function readExtendedMetadata($meta, array &$config) {
        $class = $meta->getReflectionClass();
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
                // check if field is mapped
                if (!$meta->hasField($field)) {
                    throw new InvalidMappingException("Field is not mapped as object property");
                }
                // allow encoding only strings
                if (!in_array($encode->type, array('sha1', 'md5'))) {
                    throw new InvalidMappingException("Invalid encoding type supplied, sha1 or md5 is available");
                }
                // validate encoding type
                $mapping = $meta->getFieldMapping($field);
                if ($mapping['type'] != 'string') {
                    throw new InvalidMappingException("Only strings can be encoded");
                }
                // store the metadata
                $config['encode'][$field] = array(
                    'type' => $encode->type,
                    'secret' => $encode->secret
                );
            }
        }
    }
}
