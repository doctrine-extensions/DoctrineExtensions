<?php

namespace Gedmo\Mapping;

use Doctrine\Common\Persistence\Mapping\Driver\AnnotationDriver as PersistenceAnnotationDriver;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Gedmo\Mapping\Driver\AnnotationDriver;
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
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata $meta
     *
     * @return \Gedmo\Mapping\ExtensionMetadataInterface - extension metadata container
     */
    public function getExtensionMetadata(ClassMetadata $meta)
    {
        if ($meta->isMappedSuperclass) {
            return; // ignore mappedSuperclasses for now
        }
        // create extension metadata instance
        $exm = $this->getNewExtensionMetadataInstance();
        $cmf = $this->om->getMetadataFactory();
        // collect metadata from inherited classes
        if (null !== $meta->reflClass) {
            foreach (array_reverse(class_parents($meta->name)) as $parentClass) {
                // read only inherited mapped classes
                if ($cmf->hasMetadataFor($parentClass)) {
                    $parentClassMeta = $this->om->getClassMetadata($parentClass);
                    $this->driver->loadExtensionMetadata($parentClassMeta, $exm);
                }
            }
            $this->driver->loadExtensionMetadata($meta, $exm);
        }
        $exm->validate($this->om, $meta);
        // cache the metadata (even if it's empty)
        // caching empty metadata will prevent re-parsing non-existent annotations
        $cacheId = self::getCacheId($meta->name, $this->extensionNamespace);
        if ($cacheDriver = $cmf->getCacheDriver()) {
            $cacheDriver->save($cacheId, $exm->toArray(), null);
        }
        return $exm;
    }

    /**
     * Get extension metadata instance for this specific
     * factory which is based on listener
     *
     * @return \Gedmo\Mapping\ExtensionMetadataInterface
     */
    public function getNewExtensionMetadataInstance()
    {
        $exname = substr($this->extensionNamespace, strrpos($this->extensionNamespace, '\\') + 1);
        $exm = $this->extensionNamespace . '\Mapping\\' . $exname . 'Metadata';
        if (!class_exists($exm)) {
            throw new RuntimeException("Extension metadata class: [{$exm}] does not exists or could not be autoloaded for extension: {$exname}");
        }
        return new $exm;
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
     * read the metadata required by extensions. Each
     * driver is generated once for specific object manager
     *
     * @param \Doctrine\Common\Persistence\Mapping\Driver\MappingDriver $omDriver
     * @throws \Gedmo\Exception\RuntimeException if driver was not found in extension
     * @return \Gedmo\Mapping\Driver
     */
    protected function getDriver(MappingDriver $omDriver)
    {
        $driver = null;
        $className = get_class($omDriver);
        $driverName = substr($className, strrpos($className, '\\') + 1);
        if ($omDriver instanceof MappingDriverChain || $driverName == 'DriverChain') {
            $driver = new MappingDriverChain;
            foreach ($omDriver->getDrivers() as $namespace => $nestedOmDriver) {
                $driver->addDriver($this->getDriver($nestedOmDriver), $namespace);
            }
            $driver->setDefaultDriver($this->getDriver($omDriver->getDefaultDriver()));
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
                    throw new RuntimeException("Failed to fallback to annotation driver: ({$driverClassName}), extension driver was not found.");
                }
            }
            $driver = new $driverClassName;
            $driver->setOriginalDriver($omDriver);
            if ($driver instanceof AnnotationDriver) {
                if ($omDriver instanceof PersistenceAnnotationDriver) {
                    $driver->setReader($omDriver->getReader());
                } else {
                    $driver->setReader($this->annotationReader);
                }
            }
        }
        return $driver;
    }
}
