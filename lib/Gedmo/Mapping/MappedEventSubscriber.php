<?php

namespace Gedmo\Mapping;

use Gedmo\Mapping\ExtensionMetadataFactory,
    Doctrine\Common\EventSubscriber,
    Doctrine\Common\Persistence\ObjectManager,
    Doctrine\Common\Persistence\Mapping\ClassMetadata,
    Doctrine\Common\ClassLoader,
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
    protected $configurations = array();

    /**
     * ExtensionMetadataFactory used to read the extension
     * metadata through the extension drivers
     *
     * @var Gedmo\Mapping\ExtensionMetadataFactory
     */
    private $extensionMetadataFactory;

    /**
     * List of event adapters used for this listener
     *
     * @var array
     */
    private $adapters = array();

    /**
     * Get an event adapter to handle event specific
     * methods
     *
     * @param EventArgs $args
     * @throws \Gedmo\Exception\InvalidArgumentException - if event is not recognized
     * @return \Gedmo\Mapping\Event\AdapterInterface
     */
    protected function getEventAdapter(EventArgs $args)
    {
        $class = get_class($args);
        if (preg_match('@Doctrine\\\([^\\\]+)@', $class, $m) && in_array($m[1], array('ODM', 'ORM'))) {
            if (!isset($this->adapters[$m[1]])) {
                $adapterClass = $this->getNamespace() . '\\Mapping\\Event\\Adapter\\' . $m[1];
                if (!class_exists($adapterClass)) {
                    $adapterClass = 'Gedmo\\Mapping\\Event\\Adapter\\'.$m[1];
                }
                $this->adapters[$m[1]] = new $adapterClass;
            }
            $this->adapters[$m[1]]->setEventArgs($args);
            return $this->adapters[$m[1]];
        } else {
            throw new \Gedmo\Exception\InvalidArgumentException('Event mapper does not support event arg class: '.$class);
        }
    }

    /**
     * Get the configuration for specific object class
     * if cache driver is present it scans it also
     *
     * @param ObjectManager $objectManager
     * @param string $class
     * @return array
     */
    public function getConfiguration(ObjectManager $objectManager, $class) {
        $config = array();
        if (isset($this->configurations[$class])) {
            $config = $this->configurations[$class];
        } else {
            $cacheDriver = $objectManager->getMetadataFactory()->getCacheDriver();
            $cacheId = ExtensionMetadataFactory::getCacheId($class, $this->getNamespace());
            if ($cacheDriver && ($cached = $cacheDriver->fetch($cacheId)) !== false) {
                $this->configurations[$class] = $cached;
                $config = $cached;
            }
        }
        return $config;
    }

    /**
     * Get extended metadata mapping reader
     *
     * @param ObjectManager $objectManager
     * @return Gedmo\Mapping\ExtensionMetadataFactory
     */
    public function getExtensionMetadataFactory(ObjectManager $objectManager)
    {
        if (null === $this->extensionMetadataFactory) {
            $this->extensionMetadataFactory = new ExtensionMetadataFactory(
                $objectManager,
                $this->getNamespace()
            );
        }
        return $this->extensionMetadataFactory;
    }

    /**
     * Scans the objects for extended annotations
     * event subscribers must subscribe to loadClassMetadata event
     *
     * @param ObjectManager $objectManager
     * @param ClassMetadata $metadata
     * @return void
     */
    public function loadMetadataForObjectClass(ObjectManager $objectManager, ClassMetadata $metadata)
    {
        $factory = $this->getExtensionMetadataFactory($objectManager);
        $config = $factory->getExtensionMetadata($metadata);
        if ($config) {
            $this->configurations[$metadata->name] = $config;
        }
    }

    /**
     * Get the namespace of extension event subscriber.
     * used for cache id of extensions also to know where
     * to find Mapping drivers and event adapters
     *
     * @return string
     */
    abstract protected function getNamespace();
}