<?php

namespace Gedmo\Mapping;

use Doctrine\Common\Persistence\ObjectManager;
use Gedmo\Mapping\Driver\File as FileDriver;
use Gedmo\Mapping\Driver\AnnotationDriverInterface;
use Doctrine\ORM\Tools\DisconnectedClassMetadataFactory;

/**
 * The extension metadata factory is responsible for extension driver
 * initialization and fully reading the extension metadata
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Mapping
 * @subpackage ExtensionMetadataFactory
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class ExtensionMetadataFactory
{
    /**
     * Extension driver
     * @var Gedmo\Mapping\Driver
     */
    private $driver;

    /**
     * Object manager, entity or document
     * @var object
     */
    private $objectManager;

    /**
     * Extension namespace
     *
     * @var string
     */
    private $extensionNamespace;

    /**
     * Custom annotation reader
     *
     * @var object
     */
    private $annotationReader;

    /**
     * Initializes extension driver
     *
     * @param ObjectManager $objectManager
     * @param string $extensionNamespace
     * @param object $annotationReader
     */
    public function __construct(ObjectManager $objectManager, $extensionNamespace, $annotationReader)
    {
        $this->objectManager = $objectManager;
        $this->annotationReader = $annotationReader;
        $this->extensionNamespace = $extensionNamespace;
        $omDriver = $objectManager->getConfiguration()->getMetadataDriverImpl();
        $this->driver = $this->getDriver($omDriver);
    }

    /**
     * Reads extension metadata
     *
     * @param object $meta
     * @return array - the metatada configuration
     */
    public function getExtensionMetadata($meta)
    {
        if ($meta->isMappedSuperclass) {
            return; // ignore mappedSuperclasses for now
        }
        $config = array();
        $cmf = $this->objectManager->getMetadataFactory();
        $useObjectName = $meta->name;
        // collect metadata from inherited classes
        if (!$cmf instanceof DisconnectedClassMetadataFactory) {
            foreach (array_reverse(class_parents($meta->name)) as $parentClass) {
                // read only inherited mapped classes
                if ($cmf->hasMetadataFor($parentClass)) {
                    $class = $this->objectManager->getClassMetadata($parentClass);
                    $this->driver->readExtendedMetadata($class, $config);
                    if (!$class->isInheritanceTypeNone() && $config) {
                        $useObjectName = $class->name;
                    }
                }
            }
        }
        $this->driver->readExtendedMetadata($meta, $config);
        if ($config) {
            $config['useObjectClass'] = $useObjectName;
        }

        // cache the metadata (even if it's empty)
        // caching empty metadata will prevent re-parsing non-existent annotations
        $cacheId = self::getCacheId($meta->name, $this->extensionNamespace);
        if ($cacheDriver = $cmf->getCacheDriver()) {
            $cacheDriver->save($cacheId, $config, null);
        }

        return $config;
    }

    /**
     * Get the cache id
     *
     * @param string $className
     * @param string $extensionNamespace
     * @return string
     */
    public static function getCacheId($className, $extensionNamespace)
    {
        return $className . '\\$' . strtoupper(str_replace('\\', '_', $extensionNamespace)) . '_CLASSMETADATA';
    }

    /**
     * Get the extended driver instance which will
     * read the metadata required by extension
     *
     * @param object $omDriver
     * @throws DriverException if driver was not found in extension
     * @return Gedmo\Mapping\Driver
     */
    private function getDriver($omDriver)
    {
        $driver = null;
        $className = get_class($omDriver);
        $driverName = substr($className, strrpos($className, '\\') + 1);
        if ($driverName == 'DriverChain') {
            $driver = new Driver\Chain();
            foreach ($omDriver->getDrivers() as $namespace => $nestedOmDriver) {
                $driver->addDriver($this->getDriver($nestedOmDriver), $namespace);
            }
        } else {
            $driverName = substr($driverName, 0, strpos($driverName, 'Driver'));
            if (substr($driverName, 0, 10) === 'Simplified') {
                // support for simplified file drivers
                $driverName = substr($driverName, 10);
            }
            // create driver instance
            $driverClassName = $this->extensionNamespace . '\Mapping\Driver\\' . $driverName;
            if (!class_exists($driverClassName)) {
                $driverClassName = $this->extensionNamespace . '\Mapping\Driver\Annotation';
                if (!class_exists($driverClassName)) {
                    throw new \Gedmo\Exception\RuntimeException("Failed to fallback to annotation driver: ({$driverClassName}), extension driver was not found.");
                }
            }
            $driver = new $driverClassName();
            $driver->setOriginalDriver($omDriver);
            if ($driver instanceof FileDriver) {
                $driver->setPaths($omDriver->getPaths());
                $driver->setExtension($omDriver->getFileExtension());
            }
            if ($driver instanceof AnnotationDriverInterface) {
                $driver->setAnnotationReader($this->annotationReader);
            }
        }
        return $driver;
    }
}