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
 * Mapping driver for the sortable extension which reads extended metadata from attributes on a sortable class.
 *
 * @author Lukas Botsch <lukas.botsch@gmail.com>
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 *
 * @internal
 */
class Attribute extends AbstractAnnotationDriver
{
    /**
     * Mapping object to mark a field as the one which will store the node position on a sortable object.
     */
    public const POSITION = SortablePosition::class;

    /**
     * Mapping object to mark a field as part of a sorting group for a sortable object.
     */
    public const GROUP = SortableGroup::class;

    /**
     * List of types which are valid for position fields
     *
     * @var string[]
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
            if ($meta->isMappedSuperclass && !$property->isPrivate()
                || $meta->isInheritedField($property->name)
                || isset($meta->associationMappings[$property->name]['inherited'])
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

                $config['groups'] ??= [];
                $config['groups'][] = $field;
            }
        }

        if (!$meta->isMappedSuperclass && $config) {
            if (!isset($config['position'])) {
                throw new InvalidMappingException("Missing property: 'position' in class - {$meta->getName()}");
            }
        }

        return $config;
    }
}
