<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\References\Mapping\Driver;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Gedmo\Mapping\Annotation\ReferenceMany;
use Gedmo\Mapping\Annotation\ReferenceManyEmbed;
use Gedmo\Mapping\Annotation\ReferenceOne;
use Gedmo\Mapping\Driver\AnnotationDriverInterface;
use Gedmo\Mapping\Driver\AttributeReader;

/**
 * This is an annotation mapping driver for References
 * behavioral extension.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 * @author Jonathan H. Wage <jonwage@gmail.com>
 *
 * @internal
 */
class Annotation implements AnnotationDriverInterface
{
    /**
     * Annotation to mark field as reference to one
     */
    public const REFERENCE_ONE = ReferenceOne::class;

    /**
     * Annotation to mark field as reference to many
     */
    public const REFERENCE_MANY = ReferenceMany::class;

    /**
     * Annotation to mark field as reference to many
     */
    public const REFERENCE_MANY_EMBED = ReferenceManyEmbed::class;

    /**
     * @var array<string, self::REFERENCE_ONE|self::REFERENCE_MANY|self::REFERENCE_MANY_EMBED>
     */
    private const ANNOTATIONS = [
        'referenceOne' => self::REFERENCE_ONE,
        'referenceMany' => self::REFERENCE_MANY,
        'referenceManyEmbed' => self::REFERENCE_MANY_EMBED,
    ];

    /**
     * original driver if it is available
     *
     * @var MappingDriver
     */
    protected $_originalDriver;

    /**
     * Annotation reader instance
     *
     * @var Reader|AttributeReader|object
     */
    private $reader;

    public function setAnnotationReader($reader)
    {
        $this->reader = $reader;
    }

    public function readExtendedMetadata($meta, array &$config)
    {
        $class = $meta->getReflectionClass();
        foreach (self::ANNOTATIONS as $key => $annotation) {
            $config[$key] = [];
            foreach ($class->getProperties() as $property) {
                if ($meta->isMappedSuperclass && !$property->isPrivate() ||
                    $meta->isInheritedField($property->name) ||
                    isset($meta->associationMappings[$property->name]['inherited'])
                ) {
                    continue;
                }

                if ($reference = $this->reader->getPropertyAnnotation($property, $annotation)) {
                    $config[$key][$property->getName()] = [
                        'field' => $property->getName(),
                        'type' => $reference->type,
                        'class' => $reference->class,
                        'identifier' => $reference->identifier,
                        'mappedBy' => $reference->mappedBy,
                        'inversedBy' => $reference->inversedBy,
                    ];
                }
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
