<?php

namespace Gedmo\Tests\Mapping\Annotation;

use Doctrine\Common\Annotations\AnnotationReader;
use Gedmo\Mapping\Annotation\Annotation;
use PHPUnit\Framework\TestCase;

abstract class BaseClassAnnotationTestCase extends TestCase
{
    /**
     * @requires PHP 8
     * @dataProvider getValidParameters
     */
    public function testLoadFromAttribute(string $annotationProperty, $expectedReturn): void
    {
        $annotation = $this->getClassAnnotation(true);
        static::assertSame($annotation->$annotationProperty, $expectedReturn);
    }

    /**
     * @dataProvider getValidParameters
     */
    public function testLoadFromDoctrineAnnotation(string $annotationProperty, $expectedReturn): void
    {
        $annotation = $this->getClassAnnotation(false);
        static::assertSame($annotation->$annotationProperty, $expectedReturn);
    }

    abstract public function getValidParameters(): iterable;

    abstract protected function getAnnotationClass(): string;

    abstract protected function getAttributeModelClass(): string;

    abstract protected function getAnnotationModelClass(): string;

    private function getClassAnnotation(bool $attributes): Annotation
    {
        $class = $attributes ? $this->getAttributeModelClass() : $this->getAnnotationModelClass();
        $reflection = new \ReflectionClass($class);
        $annotationClass = $this->getAnnotationClass();

        if ($attributes) {
            $attributes = $reflection->getAttributes($annotationClass);
            $annotation = $attributes[0]->newInstance();
        } else {
            $reader = new AnnotationReader();
            $annotation = $reader->getClassAnnotation($reflection, $annotationClass);
        }

        if (!is_a($annotation, $annotationClass)) {
            throw new \LogicException('Can\'t parse annotation.');
        }

        return $annotation;
    }
}
