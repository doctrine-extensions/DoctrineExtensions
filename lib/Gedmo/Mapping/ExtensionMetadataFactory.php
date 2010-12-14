<?php

namespace Gedmo\Mapping;

use Doctrine\ORM\Mapping\ClassMetadataInfo,
    Doctrine\ORM\EntityManager,
    Doctrine\ORM\Mapping\Driver\Driver as ORMDriver;

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
class ExtensionMetadataFactory
{
    /**
     * Extension driver
     * @var Gedmo\Mapping\Driver
     */
    protected $_driver;
    
    /**
     * Entity manager
     * @var Doctrine\ORM\EntityManager
     */
    private $_em;
    
    /**
     * Extension namespace
     * @var string
     */
    private $_extensionNamespace;
    
    /**
     * Initializes extension driver
     * 
     * @param EntityManager $em
     * @param string $extensionNamespace
     */
    public function __construct(EntityManager $em, $extensionNamespace)
    {
        $this->_em = $em;
        $this->_extensionNamespace = $extensionNamespace;
        $ormDriver = $em->getConfiguration()->getMetadataDriverImpl();
        $this->_driver = $this->_getDriver($ormDriver);
    }
    
    /**
     * Reads extension metadata
     * 
     * @param ClassMetadataInfo $meta
     * @return array - the metatada configuration
     */
    public function getExtensionMetadata(ClassMetadataInfo $meta)
    {
        if ($meta->isMappedSuperclass) {
            return; // ignore mappedSuperclasses for now
        }
        $config = array();
        // collect metadata from inherited classes
        foreach (array_reverse(class_parents($meta->name)) as $parentClass) {
            // read only inherited mapped classes
            if ($this->_em->getMetadataFactory()->hasMetadataFor($parentClass)) {
                $this->_driver->readExtendedMetadata($this->_em->getClassMetadata($parentClass), $config);
            }
        }
        $this->_driver->readExtendedMetadata($meta, $config);
        $this->_driver->validateFullMetadata($meta, $config);
        if ($config) {
            // cache the metadata
            $cacheId = self::getCacheId($meta->name, $this->_extensionNamespace);
            if ($cacheDriver = $this->_em->getMetadataFactory()->getCacheDriver()) {
                $cacheDriver->save($cacheId, $config, null);
            }
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
     * @param ORMDriver $ormDriver
     * @throws DriverException if driver was not found in extension
     * @return Gedmo\Mapping\Driver
     */
    private function _getDriver(ORMDriver $ormDriver)
    {
        $driver = null;
        if ($ormDriver instanceof \Doctrine\ORM\Mapping\Driver\DriverChain) {
            $driver = new Driver\Chain();
            foreach ($ormDriver->getDrivers() as $namespace => $nestedOrmDriver) {
                $driver->addDriver($this->_getDriver($nestedOrmDriver), $namespace);
            }
        } else {
            $className = get_class($ormDriver);
            $driverName = substr($className, strrpos($className, '\\') + 1);
            $driverName = substr($driverName, 0, strpos($driverName, 'Driver'));
            // create driver instance
            $driverClassName = $this->_extensionNamespace . '\Mapping\Driver\\' . $driverName;
            if (!class_exists($driverClassName)) {
                throw DriverException::extensionDriverNotSupported($driverClassName, $driverName);
            }
            $driver = new $driverClassName();
            if ($driver instanceof \Gedmo\Mapping\Driver\File) {
                $driver->setPaths($ormDriver->getPaths());
            }
        }
        return $driver;
    }
}