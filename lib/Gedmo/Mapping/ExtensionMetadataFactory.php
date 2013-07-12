<?php

namespace Gedmo\Mapping;

use Doctrine\Common\Persistence\Mapping\Driver\DefaultFileLocator;
use Doctrine\Common\Persistence\Mapping\Driver\SymfonyFileLocator;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Version as CommonLibVer;
use Gedmo\Mapping\Driver\File as FileDriver;
use Gedmo\Mapping\Driver\AnnotationDriverInterface;
use Doctrine\ORM\Tools\DisconnectedClassMetadataFactory;
use Gedmo\Exception\RuntimeException;

/**
 * The extension metadata factory is responsible for extension driver
 * initialization and fully reading the extension metadata
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class ExtensionMetadataFactory
{
    /**
     * Extension driver
     * @var \Gedmo\Mapping\Driver
     */
    protected $driver;

    /**
     * Object manager, entity or document
     * @var object
     */
    protected $om;

    /**
     * Extension namespace
     *
     * @var string
     */
    protected $extensionNamespace;

    /**
     * Custom annotation reader
     *
     * @var object
     */
    protected $annotationReader;

    /**
     * Initializes extension driver
     *
     * @param ObjectManager $om
     * @param string $extensionNamespace
     * @param object $annotationReader
     */
    public function __construct(ObjectManager $om, $extensionNamespace, $annotationReader)
    {
        $this->om = $om;
        $this->annotationReader = $annotationReader;
        $this->extensionNamespace = $extensionNamespace;
        $this->driver = $this->getDriver($om->getConfiguration()->getMetadataDriverImpl());
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
        $cmf = $this->om->getMetadataFactory();
        $useObjectName = $meta->name;
        // collect metadata from inherited classes
        if (null !== $meta->reflClass) {
            foreach (array_reverse(class_parents($meta->name)) as $parentClass) {
                // read only inherited mapped classes
                if ($cmf->hasMetadataFor($parentClass)) {
                    $class = $this->om->getClassMetadata($parentClass);
                    $this->driver->readExtendedMetadata($class, $config);
                    $isBaseInheritanceLevel = !$class->isInheritanceTypeNone()
                        && !$class->parentClasses
                        && $config
                    ;
                    if ($isBaseInheritanceLevel) {
                        $useObjectName = $class->name;
                    }
                }
            }
            $this->driver->readExtendedMetadata($meta, $config);
        }
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
     * Get the initialized extension driver
     *
     * @return \Gedmo\Mapping\Driver
     */
    public function getExtensionDriver()
    {
        return $this->driver;
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
     * @throws \Gedmo\Exception\RuntimeException if driver was not found in extension
     * @return \Gedmo\Mapping\Driver
     */
    protected function getDriver($omDriver)
    {
        $driver = null;
        $className = get_class($omDriver);
        $driverName = substr($className, strrpos($className, '\\') + 1);
        if ($omDriver instanceof MappingDriverChain || $driverName == 'DriverChain') {
            $driver = new Driver\Chain();
            foreach ($omDriver->getDrivers() as $namespace => $nestedOmDriver) {
                $driver->addDriver($this->getDriver($nestedOmDriver), $namespace);
            }
            if (version_compare(CommonLibVer::VERSION, '2.3.0', '>=') && $omDriver->getDefaultDriver() !== null) {
                $driver->setDefaultDriver($this->getDriver($omDriver->getDefaultDriver()));
            }
        } else {
            $driverName = substr($driverName, 0, strpos($driverName, 'Driver'));
            $isSimplified = false;
            if (substr($driverName, 0, 10) === 'Simplified') {
                // support for simplified file drivers
                $driverName = substr($driverName, 10);
                $isSimplified = true;
            }
            // create driver instance
            $driverClassName = $this->extensionNamespace . '\Mapping\Driver\\' . $driverName;
            if (!class_exists($driverClassName)) {
                $driverClassName = $this->extensionNamespace . '\Mapping\Driver\Annotation';
                if (!class_exists($driverClassName)) {
                    throw new RuntimeException("Failed to fallback to annotation driver: ({$driverClassName}), extension driver was not found.");
                }
            }
            $driver = new $driverClassName();
            $driver->setOriginalDriver($omDriver);
            if ($driver instanceof FileDriver) {
                /** @var $driver FileDriver */
                if ($omDriver instanceof MappingDriver) {
                    $driver->setLocator($omDriver->getLocator());
                // BC for Doctrine 2.2
                } elseif ($isSimplified) {
                    $driver->setLocator(new SymfonyFileLocator($omDriver->getNamespacePrefixes(), $omDriver->getFileExtension()));
                } else {
                    $driver->setLocator(new DefaultFileLocator($omDriver->getPaths(), $omDriver->getFileExtension()));
                }
            }
            if ($driver instanceof AnnotationDriverInterface) {
                $driver->setAnnotationReader($this->annotationReader);
            }
        }
        return $driver;
    }
}
