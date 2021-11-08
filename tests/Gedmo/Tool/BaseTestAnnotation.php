<?php

namespace Tool;

use Doctrine\Common\Annotations\AnnotationReader;
use Gedmo\Mapping\Annotation\Annotation;
use PHPUnit\Framework\TestCase;

abstract class BaseTestAnnotation extends TestCase
{
    /**
     * @requires PHP 8
     * @dataProvider getValidParameters
     */
    public function testLoadFromAttribute(string $annotationProperty, string $classProperty, $expectedReturn)
    {
        $annotation = $this->getMethodAnnotation($classProperty, true);
        static::assertEquals($annotation->$annotationProperty, $expectedReturn);
    }

    /**
     * @dataProvider getValidParameters
     */
    public function testLoadFromDoctrineAnnotation(string $annotationProperty, string $classProperty, $expectedReturn)
    {
        $annotation = $this->getMethodAnnotation($classProperty, false);
        static::assertEquals($annotation->$annotationProperty, $expectedReturn);
    }

    abstract public function getValidParameters(): iterable;

    abstract protected function getAnnotationClass(): string;

    abstract protected function getAttributeModelClass(): string;

    abstract protected function getAnnotationModelClass(): string;

    private function getMethodAnnotation(string $property, bool $attributes): Annotation
    {
        $class = $attributes ? $this->getAttributeModelClass() : $this->getAnnotationModelClass();
        $reflection = new \ReflectionProperty($class, $property);
        $annotationClass = $this->getAnnotationClass();

        if ($attributes) {
            $attributes = $reflection->getAttributes($annotationClass);
            $annotation = $attributes[0]->newInstance();
        } else {
            $reader = new AnnotationReader();
            $annotation = $reader->getPropertyAnnotation($reflection, $annotationClass);
        }

        if (!$annotation instanceof $annotationClass) {
            throw new \Exception('Can\'t parse annotation');
        }

        return $annotation;
    }
}
