<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Blameable\Mapping\Driver;

use Gedmo\Exception\InvalidMappingException;
use Gedmo\Mapping\Annotation\Blameable;
use Gedmo\Mapping\Driver\AbstractAnnotationDriver;

/**
 * This is an annotation mapping driver for Blameable
 * behavioral extension. Used for extraction of extended
 * metadata from Annotations specifically for Blameable
 * extension.
 *
 * @author David Buchmann <mail@davidbu.ch>
 */
class Annotation extends AbstractAnnotationDriver
{
    /**
     * Annotation field is blameable
     */
    public const BLAMEABLE = Blameable::class;

    /**
     * List of types which are valid for blame
     *
     * @var array
     */
    protected $validTypes = [
        'one',
        'string',
        'int',
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
            if ($blameable = $this->reader->getPropertyAnnotation($property, self::BLAMEABLE)) {
                $field = $property->getName();

                if (!$meta->hasField($field) && !$meta->hasAssociation($field)) {
                    throw new InvalidMappingException("Unable to find blameable [{$field}] as mapped property in entity - {$meta->getName()}");
                }
                if ($meta->hasField($field)) {
                    if (!$this->isValidField($meta, $field)) {
                        throw new InvalidMappingException("Field - [{$field}] type is not valid and must be 'string' or a one-to-many relation in class - {$meta->getName()}");
                    }
                } else {
                    // association
                    if (!$meta->isSingleValuedAssociation($field)) {
                        throw new InvalidMappingException("Association - [{$field}] is not valid, it must be a one-to-many relation or a string field - {$meta->getName()}");
                    }
                }
                if (!in_array($blameable->on, ['update', 'create', 'change'], true)) {
                    throw new InvalidMappingException("Field - [{$field}] trigger 'on' is not one of [update, create, change] in class - {$meta->getName()}");
                }
                if ('change' === $blameable->on) {
                    if (!isset($blameable->field)) {
                        throw new InvalidMappingException("Missing parameters on property - {$field}, field must be set on [change] trigger in class - {$meta->getName()}");
                    }
                    if (is_array($blameable->field) && isset($blameable->value)) {
                        throw new InvalidMappingException('Blameable extension does not support multiple value changeset detection yet.');
                    }
                    $field = [
                        'field' => $field,
                        'trackedField' => $blameable->field,
                        'value' => $blameable->value,
                    ];
                }
                // properties are unique and mapper checks that, no risk here
                $config[$blameable->on][] = $field;
            }
        }
    }
}
