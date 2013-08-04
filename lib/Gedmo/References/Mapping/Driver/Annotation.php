<?php

namespace Gedmo\References\Mapping\Driver;

use Gedmo\Mapping\Driver\AnnotationDriver;
use Gedmo\Mapping\ExtensionMetadataInterface;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;

/**
 * This is an annotation mapping driver for References
 * behavioral extension.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 * @author Jonathan H. Wage <jonwage@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Annotation extends AnnotationDriver
{
    /**
     * Annotation to mark field as reference to one
     */
    const REFERENCE_ONE = 'Gedmo\Mapping\Annotation\ReferenceOne';

    /**
     * Annotation to mark field as reference to many
     */
    const REFERENCE_MANY = 'Gedmo\Mapping\Annotation\ReferenceMany';

    /**
     * Annotation to mark field as reference to many
     */
    const REFERENCE_MANY_EMBED = 'Gedmo\Mapping\Annotation\ReferenceManyEmbed';

    /**
     * All association property annotations
     *
     * @var array
     */
    private $all = array(
        'referenceOne'  => self::REFERENCE_ONE,
        'referenceMany' => self::REFERENCE_MANY,
        'referenceManyEmbed' => self::REFERENCE_MANY_EMBED,
    );

    /**
     * {@inheritDoc}
     */
    public function loadExtensionMetadata(ClassMetadata $meta, ExtensionMetadataInterface $exm)
    {
        $class = $meta->reflClass;
        foreach($this->all as $type => $annotation) {
            foreach ($class->getProperties() as $property) {
                if ($meta->isMappedSuperclass && !$property->isPrivate() ||
                    $meta->isInheritedField($property->name) ||
                    isset($meta->associationMappings[$property->name]['inherited'])
                ) {
                    continue;
                }

                if ($reference = $this->reader->getPropertyAnnotation($property, $annotation)) {
                    $exm->map($type, $property->getName(), array(
                        'field'      => $property->getName(),
                        'type'       => $reference->type,
                        'class'      => $reference->class,
                        'identifier' => $reference->identifier,
                        'mappedBy'   => $reference->mappedBy,
                        'inversedBy' => $reference->inversedBy,
                    ));
                }
            }
        }
    }
}
