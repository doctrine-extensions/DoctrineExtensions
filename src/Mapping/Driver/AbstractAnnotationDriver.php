<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Mapping\Driver;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Deprecations\Deprecation;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;

/**
 * This is an abstract class to implement common functionality
 * for extension annotation mapping drivers.
 *
 * @author Derek J. Lambert <dlambert@dereklambert.com>
 */
abstract class AbstractAnnotationDriver implements AttributeDriverInterface
{
    /**
     * Annotation reader instance
     *
     * @var Reader|AttributeReader|object
     *
     * @todo Remove the support for the `object` type in the next major release.
     */
    protected $reader;

    /**
     * Original driver if it is available
     *
     * @var MappingDriver
     */
    protected $_originalDriver;

    /**
     * List of types which are valid for extension
     *
     * @var string[]
     */
    protected $validTypes = [];

    /**
     * Set the annotation reader instance
     *
     * When originally implemented, `Doctrine\Common\Annotations\Reader` was not available,
     * therefore this method may accept any object implementing these methods from the interface:
     *
     *     getClassAnnotations([reflectionClass])
     *     getClassAnnotation([reflectionClass], [name])
     *     getPropertyAnnotations([reflectionProperty])
     *     getPropertyAnnotation([reflectionProperty], [name])
     *
     * @param Reader|AttributeReader|object $reader
     *
     * @return void
     *
     * @note Providing any object is deprecated, as of 4.0 an {@see AttributeReader} will be required
     */
    public function setAnnotationReader($reader)
    {
        if ($reader instanceof Reader) {
            Deprecation::trigger(
                'gedmo/doctrine-extensions',
                'https://github.com/doctrine-extensions/DoctrineExtensions/pull/2772',
                'Annotations support is deprecated, migrate your application to use attributes and pass an instance of %s to the %s() method instead.',
                AttributeReader::class,
                __METHOD__
            );
        } elseif (!$reader instanceof AttributeReader) {
            Deprecation::trigger(
                'gedmo/doctrine-extensions',
                'https://github.com/doctrine-extensions/DoctrineExtensions/pull/2558',
                'Providing an annotation reader which does not implement %s or is not an instance of %s to %s() is deprecated.',
                Reader::class,
                AttributeReader::class,
                __METHOD__
            );
        }

        $this->reader = $reader;
    }

    /**
     * Passes in the mapping read by original driver
     *
     * @param MappingDriver $driver
     *
     * @return void
     */
    public function setOriginalDriver($driver)
    {
        $this->_originalDriver = $driver;
    }

    /**
     * @param ClassMetadata<object> $meta
     *
     * @return \ReflectionClass<object>
     */
    public function getMetaReflectionClass($meta)
    {
        return $meta->getReflectionClass();
    }

    /**
     * @param ClassMetadata<object> $meta
     * @param array<string, mixed>  $config
     *
     * @return void
     */
    public function validateFullMetadata(ClassMetadata $meta, array $config)
    {
    }

    /**
     * Checks if $field type is valid
     *
     * @param ClassMetadata<object> $meta
     * @param string                $field
     *
     * @return bool
     */
    protected function isValidField($meta, $field)
    {
        $mapping = $meta->getFieldMapping($field);

        return $mapping && in_array($mapping->type ?? $mapping['type'], $this->validTypes, true);
    }

    /**
     * Try to find out related class name out of mapping
     *
     * @param ClassMetadata<object> $metadata the mapped class metadata
     * @param string                $name     the related object class name
     *
     * @return string related class name or empty string if does not exist
     *
     * @phpstan-param class-string|string $name
     *
     * @phpstan-return class-string|''
     */
    protected function getRelatedClassName($metadata, $name)
    {
        if (class_exists($name) || interface_exists($name)) {
            return $name;
        }
        $refl = $metadata->getReflectionClass();
        $ns = $refl->getNamespaceName();
        $className = $ns.'\\'.$name;

        return class_exists($className) ? $className : '';
    }
}
