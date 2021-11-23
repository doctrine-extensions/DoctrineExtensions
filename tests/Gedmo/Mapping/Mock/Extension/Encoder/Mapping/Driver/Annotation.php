<?php

namespace Gedmo\Tests\Mapping\Mock\Extension\Encoder\Mapping\Driver;

use Doctrine\Common\Annotations\AnnotationReader;
use Gedmo\Mapping\Driver;
use Gedmo\Tests\Mapping\Mock\Extension\Encoder\Mapping\Encode;

class Annotation implements Driver
{
    /**
     * original driver if it is available
     */
    protected $_originalDriver = null;

    public function readExtendedMetadata($meta, array &$config)
    {
        $reader = new AnnotationReader();
        // set annotation namespace and alias
        //$reader->setAnnotationNamespaceAlias('Gedmo\Tests\Mapping\Mock\Extension\Encoder\Mapping\\', 'ext');

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
            if ($encode = $reader->getPropertyAnnotation($property, Encode::class)) {
                $field = $property->getName();
                // check if field is mapped
                if (!$meta->hasField($field)) {
                    throw new \Exception('Field is not mapped as object property');
                }
                // allow encoding only strings
                if (!in_array($encode->type, ['sha1', 'md5'])) {
                    throw new \Exception('Invalid encoding type supplied');
                }
                // validate encoding type
                $mapping = $meta->getFieldMapping($field);
                if ('string' != $mapping['type']) {
                    throw new \Exception('Only strings can be encoded');
                }
                // store the metadata
                $config['encode'][$field] = [
                    'type' => $encode->type,
                    'secret' => $encode->secret,
                ];
            }
        }
    }

    /**
     * Passes in the mapping read by original driver
     */
    public function setOriginalDriver($driver)
    {
        $this->_originalDriver = $driver;
    }
}
