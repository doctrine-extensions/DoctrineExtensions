<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Mapping\Driver;

use Gedmo\Mapping\Annotation\Annotation;

/**
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @internal
 */
final class AttributeReader
{
    /** @var array<string,bool> */
    private array $isRepeatableAttribute = [];

    /**
     * @phpstan-param \ReflectionClass<object> $class
     *
     * @return array<Annotation|Annotation[]>
     */
    public function getClassAnnotations(\ReflectionClass $class): array
    {
        return $this->convertToAttributeInstances($class->getAttributes());
    }

    /**
     * @phpstan-param \ReflectionClass<object> $class
     * @phpstan-param class-string $annotationName
     *
     * @return Annotation|Annotation[]|null
     */
    public function getClassAnnotation(\ReflectionClass $class, string $annotationName)
    {
        return $this->getClassAnnotations($class)[$annotationName] ?? null;
    }

    /**
     * @return array<Annotation|Annotation[]>
     */
    public function getPropertyAnnotations(\ReflectionProperty $property): array
    {
        return $this->convertToAttributeInstances($property->getAttributes());
    }

    /**
     * @phpstan-param class-string $annotationName
     *
     * @return Annotation|Annotation[]|null
     */
    public function getPropertyAnnotation(\ReflectionProperty $property, string $annotationName)
    {
        return $this->getPropertyAnnotations($property)[$annotationName] ?? null;
    }

    /**
     * @param iterable<\ReflectionAttribute> $attributes
     *
     * @phpstan-param iterable<\ReflectionAttribute<object>> $attributes
     *
     * @return array<string, Annotation|Annotation[]>
     */
    private function convertToAttributeInstances(iterable $attributes): array
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

            if ($this->isRepeatable($attributeName)) {
                if (!isset($instances[$attributeName])) {
                    $instances[$attributeName] = [];
                }

                $instances[$attributeName][] = $instance;
            } else {
                $instances[$attributeName] = $instance;
            }
        }

        return $instances;
    }

    private function isRepeatable(string $attributeClassName): bool
    {
        if (isset($this->isRepeatableAttribute[$attributeClassName])) {
            return $this->isRepeatableAttribute[$attributeClassName];
        }

        $reflectionClass = new \ReflectionClass($attributeClassName);
        $attribute = $reflectionClass->getAttributes()[0]->newInstance();

        return $this->isRepeatableAttribute[$attributeClassName] = ($attribute->flags & \Attribute::IS_REPEATABLE) > 0;
    }
}
