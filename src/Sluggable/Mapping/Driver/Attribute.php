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
use Gedmo\Mapping\Driver\AttributeDriverInterface;

/**
 * This is an attribute mapping driver for Sluggable
 * behavioral extension. Used for extraction of extended
 * metadata from attribute specifically for Sluggable
 * extension.
 *
 * @internal
 */
final class Attribute extends Annotation implements AttributeDriverInterface
{
    /**
     * @return array<string, SlugHandler[]>
     */
    protected function getSlugHandlers(\ReflectionProperty $property, Slug $slug, ClassMetadata $meta): array
    {
        $attributeHandlers = $this->reader->getPropertyAnnotation($property, self::HANDLER);

        if (null === $attributeHandlers) {
            return [];
        }

        $handlers = [];

        foreach ($attributeHandlers as $handler) {
            if (!class_exists($handler->class)) {
                throw new InvalidMappingException("SlugHandler class: {$handler->class} should be a valid class name in entity - {$meta->getName()}");
            }

            $class = $handler->class;

            $handlers[$class] = [];
            foreach ($handler->options as $name => $value) {
                $handlers[$class][$name] = $value;
            }

            $class::validate($handlers[$class], $meta);
        }

        return $handlers;
    }
}
