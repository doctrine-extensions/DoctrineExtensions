<?php

namespace Gedmo\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;

/**
 * This is an abstract class to implement common functionality
 * for extension annotation mapping drivers.
 *
 * @author     Derek J. Lambert <dlambert@dereklambert.com>
 * @license    MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
abstract class AbstractAnnotationDriver implements AnnotationDriverInterface
{
    /**
     * Annotation reader instance
     *
     * @var object
     */
    protected $reader;

    /**
     * Original driver if it is available
     */
    protected $_originalDriver = null;

    /**
     * List of types which are valid for extension
     *
     * @var array
     */
    protected $validTypes = array();

    /**
     * {@inheritDoc}
     */
    public function setAnnotationReader($reader)
    {
        $this->reader = $reader;
    }

    /**
     * Passes in the mapping read by original driver
     *
     * @param object $driver
     */
    public function setOriginalDriver($driver)
    {
        $this->_originalDriver = $driver;
    }

    /**
     * @param object $meta
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
            $class = new \ReflectionClass($meta->name);
        }

        return $class;
    }

    /**
     * Checks if $field type is valid
     *
     * @param object $meta
     * @param string $field
     *
     * @return boolean
     */
    protected function isValidField($meta, $field)
    {
        $mapping = $meta->getFieldMapping($field);

        return $mapping && in_array($mapping['type'], $this->validTypes);
    }

    /**
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetaData $meta
     * @param array                                              $config
     */
    public function validateFullMetadata(ClassMetadata $meta, array $config)
    {
    }

    /**
     * Try to find out related class name out of mapping
     *
     * @param $metadata - the mapped class metadata
     * @param $name - the related object class name
     * @return string - related class name or empty string if does not exist
     */
    protected function getRelatedClassName($metadata, $name)
    {
        if (class_exists($name) || interface_exists($name)) {
            return $name;
        }
        $refl = $metadata->getReflectionClass();
        $ns = $refl->getNamespaceName();
        $className = $ns . '\\' . $name;
        return class_exists($className) ? $className : '';
    }
}
