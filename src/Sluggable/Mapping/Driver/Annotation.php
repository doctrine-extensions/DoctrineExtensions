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
use Gedmo\Mapping\Driver\AbstractAnnotationDriver;

/**
 * This is an annotation mapping driver for Sluggable
 * behavioral extension. Used for extraction of extended
 * metadata from Annotations specifically for Sluggable
 * extension.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @internal
 */
class Annotation extends AbstractAnnotationDriver
{
    /**
     * Annotation to identify field as one which holds the slug
     * together with slug options
     */
    public const SLUG = Slug::class;

    /**
     * SlugHandler extension annotation
     */
    public const HANDLER = SlugHandler::class;

    /**
     * SlugHandler option annotation
     */
    public const HANDLER_OPTION = SlugHandlerOption::class;

    /**
     * List of types which are valid for slug and sluggable fields
     *
     * @var string[]
     */
    protected $validTypes = [
        'string',
        'text',
        'integer',
        'int',
        'date',
        'date_immutable',
        'datetime',
        'datetime_immutable',
        'datetimetz',
        'datetimetz_immutable',
        'citext',
    ];

    public function readExtendedMetadata($meta, array &$config)
    {
        $class = $this->getMetaReflectionClass($meta);
        // property annotations
        foreach ($class->getProperties() as $property) {
            if ($meta->isMappedSuperclass && !$property->isPrivate()
                || $meta->isInheritedField($property->name)
                || isset($meta->associationMappings[$property->name]['inherited'])
            ) {
                continue;
            }
            $config = $this->retrieveSlug($meta, $config, $property);
        }

        // Embedded entity
        if (property_exists($meta, 'embeddedClasses') && $meta->embeddedClasses) {
            foreach ($meta->embeddedClasses as $propertyName => $embeddedClassInfo) {
                $embeddedClass = new \ReflectionClass($embeddedClassInfo['class']);
                foreach ($embeddedClass->getProperties() as $embeddedProperty) {
                    $config = $this->retrieveSlug($meta, $config, $embeddedProperty, $propertyName);
                }
            }
        }

        return $config;
    }

    /**
     * @internal
     *
     * @return array<string, SlugHandler[]>
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

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, array<string, mixed>>
     */
    private function retrieveSlug(ClassMetadata $meta, array &$config, \ReflectionProperty $property, ?string $fieldNamePrefix = null): array
    {
        $fieldName = null !== $fieldNamePrefix ? ($fieldNamePrefix.'.'.$property->getName()) : $property->getName();

        // slug property
        $slug = $this->reader->getPropertyAnnotation($property, self::SLUG);

        if (null === $slug) {
            return $config;
        }

        assert($slug instanceof Slug);

        if (!$meta->hasField($fieldName)) {
            throw new InvalidMappingException("Unable to find slug [{$fieldName}] as mapped property in entity - {$meta->getName()}");
        }
        if (!$this->isValidField($meta, $fieldName)) {
            throw new InvalidMappingException("Cannot use field - [{$fieldName}] for slug storage, type is not valid and must be 'string' or 'text' in class - {$meta->getName()}");
        }
        // process slug handlers
        $handlers = $this->getSlugHandlers($property, $slug, $meta);

        // process slug fields
        if ([] === $slug->fields || !is_array($slug->fields)) {
            throw new InvalidMappingException("Slug must contain at least one field for slug generation in class - {$meta->getName()}");
        }
        foreach ($slug->fields as $slugField) {
            $slugFieldWithPrefix = null !== $fieldNamePrefix ? ($fieldNamePrefix.'.'.$slugField) : $slugField;
            if (!$meta->hasField($slugFieldWithPrefix)) {
                throw new InvalidMappingException("Unable to find slug [{$slugFieldWithPrefix}] as mapped property in entity - {$meta->getName()}");
            }
            if (!$this->isValidField($meta, $slugFieldWithPrefix)) {
                throw new InvalidMappingException("Cannot use field - [{$slugFieldWithPrefix}] for slug storage, type is not valid and must be 'string' or 'text' in class - {$meta->getName()}");
            }
        }
        if (!is_bool($slug->updatable)) {
            throw new InvalidMappingException("Slug annotation [updatable], type is not valid and must be 'boolean' in class - {$meta->getName()}");
        }
        if (!is_bool($slug->unique)) {
            throw new InvalidMappingException("Slug annotation [unique], type is not valid and must be 'boolean' in class - {$meta->getName()}");
        }
        if ([] !== $meta->getIdentifier() && $meta->isIdentifier($fieldName) && !(bool) $slug->unique) {
            throw new InvalidMappingException("Identifier field - [{$fieldName}] slug must be unique in order to maintain primary key in class - {$meta->getName()}");
        }
        if (false === $slug->unique && $slug->unique_base) {
            throw new InvalidMappingException("Slug annotation [unique_base] can not be set if unique is unset or 'false'");
        }
        if ($slug->unique_base && !$meta->hasField($slug->unique_base) && !$meta->hasAssociation($slug->unique_base)) {
            throw new InvalidMappingException("Unable to find [{$slug->unique_base}] as mapped property in entity - {$meta->getName()}");
        }
        $sluggableFields = [];
        foreach ($slug->fields as $field) {
            $sluggableFields[] = null !== $fieldNamePrefix ? ($fieldNamePrefix.'.'.$field) : $field;
        }

        // set all options
        $config['slugs'][$fieldName] = [
            'fields' => $sluggableFields,
            'slug' => $fieldName,
            'style' => $slug->style,
            'dateFormat' => $slug->dateFormat,
            'updatable' => $slug->updatable,
            'unique' => $slug->unique,
            'unique_base' => $slug->unique_base,
            'separator' => $slug->separator,
            'prefix' => $slug->prefix,
            'suffix' => $slug->suffix,
            'handlers' => $handlers,
        ];

        return $config;
    }
}
