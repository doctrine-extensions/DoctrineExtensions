<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping\Mock\Extension\Encoder\Mapping\Driver;

use Gedmo\Mapping\Driver\AbstractAnnotationDriver;
use Gedmo\Tests\Mapping\Mock\Extension\Encoder\Mapping\Encode;

class Annotation extends AbstractAnnotationDriver
{
    public function readExtendedMetadata($meta, array &$config)
    {
        $class = $meta->getReflectionClass();
        // check only property annotations
        foreach ($class->getProperties() as $property) {
            // skip inherited properties
            if ($meta->isMappedSuperclass && !$property->isPrivate()
                || $meta->isInheritedField($property->name)
            ) {
                continue;
            }

            // now lets check if property has our annotation
            if ($encode = $this->reader->getPropertyAnnotation($property, Encode::class)) {
                $field = $property->getName();
                // check if field is mapped
                if (!$meta->hasField($field)) {
                    throw new \Exception('Field is not mapped as object property');
                }
                // allow encoding only strings
                if (!in_array($encode->type, ['sha1', 'md5'], true)) {
                    throw new \Exception('Invalid encoding type supplied');
                }
                // validate encoding type
                $mapping = $meta->getFieldMapping($field);
                if ('string' != $mapping['type']) {
                    throw new \Exception('Only strings can be encoded');
                }
                // store the metadata
                $config['encode'][$field] = [
                    'type' => $encode->type,
                    'secret' => $encode->secret,
                ];
            }
        }

        return $config;
    }
}
