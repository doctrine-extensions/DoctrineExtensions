<?php

namespace Gedmo\Timestampable\Mapping\Driver;

use Gedmo\Mapping\Driver\AnnotationDriver;
use Gedmo\Mapping\ExtensionMetadataInterface;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;

/**
 * This is an annotation mapping driver for Timestampable
 * behavioral extension. Used for extraction of extended
 * metadata from Annotations specificaly for Timestampable
 * extension.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Annotation extends AnnotationDriver
{
    /**
     * Annotation field is timestampable
     */
    const TIMESTAMPABLE = 'Gedmo\Mapping\Annotation\Timestampable';

    /**
     * {@inheritDoc}
     */
    public function loadExtensionMetadata(ClassMetadata $meta, ExtensionMetadataInterface $exm)
    {
        $class = $meta->reflClass;
        // property annotations
        foreach ($class->getProperties() as $property) {
            if ($meta->isMappedSuperclass && !$property->isPrivate() ||
                $meta->isInheritedField($property->name) ||
                isset($meta->associationMappings[$property->name]['inherited'])
            ) {
                continue;
            }
            if ($timestampable = $this->reader->getPropertyAnnotation($property, self::TIMESTAMPABLE)) {
                $field = $property->getName();
                $options = array('on' => strtolower($timestampable->on));
                if (isset($timestampable->field)) {
                    $options['field'] = $timestampable->field;
                }
                $options['value'] = isset($timestampable->value) ? $timestampable->value : null;
                $exm->map($field, $options);
            }
        }
    }
}
