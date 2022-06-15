<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Mapping\Driver;

use Doctrine\Common\Annotations\Reader;
use Gedmo\Mapping\Annotation\Annotation;
use ReflectionClass;
use ReflectionMethod;

/**
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 *
 * @internal
 */
final class AttributeAnnotationReader implements Reader
{
    /**
     * @var Reader
     */
    private $annotationReader;

    /**
     * @var AttributeReader
     */
    private $attributeReader;

    public function __construct(AttributeReader $attributeReader, Reader $annotationReader)
    {
        $this->attributeReader = $attributeReader;
        $this->annotationReader = $annotationReader;
    }

    /**
     * @return Annotation[]
     */
    public function getClassAnnotations(ReflectionClass $class): array
    {
        $annotations = $this->attributeReader->getClassAnnotations($class);

        if ([] !== $annotations) {
            return $annotations;
        }

        return $this->annotationReader->getClassAnnotations($class);
    }

    /**
     * @param class-string<T> $annotationName the name of the annotation
     *
     * @return T|null the Annotation or NULL, if the requested annotation does not exist
     *
     * @template T
     */
    public function getClassAnnotation(ReflectionClass $class, $annotationName)
    {
        $annotation = $this->attributeReader->getClassAnnotation($class, $annotationName);

        if (null !== $annotation) {
            return $annotation;
        }

        return $this->annotationReader->getClassAnnotation($class, $annotationName);
    }

    /**
     * @return Annotation[]
     */
    public function getPropertyAnnotations(\ReflectionProperty $property): array
    {
        $propertyAnnotations = $this->attributeReader->getPropertyAnnotations($property);

        if ([] !== $propertyAnnotations) {
            return $propertyAnnotations;
        }

        return $this->annotationReader->getPropertyAnnotations($property);
    }

    /**
     * @param class-string<T> $annotationName the name of the annotation
     *
     * @return T|null the Annotation or NULL, if the requested annotation does not exist
     *
     * @template T
     */
    public function getPropertyAnnotation(\ReflectionProperty $property, $annotationName)
    {
        $annotation = $this->attributeReader->getPropertyAnnotation($property, $annotationName);

        if (null !== $annotation) {
            return $annotation;
        }

        return $this->annotationReader->getPropertyAnnotation($property, $annotationName);
    }

    public function getMethodAnnotations(ReflectionMethod $method): array
    {
        throw new \BadMethodCallException('Not implemented');
    }

    /**
     * @return mixed
     */
    public function getMethodAnnotation(ReflectionMethod $method, $annotationName)
    {
        throw new \BadMethodCallException('Not implemented');
    }
}
