<?php

namespace Gedmo\IpTraceable\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Gedmo\Mapping\Driver\AnnotationDriver;
use Gedmo\Mapping\ExtensionMetadataInterface;

/**
 * This is an annotation mapping driver for IpTraceable
 * behavioral extension. Used for extraction of extended
 * metadata from Annotations specifically for IpTraceable
 * extension.
 *
 * @author Pierre-Charles Bertineau <pc.bertineau@alterphp.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Annotation extends AnnotationDriver
{
    /**
     * Annotation field is ipTraceable
     */
    const IP_TRACEABLE = 'Gedmo\Mapping\Annotation\IpTraceable';

    /**
     * {@inheritDoc}
     */
    public function loadExtensionMetadata(ClassMetadata $meta, ExtensionMetadataInterface $exm)
    {
        $class = $meta->getReflectionClass();
        // property annotations
        foreach ($class->getProperties() as $property) {
            if ($meta->isMappedSuperclass && !$property->isPrivate() ||
                $meta->isInheritedField($property->name) ||
                isset($meta->associationMappings[$property->name]['inherited'])
            ) {
                continue;
            }
            if ($ipTraceable = $this->reader->getPropertyAnnotation($property, self::IP_TRACEABLE)) {
                $field = $property->getName();
                $options = array('on' => strtolower($ipTraceable->on));
                if (isset($ipTraceable->field)) {
                    $options['field'] = $ipTraceable->field;
                }
                $options['value'] = isset($ipTraceable->value) ? $ipTraceable->value : null;
                $exm->map($field, $options);
            }
        }
    }
}
