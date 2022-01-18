<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Sortable\Mapping\Driver;

use Gedmo\Exception\InvalidMappingException;
use Gedmo\Mapping\Annotation\SortableGroup;
use Gedmo\Mapping\Annotation\SortablePosition;
use Gedmo\Mapping\Driver\AbstractAnnotationDriver;

/**
 * This is an annotation mapping driver for Sortable
 * behavioral extension. Used for extraction of extended
 * metadata from Annotations specifically for Sortable
 * extension.
 *
 * @author Lukas Botsch <lukas.botsch@gmail.com>
 */
class Annotation extends AbstractAnnotationDriver
{
    /**
     * Annotation to mark field as one which will store node position
     */
    public const POSITION = SortablePosition::class;

    /**
     * Annotation to mark field as sorting group
     */
    public const GROUP = SortableGroup::class;

    /**
     * List of types which are valid for position fields
     *
     * @var array
     */
    protected $validTypes = [
        'int',
        'integer',
        'smallint',
        'bigint',
    ];

    public function readExtendedMetadata($meta, array &$config)
    {
        $class = $this->getMetaReflectionClass($meta);

        // property annotations
        foreach ($class->getProperties() as $property) {
            if ($meta->isMappedSuperclass && !$property->isPrivate() ||
                $meta->isInheritedField($property->name) ||
                isset($meta->associationMappings[$property->name]['inherited'])
            ) {
                continue;
            }

            // position
            if ($this->reader->getPropertyAnnotation($property, self::POSITION)) {
                $field = $property->getName();
                if (!$meta->hasField($field)) {
                    throw new InvalidMappingException("Unable to find 'position' - [{$field}] as mapped property in entity - {$meta->getName()}");
                }
                if (!$this->isValidField($meta, $field)) {
                    throw new InvalidMappingException("Sortable position field - [{$field}] type is not valid and must be 'integer' in class - {$meta->getName()}");
                }
                $config['position'] = $field;
            }

            // group
            if ($this->reader->getPropertyAnnotation($property, self::GROUP)) {
                $field = $property->getName();
                if (!$meta->hasField($field) && !$meta->hasAssociation($field)) {
                    throw new InvalidMappingException("Unable to find 'group' - [{$field}] as mapped property in entity - {$meta->getName()}");
                }
                if (!isset($config['groups'])) {
                    $config['groups'] = [];
                }
                $config['groups'][] = $field;
            }
        }

        if (!$meta->isMappedSuperclass && $config) {
            if (!isset($config['position'])) {
                throw new InvalidMappingException("Missing property: 'position' in class - {$meta->getName()}");
            }
        }
    }
}
