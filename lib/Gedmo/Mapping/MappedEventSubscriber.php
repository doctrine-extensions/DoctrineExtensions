<?php

namespace Gedmo\Mapping;

use Gedmo\Mapping\ExtensionMetadataFactory,
    Doctrine\ORM\EntityManager,
    Doctrine\ORM\Event\LoadClassMetadataEventArgs;

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
abstract class MappedEventSubscriber
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
     * Get the namespace of extension event subscriber.
     * used for loading mapping drivers and cache of
     * extensions
     * 
     * @return string
     */
    abstract protected function _getNamespace();
    
	/**
     * Get the configuration for specific object class
     * if cache driver is present it scans it also
     * 
     * @param EntityManager $em
     * @param string $class
     * @return array
     */
    public function getConfiguration(EntityManager $em, $class) {
        $config = array();
        if (isset($this->_configurations[$class])) {
            $config = $this->_configurations[$class];
        } else {
            $cacheDriver = $em->getMetadataFactory()->getCacheDriver();
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
     * @param EntityManager $em
     * @return Gedmo\Mapping\ExtensionMetadataFactory
     */
    public function getExtensionMetadataFactory(EntityManager $em)
    {
        if (null === $this->_extensionMetadataFactory) {
            $this->_extensionMetadataFactory = new ExtensionMetadataFactory($em, $this->_getNamespace());
        }
        return $this->_extensionMetadataFactory;
    }
    
	/**
     * Scans the objects for extended annotations
     * event subscribers must subscribe to loadClassMetadata event
     * 
     * @param LoadClassMetadataEventArgs $eventArgs
     * @return void
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        $meta = $eventArgs->getClassMetadata();
        $em = $eventArgs->getEntityManager();
        $factory = $this->getExtensionMetadataFactory($em);
        $config = $factory->getExtensionMetadata($meta);
        if ($config) {
            $this->_configurations[$meta->name] = $config;
        }
    }
}