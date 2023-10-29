<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\IpTraceable\Mapping\Driver;

use Gedmo\Exception\InvalidMappingException;
use Gedmo\Mapping\Annotation\IpTraceable;
use Gedmo\Mapping\Driver\AbstractAnnotationDriver;

/**
 * This is an annotation mapping driver for IpTraceable
 * behavioral extension. Used for extraction of extended
 * metadata from Annotations specifically for IpTraceable
 * extension.
 *
 * @author Pierre-Charles Bertineau <pc.bertineau@alterphp.com>
 *
 * @internal
 */
class Annotation extends AbstractAnnotationDriver
{
    /**
     * Annotation field is ipTraceable
     */
    public const IP_TRACEABLE = IpTraceable::class;

    /**
     * List of types which are valid for IP
     *
     * @var string[]
     */
    protected $validTypes = [
        'string',
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
            if ($ipTraceable = $this->reader->getPropertyAnnotation($property, self::IP_TRACEABLE)) {
                $field = $property->getName();

                if (!$meta->hasField($field)) {
                    throw new InvalidMappingException("Unable to find ipTraceable [{$field}] as mapped property in entity - {$meta->getName()}");
                }
                if (!$this->isValidField($meta, $field)) {
                    throw new InvalidMappingException("Field - [{$field}] type is not valid and must be 'string' - {$meta->getName()}");
                }
                if (!in_array($ipTraceable->on, ['update', 'create', 'change'], true)) {
                    throw new InvalidMappingException("Field - [{$field}] trigger 'on' is not one of [update, create, change] in class - {$meta->getName()}");
                }
                if ('change' === $ipTraceable->on) {
                    if (!isset($ipTraceable->field)) {
                        throw new InvalidMappingException("Missing parameters on property - {$field}, field must be set on [change] trigger in class - {$meta->getName()}");
                    }
                    if (is_array($ipTraceable->field) && isset($ipTraceable->value)) {
                        throw new InvalidMappingException('IpTraceable extension does not support multiple value changeset detection yet.');
                    }
                    $field = [
                        'field' => $field,
                        'trackedField' => $ipTraceable->field,
                        'value' => $ipTraceable->value,
                    ];
                }
                // properties are unique and mapper checks that, no risk here
                $config[$ipTraceable->on][] = $field;
            }
        }

        return $config;
    }
}
