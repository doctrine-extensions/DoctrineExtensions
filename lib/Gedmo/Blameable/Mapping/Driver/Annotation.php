<?php

namespace Gedmo\Blameable\Mapping\Driver;

use Gedmo\Mapping\Driver\AbstractAnnotationDriver,
    Doctrine\Common\Annotations\AnnotationReader,
    Gedmo\Exception\InvalidMappingException;

/**
 * This is an annotation mapping driver for Blameable
 * behavioral extension. Used for extraction of extended
 * metadata from Annotations specifically for Blameable
 * extension.
 *
 * @author David Buchmann <mail@davidbu.ch>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Annotation extends AbstractAnnotationDriver
{
    /**
     * Annotation field is blameable
     */
    const BLAMEABLE = 'Gedmo\\Mapping\\Annotation\\Blameable';

    /**
     * List of types which are valid for blame
     *
     * @var array
     */
    protected $validTypes = array(
        'one',
        'string',
        'int',
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
            if ($blameable = $this->reader->getPropertyAnnotation($property, self::BLAMEABLE)) {
                $name = $property->getName();
                $field = array(
                    'name' => $name,
                    'whom' => $blameable->whom
                );

                if (!$meta->hasField($name) && !$meta->hasAssociation($name)) {
                    throw new InvalidMappingException("Unable to find blameable [{$name}] as mapped property in entity - {$meta->name}");
                }
                if ($meta->hasField($name)) {
                    if ( !$this->isValidField($meta, $name)) {
                        throw new InvalidMappingException("Field - [{$name}] type is not valid and must be 'string' or a one-to-many relation in class - {$meta->name}");
                    }
                } else {
                    // association
                    if (! $meta->isSingleValuedAssociation($name)) {
                        throw new InvalidMappingException("Association - [{$name}] is not valid, it must be a one-to-many relation or a string field - {$meta->name}");
                    }
                }
                if (!in_array($blameable->on, array('update', 'create', 'change'))) {
                    throw new InvalidMappingException("Field - [{$name}] trigger 'on' is not one of [update, create, change] in class - {$meta->name}");
                }
                if ($blameable->on == 'change') {
                    if (!isset($blameable->field)) {
                        throw new InvalidMappingException("Missing parameters on property - {$name}, field must be set on [change] trigger in class - {$meta->name}");
                    }
                    if (is_array($blameable->field) && isset($blameable->value)) {
                        throw new InvalidMappingException("Blameable extension does not support multiple value changeset detection yet.");
                    }
                    $field['trackedField'] = $blameable->field;
                    $field['value'] = $blameable->value;
                }
                // properties are unique and mapper checks that, no risk here
                $config[$blameable->on][] = $field;
            }
        }
    }
}
