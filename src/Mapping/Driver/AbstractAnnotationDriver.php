<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Mapping\Driver;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;

/**
 * This is an abstract class to implement common functionality
 * for extension annotation mapping drivers.
 *
 * @author Derek J. Lambert <dlambert@dereklambert.com>
 */
abstract class AbstractAnnotationDriver implements AnnotationDriverInterface
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

    public function setAnnotationReader($reader)
    {
        if (!$reader instanceof Reader && !$reader instanceof AttributeReader) {
            trigger_deprecation(
                'gedmo/doctrine-extensions',
                '3.11',
                'Passing an object not implementing "%s" or "%s" as argument 1 to "%s()" is deprecated and'
                .' will throw an "%s" error in version 4.0. Instance of "%s" given.',
                Reader::class,
                AttributeReader::class,
                __METHOD__,
                \TypeError::class,
                get_class($reader)
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
     * @param ClassMetadata $meta
     *
     * @return \ReflectionClass
     */
    public function getMetaReflectionClass($meta)
    {
        $class = $meta->getReflectionClass();
        if (!$class) {
            // based on recent doctrine 2.3.0-DEV maybe will be fixed in some way
            // this happens when running annotation driver in combination with
            // static reflection services. This is not the nicest fix
            $class = new \ReflectionClass($meta->getName());
        }

        return $class;
    }

    /**
     * @return void
     */
    public function validateFullMetadata(ClassMetadata $meta, array $config)
    {
    }

    /**
     * Checks if $field type is valid
     *
     * @param ClassMetadata $meta
     * @param string        $field
     *
     * @return bool
     */
    protected function isValidField($meta, $field)
    {
        $mapping = $meta->getFieldMapping($field);

        return $mapping && in_array($mapping['type'], $this->validTypes, true);
    }

    /**
     * Try to find out related class name out of mapping
     *
     * @param ClassMetadata $metadata the mapped class metadata
     * @param string        $name     the related object class name
     *
     * @return string related class name or empty string if does not exist
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
