<?php

namespace Gedmo\Blameable\Mapping\Driver;

use Gedmo\Mapping\Driver\AnnotationDriver;
use Gedmo\Mapping\ExtensionMetadataInterface;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;

/**
 * This is an annotation mapping driver for Blameable
 * behavioral extension. Used for extraction of extended
 * metadata from Annotations specificaly for Blameable
 * extension.
 *
 * @author David Buchmann <mail@davidbu.ch>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Annotation extends AnnotationDriver
{
    /**
     * Annotation field is blameable
     */
    const BLAMEABLE = 'Gedmo\Mapping\Annotation\Blameable';

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
            if ($blameable = $this->reader->getPropertyAnnotation($property, self::BLAMEABLE)) {
                $field = $property->getName();
                $options = array('on' => strtolower($blameable->on));
                if (isset($blameable->field)) {
                    $options['field'] = $blameable->field;
                }
                $options['value'] = isset($blameable->value) ? $blameable->value : null;
                $exm->map($field, $options);
            }
        }
    }
}
