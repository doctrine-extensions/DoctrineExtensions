<?php

namespace Gedmo\IpTraceable\Mapping\Driver;

use Gedmo\Mapping\Driver\AbstractAnnotationDriver,
    Doctrine\Common\Annotations\AnnotationReader,
    Gedmo\Exception\InvalidMappingException;

/**
 * This is an annotation mapping driver for IpTraceable
 * behavioral extension. Used for extraction of extended
 * metadata from Annotations specifically for IpTraceable
 * extension.
 *
 * @author Pierre-Charles Bertineau <pc.bertineau@alterphp.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Annotation extends AbstractAnnotationDriver
{
    /**
     * Annotation field is ipTraceable
     */
    const IP_TRACEABLE = 'Gedmo\\Mapping\\Annotation\\IpTraceable';

    /**
     * List of types which are valid for IP
     *
     * @var array
     */
    protected $validTypes = array(
        'string',
    );

    /**
     * {@inheritDoc}
     */
    public function readExtendedMetadata($meta, array &$config) {
        $class = $this->getMetaReflectionClass($meta);
        // property annotations
        foreach ($class->getProperties() as $property) {
            if ($meta->isMappedSuperclass && !$property->isPrivate() ||
                $meta->isInheritedField($property->name) ||
                isset($meta->associationMappings[$property->name]['inherited'])
            ) {
                continue;
            }
            if ($ipTraceable = $this->reader->getPropertyAnnotation($property, self::IP_TRACEABLE)) {
                $name = $property->getName();
                $field = array(
                    'name' => $name,
                );

                if (!$meta->hasField($name)) {
                    throw new InvalidMappingException("Unable to find ipTraceable [{$name}] as mapped property in entity - {$meta->name}");
                }
                if ($meta->hasField($name) && !$this->isValidField($meta, $name)) {
                        throw new InvalidMappingException("Field - [{$name}] type is not valid and must be 'string' - {$meta->name}");
                }
                if (!in_array($ipTraceable->on, array('update', 'create', 'change'))) {
                    throw new InvalidMappingException("Field - [{$name}] trigger 'on' is not one of [update, create, change] in class - {$meta->name}");
                }
                if ($ipTraceable->on == 'change') {
                    if (!isset($ipTraceable->field)) {
                        throw new InvalidMappingException("Missing parameters on property - {$name}, field must be set on [change] trigger in class - {$meta->name}");
                    }
                    if (is_array($ipTraceable->field) && isset($ipTraceable->value)) {
                        throw new InvalidMappingException("IpTraceable extension does not support multiple value changeset detection yet.");
                    }
                    $field['trackedField'] = $ipTraceable->field;
                    $field['value'] = $ipTraceable->value;
                }
                // properties are unique and mapper checks that, no risk here
                $config[$ipTraceable->on][] = $field;
            }
        }
    }
}
