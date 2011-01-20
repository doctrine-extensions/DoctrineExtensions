<?php

namespace Gedmo\Mapping;

use Gedmo\Mapping\ExtensionMetadataFactory,
    Doctrine\Common\EventSubscriber,
    Doctrine\Common\EventArgs;

/**
 * This is extension of event subscriber class and is
 * used specifically for handling the extension metadata
 * mapping for extensions.
 * 
 * It dries up some reusable code which is common for
 * all extensions who mapps additional metadata through
 * extended drivers
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Mapping
 * @subpackage MappedEventSubscriber
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
abstract class MappedEventSubscriber implements EventSubscriber
{    
    /**
     * List of cached object configurations
     *  
     * @var array
     */
    protected $_configurations = array();
    
    /**
     * ExtensionMetadataFactory used to read the extension
     * metadata through the extension drivers
     * 
     * @var Gedmo\Mapping\ExtensionMetadataFactory
     */
    protected $_extensionMetadataFactory = null;
    
	/**
     * Get the configuration for specific object class
     * if cache driver is present it scans it also
     * 
     * @param object $objectManager
     * @param string $class
     * @return array
     */
    public function getConfiguration($objectManager, $class) {
        $config = array();
        if (isset($this->_configurations[$class])) {
            $config = $this->_configurations[$class];
        } else {
            $cacheDriver = $objectManager->getMetadataFactory()->getCacheDriver();
            $cacheId = ExtensionMetadataFactory::getCacheId($class, $this->_getNamespace());
            if ($cacheDriver && ($cached = $cacheDriver->fetch($cacheId)) !== false) {
                $this->_configurations[$class] = $cached;
                $config = $cached;
            }
        }
        return $config;
    }
    
	/**
     * Get extended metadata mapping reader
     * 
     * @param object $objectManager
     * @return Gedmo\Mapping\ExtensionMetadataFactory
     */
    public function getExtensionMetadataFactory($objectManager)
    {
        if (null === $this->_extensionMetadataFactory) {
            $this->_extensionMetadataFactory = new ExtensionMetadataFactory($objectManager, $this->_getNamespace());
        }
        return $this->_extensionMetadataFactory;
    }
    
    /**
     * Scans the objects for extended annotations
     * event subscribers must subscribe to loadClassMetadata event
     * 
     * @param object $objectManager
     * @param object $metadata
     * @return void
     */
    public function loadMetadataForObjectClass($objectManager, $metadata)
    {
        $factory = $this->getExtensionMetadataFactory($objectManager);
        $config = $factory->getExtensionMetadata($metadata);
        if ($config) {
            $this->_configurations[$metadata->name] = $config;
        }
    }
    
    /**
     * Get the namespace of extension event subscriber.
     * used for loading mapping drivers and cache of
     * extensions
     * 
     * @return string
     */
    abstract protected function _getNamespace();
}