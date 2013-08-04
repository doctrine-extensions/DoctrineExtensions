<?php

namespace Gedmo\Sortable\Mapping\Driver;

use Gedmo\Mapping\Driver\AnnotationDriver;
use Gedmo\Mapping\ExtensionMetadataInterface;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;

/**
 * This is an annotation mapping driver for Sortable
 * behavioral extension. Used for extraction of extended
 * metadata from Annotations specificaly for Sortable
 * extension.
 *
 * @author Lukas Botsch <lukas.botsch@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Annotation extends AnnotationDriver
{
    /**
     * Annotation to mark field as sortable
     */
    const SORTABLE = 'Gedmo\Mapping\Annotation\Sortable';

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
            // position
            if ($sortable = $this->reader->getPropertyAnnotation($property, self::SORTABLE)) {
                $exm->map($property->getName(), array(
                    'groups' => $sortable->groups,
                    'rootClass' => $meta->isMappedSuperclass ? null : $meta->name
                ));
            }
        }
    }
}
