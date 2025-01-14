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
use Gedmo\Sluggable\Handler\SlugHandlerInterface;

/**
 * Mapping driver for the sluggable extension which reads extended metadata from attributes on a sluggable class.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @internal
 */
class Attribute extends AbstractAnnotationDriver
{
    /**
     * Mapping object configuring a field which should have a slug computed.
     */
    public const SLUG = Slug::class;

    /**
     * Mapping object configuring a slug handler for a sluggable field.
     */
    public const HANDLER = SlugHandler::class;

    /**
     * Mapping object configuring an option for a slug handler.
     *
     * @deprecated since gedmo/doctrine-extensions 3.18, will be removed in version 4.0.
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
        'ascii_string',
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
     * @param ClassMetadata<object> $meta
     *
     * @return array<class-string<SlugHandlerInterface>, SlugHandler[]>
     */
    protected function getSlugHandlers(\ReflectionProperty $property, Slug $slug, ClassMetadata $meta): array
    {
        /** @var list<SlugHandler>|null $attributeHandlers */
        $attributeHandlers = $this->reader->getPropertyAnnotation($property, self::HANDLER);

        if (null === $attributeHandlers) {
            return [];
        }

        $handlers = [];

        foreach ($attributeHandlers as $handler) {
            if (!class_exists($handler->class)) {
                throw new InvalidMappingException("SlugHandler class: {$handler->class} should be a valid class name in entity - {$meta->getName()}");
            }

            /** @var class-string<SlugHandlerInterface> $class */
            $class = $handler->class;

            $handlers[$class] = [];

            foreach ($handler->options as $name => $value) {
                $handlers[$class][$name] = $value;
            }

            $class::validate($handlers[$class], $meta);
        }

        return $handlers;
    }

    /**
     * @param ClassMetadata<object> $meta
     * @param array<string, mixed>  $config
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

        if ([] !== $meta->getIdentifier() && $meta->isIdentifier($fieldName) && !(bool) $slug->unique) {
            throw new InvalidMappingException("Identifier field - [{$fieldName}] slug must be unique in order to maintain primary key in class - {$meta->getName()}");
        }

        if (false === $slug->unique && $slug->unique_base) {
            throw new InvalidMappingException("Slug annotation [unique_base] can not be set if unique is unset or 'false'");
        }

        if (false === $slug->unique && $slug->uniqueOverTranslations) {
            throw new InvalidMappingException("Slug annotation [uniqueOverTranslations] can not be set if unique is unset or 'false'");
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
            'uniqueOverTranslations' => $slug->uniqueOverTranslations,
        ];

        return $config;
    }
}
