<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Mapping;

use Doctrine\Bundle\DoctrineBundle\Mapping\MappingDriver as DoctrineBundleMappingDriver;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\Driver\DefaultFileLocator;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\Persistence\Mapping\Driver\SymfonyFileLocator;
use Doctrine\Persistence\ObjectManager;
use Gedmo\Mapping\Driver\AnnotationDriverInterface;
use Gedmo\Mapping\Driver\AttributeAnnotationReader;
use Gedmo\Mapping\Driver\AttributeDriverInterface;
use Gedmo\Mapping\Driver\AttributeReader;
use Gedmo\Mapping\Driver\File as FileDriver;
use Psr\Cache\CacheItemPoolInterface;

/**
 * The extension metadata factory is responsible for extension driver
 * initialization and fully reading the extension metadata
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
class ExtensionMetadataFactory
{
    /**
     * Extension driver
     *
     * @var \Gedmo\Mapping\Driver
     */
    protected $driver;

    /**
     * Object manager, entity or document
     *
     * @var object
     */
    protected $objectManager;

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
     * @var CacheItemPoolInterface|null
     */
    private $cacheItemPool;

    public function __construct(ObjectManager $objectManager, string $extensionNamespace, object $annotationReader, ?CacheItemPoolInterface $cacheItemPool = null)
    {
        $this->objectManager = $objectManager;
        $this->annotationReader = $annotationReader;
        $this->extensionNamespace = $extensionNamespace;
        $omDriver = $objectManager->getConfiguration()->getMetadataDriverImpl();
        $this->driver = $this->getDriver($omDriver);
        $this->cacheItemPool = $cacheItemPool;
    }

    /**
     * Reads extension metadata
     *
     * @param ClassMetadata $meta
     *
     * @return array the metatada configuration
     */
    public function getExtensionMetadata($meta)
    {
        if ($meta->isMappedSuperclass) {
            return []; // ignore mappedSuperclasses for now
        }
        $config = [];
        $cmf = $this->objectManager->getMetadataFactory();
        $useObjectName = $meta->getName();
        // collect metadata from inherited classes
        if (null !== $meta->reflClass) {
            foreach (array_reverse(class_parents($meta->getName())) as $parentClass) {
                // read only inherited mapped classes
                if ($cmf->hasMetadataFor($parentClass)) {
                    $class = $this->objectManager->getClassMetadata($parentClass);
                    $this->driver->readExtendedMetadata($class, $config);
                    $isBaseInheritanceLevel = !$class->isInheritanceTypeNone()
                        && !$class->parentClasses
                        && $config
                    ;
                    if ($isBaseInheritanceLevel) {
                        $useObjectName = $class->getName();
                    }
                }
            }
            $this->driver->readExtendedMetadata($meta, $config);
        }
        if ($config) {
            $config['useObjectClass'] = $useObjectName;
        }

        $this->storeConfiguration($meta->getName(), $config);

        return $config;
    }

    /**
     * Get the cache id
     *
     * @param string $className
     * @param string $extensionNamespace
     *
     * @return string
     */
    public static function getCacheId($className, $extensionNamespace)
    {
        return str_replace('\\', '_', $className).'_$'.strtoupper(str_replace('\\', '_', $extensionNamespace)).'_CLASSMETADATA';
    }

    /**
     * Get the extended driver instance which will
     * read the metadata required by extension
     *
     * @param MappingDriver $omDriver
     *
     * @throws \Gedmo\Exception\RuntimeException if driver was not found in extension
     *
     * @return \Gedmo\Mapping\Driver
     */
    protected function getDriver($omDriver)
    {
        if ($omDriver instanceof DoctrineBundleMappingDriver) {
            $omDriver = $omDriver->getDriver();
        }

        $driver = null;
        $className = get_class($omDriver);
        $driverName = substr($className, strrpos($className, '\\') + 1);
        if ($omDriver instanceof MappingDriverChain || 'DriverChain' === $driverName) {
            $driver = new Driver\Chain();
            foreach ($omDriver->getDrivers() as $namespace => $nestedOmDriver) {
                $driver->addDriver($this->getDriver($nestedOmDriver), $namespace);
            }
            if (null !== $omDriver->getDefaultDriver()) {
                $driver->setDefaultDriver($this->getDriver($omDriver->getDefaultDriver()));
            }
        } else {
            $driverName = substr($driverName, 0, strpos($driverName, 'Driver'));
            $isSimplified = false;
            if ('Simplified' === substr($driverName, 0, 10)) {
                // support for simplified file drivers
                $driverName = substr($driverName, 10);
                $isSimplified = true;
            }
            // create driver instance
            $driverClassName = $this->extensionNamespace.'\Mapping\Driver\\'.$driverName;
            if (!class_exists($driverClassName)) {
                $driverClassName = $this->extensionNamespace.'\Mapping\Driver\Annotation';
                if (!class_exists($driverClassName)) {
                    throw new \Gedmo\Exception\RuntimeException("Failed to fallback to annotation driver: ({$driverClassName}), extension driver was not found.");
                }
            }
            $driver = new $driverClassName();
            $driver->setOriginalDriver($omDriver);
            if ($driver instanceof FileDriver) {
                if ($omDriver instanceof MappingDriver) {
                    $driver->setLocator($omDriver->getLocator());
                // BC for Doctrine 2.2
                } elseif ($isSimplified) {
                    $driver->setLocator(new SymfonyFileLocator($omDriver->getNamespacePrefixes(), $omDriver->getFileExtension()));
                } else {
                    $driver->setLocator(new DefaultFileLocator($omDriver->getPaths(), $omDriver->getFileExtension()));
                }
            }

            if ($driver instanceof AttributeDriverInterface) {
                $driver->setAnnotationReader(new AttributeAnnotationReader(new AttributeReader(), $this->annotationReader));
            } elseif ($driver instanceof AnnotationDriverInterface) {
                $driver->setAnnotationReader($this->annotationReader);
            }
        }

        return $driver;
    }

    private function storeConfiguration(string $className, array $config): void
    {
        if (null === $this->cacheItemPool) {
            return;
        }

        // Cache the result, even if it's empty, to prevent re-parsing non-existent annotations.
        $cacheId = self::getCacheId($className, $this->extensionNamespace);

        $item = $this->cacheItemPool->getItem($cacheId);

        $this->cacheItemPool->save($item->set($config));
    }
}
