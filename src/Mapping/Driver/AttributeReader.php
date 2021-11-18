<?php

namespace Gedmo\Mapping\Driver;

use Gedmo\Mapping\Annotation\Annotation;
use ReflectionClass;

/**
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 *
 * @internal
 */
final class AttributeReader
{
    /**
     * @return Annotation[]
     */
    public function getClassAnnotations(ReflectionClass $class): array
    {
        return $this->convertToAttributeInstances($class->getAttributes());
    }

    public function getClassAnnotation(ReflectionClass $class, $annotationName): ?Annotation
    {
        return $this->getClassAnnotations($class)[$annotationName] ?? null;
    }

    /**
     * @return Annotation[]
     */
    public function getPropertyAnnotations(\ReflectionProperty $property): array
    {
        return $this->convertToAttributeInstances($property->getAttributes());
    }

    public function getPropertyAnnotation(\ReflectionProperty $property, $annotationName): ?Annotation
    {
        return $this->getPropertyAnnotations($property)[$annotationName] ?? null;
    }

    /**
     * @param array<\ReflectionAttribute> $attributes
     *
     * @return Annotation[]
     */
    private function convertToAttributeInstances(array $attributes): array
    {
        $instances = [];

        foreach ($attributes as $attribute) {
            $attributeName = $attribute->getName();
            assert(is_string($attributeName));
            // Make sure we only get Gedmo Annotations
            if (!is_subclass_of($attributeName, Annotation::class)) {
                continue;
            }

            $instance = $attribute->newInstance();
            assert($instance instanceof Annotation);

            $instances[$attributeName] = $instance;
        }

        return $instances;
    }
}
