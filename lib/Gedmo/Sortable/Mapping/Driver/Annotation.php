<?php

namespace Gedmo\Sortable\Mapping\Driver;

use Gedmo\Mapping\Annotation\Sortable;
use Gedmo\Mapping\Driver\AbstractAnnotationDriver;
use Gedmo\Exception\InvalidMappingException;

/**
 * This is an annotation mapping driver for Sortable
 * behavioral extension. Used for extraction of extended
 * metadata from Annotations specifically for Sortable
 * extension.
 *
 * @author Lukas Botsch <lukas.botsch@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Annotation extends AbstractAnnotationDriver
{
    const SORTABLE = 'Gedmo\\Mapping\\Annotation\\Sortable';

    /**
     * List of types which are valid for position fields
     *
     * @var array
     */
    protected $validTypes = array(
        'int',
        'integer',
        'smallint',
        'bigint',
    );

    /**
     * {@inheritDoc}
     */
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

            /** @var Sortable $sortable */
            if ($sortable = $this->reader->getPropertyAnnotation($property, self::SORTABLE)) {
                $field = $property->getName();
                if (!$meta->hasField($field)) {
                    throw new InvalidMappingException("Unable to find 'position' - [{$field}] as mapped property in entity - {$meta->name}");
                }
                if (!$this->isValidField($meta, $field)) {
                    throw new InvalidMappingException("Sortable position field - [{$field}] type is not valid and must be 'integer' in class - {$meta->name}");
                }

                foreach ($sortable->groups as $group) {
                    if (!$meta->hasField($group) && !$meta->isSingleValuedAssociation($group)) {
                        throw new InvalidMappingException("Sortable field: '{$field}' group: {$group} - is not a mapped
                                    or single valued association property in class {$meta->name}");
                    }
                }

                $config['sortables'][$field] = [
                    'position' => $field,
                    'groups' => $sortable->groups,
                    'useObjectClass' => $meta->name
                ];
            }
        }
    }
}
