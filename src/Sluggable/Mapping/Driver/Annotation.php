<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Sluggable\Mapping\Driver;

use Doctrine\Persistence\Mapping\ClassMetadata;
use Gedmo\Exception\InvalidMappingException;
use Gedmo\Mapping\Annotation\Slug;
use Gedmo\Mapping\Annotation\SlugHandler;
use Gedmo\Mapping\Annotation\SlugHandlerOption;
use Gedmo\Mapping\Driver\AnnotationDriverInterface;
use Gedmo\Sluggable\Handler\SlugHandlerInterface;

/**
 * Mapping driver for the sluggable extension which reads extended metadata from annotations on a sluggable class.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @deprecated since gedmo/doctrine-extensions 3.16, will be removed in version 4.0.
 *
 * @internal
 */
class Annotation extends Attribute implements AnnotationDriverInterface
{
    /**
     * @param ClassMetadata<object> $meta
     *
     * @return array<class-string<SlugHandlerInterface>, SlugHandler[]>
     */
    protected function getSlugHandlers(\ReflectionProperty $property, Slug $slug, ClassMetadata $meta): array
    {
        if (!is_array($slug->handlers) || [] === $slug->handlers) {
            return [];
        }

        $handlers = [];

        foreach ($slug->handlers as $handler) {
            if (!$handler instanceof SlugHandler) {
                throw new InvalidMappingException("SlugHandler: {$handler} should be instance of SlugHandler annotation in entity - {$meta->getName()}");
            }

            if (!class_exists($handler->class)) {
                throw new InvalidMappingException("SlugHandler class: {$handler->class} should be a valid class name in entity - {$meta->getName()}");
            }

            /** @var class-string<SlugHandlerInterface> $class */
            $class = $handler->class;

            $handlers[$class] = [];

            foreach ($handler->options as $option) {
                if (!$option instanceof SlugHandlerOption) {
                    throw new InvalidMappingException("SlugHandlerOption: {$option} should be instance of SlugHandlerOption annotation in entity - {$meta->getName()}");
                }

                if ('' === $option->name) {
                    throw new InvalidMappingException("SlugHandlerOption name: {$option->name} should be valid name in entity - {$meta->getName()}");
                }

                $handlers[$class][$option->name] = $option->value;
            }

            $class::validate($handlers[$class], $meta);
        }

        return $handlers;
    }
}
