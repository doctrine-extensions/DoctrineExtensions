<?php

namespace Gedmo\Mapping;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Annotations\Reader;
use Gedmo\Mapping\ExtensionMetadataFactory;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Gedmo\Exception\InvalidArgumentException;
use Doctrine\Common\Annotations\AnnotationRegistry;

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
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
abstract class MappedEventSubscriber implements EventSubscriber
{
    /**
     * Static List of cached object configurations
     * leaving it static for reasons to look into
     * other listener configuration
     *
     * @var array
     */
    protected static $configurations = array();

    /**
     * Listener name, etc: sluggable
     *
     * @var string
     */
    protected $name;

    /**
     * Filters to ignore during processing
     * Should be set in extended listener class
     *
     * @var array
     */
    protected $ignoredFilters = array();

    /**
     * ExtensionMetadataFactory used to read the extension
     * metadata through the extension drivers
     *
     * @var ExtensionMetadataFactory
     */
    private $extensionMetadataFactory = array();

    /**
     * List of event adapters used for this listener
     *
     * @var array
     */
    private $adapters = array();

    /**
     * Custom annotation reader
     *
     * @TODO: remove if cache driver of metadata is reused
     * @var object
     */
    private $annotationReader;

    /**
     * A list of filters which were disabled by listener
     * to maintain valid state
     *
     * @var array
     */
    private $disabledFilters = array();

    /**
     * @var \Doctrine\Common\Annotations\AnnotationReader
     */
    private static $defaultAnnotationReader;

    /**
     * Constructor
     */
    public function __construct()
    {
        $parts = explode('\\', $this->getNamespace());
        $this->name = end($parts);
    }

    /**
     * Add a filter to ignored list
     *
     * @param string $filterClassName
     */
    public function addFilterToIgnore($filterClassName)
    {
        $this->ignoredFilters[] = $filterClassName;
    }

    /**
     * Disables filters by class name specified in $this->ignoredFilters
     *
     * @param ObjectManager $om
     */
    protected function disableFilters(ObjectManager $om)
    {
        if ($this->ignoredFilters) {
            $filters = $this->getFilterCollectionFromObjectManager($om);
            $enabled = $filters->getEnabledFilters();
            foreach ($enabled as $name => $filter) {
                foreach ($this->ignoredFilters as $filterClassName) {
                    if (is_a($filter, $filterClassName)) {
                        $filters->disable($name);
                        $this->disabledFilters[] = $name;
                        break;
                    }
                }
            }
        }
    }

    /**
     * Checks if $filterClassName filter is enabled
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $om
     * @param String $filterClassName
     * @return boolean
     */
    protected function hasEnabledFilter(ObjectManager $om, $filterClassName)
    {
        $enabled = $this->getFilterCollectionFromObjectManager($om)->getEnabledFilters();
        foreach ($enabled as $name => $filter) {
            if (is_a($filter, $filterClassName)) {
                return true;
            }
        }
        return false;
    }


    /**
     * Enables back previously disabled filters by class name specified in $this->ignoredFilters
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $om
     */
    protected function enableFilters(ObjectManager $om)
    {
        $filters = $this->getFilterCollectionFromObjectManager($om);
        while ($name = array_pop($this->disabledFilters)) {
            $filters->enable($name);
        }
    }

    /**
     * Get extension metadata for specific object class
     * if cache driver is present - it gets scanned first
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $om
     * @param string $className
     * @return \Gedmo\Mapping\ExtensionMetadataInterface
     */
    public function getConfiguration(ObjectManager $om, $className) {
        $exm = null;
        if (isset(self::$configurations[$this->name][$className])) {
            $exm = self::$configurations[$this->name][$className];
        } else {
            $factory = $om->getMetadataFactory();
            $cacheId = ExtensionMetadataFactory::getCacheId($className, $this->getNamespace());
            if (($cacheDriver = $factory->getCacheDriver()) && ($cached = $cacheDriver->fetch($cacheId)) !== false) {
                $exm = $this->getExtensionMetadataFactory($om)->getNewExtensionMetadataInstance();
                self::$configurations[$this->name][$className] = $exm->fromArray($cached);
            } else {
                // re-generate metadata on cache miss
                $this->loadMetadataForObjectClass($om, $factory->getMetadataFor($className));
                if (isset(self::$configurations[$this->name][$className])) {
                    $exm = self::$configurations[$this->name][$className];
                }
            }
        }
        return $exm;
    }

    /**
     * Get extended metadata mapping reader
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $om
     * @return ExtensionMetadataFactory
     */
    public function getExtensionMetadataFactory(ObjectManager $om)
    {
        $oid = spl_object_hash($om);
        if (!isset($this->extensionMetadataFactory[$oid])) {
            if (null === $this->annotationReader) {
                // create default annotation reader for extensions
                $this->annotationReader = $this->getDefaultAnnotationReader($om);
            }
            $this->extensionMetadataFactory[$oid] = new ExtensionMetadataFactory(
                $om,
                $this->getNamespace(),
                $this->annotationReader
            );
        }
        return $this->extensionMetadataFactory[$oid];
    }

    /**
     * Set annotation reader class
     *
     * @param \Doctrine\Common\Annotations\Reader $reader - annotation reader class
     */
    public function setAnnotationReader(Reader $reader)
    {
        $this->annotationReader = $reader;
    }

    /**
     * Scans the objects for extended annotations
     * event subscribers must subscribe to loadClassMetadata event
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $om
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata $metadata
     */
    public function loadMetadataForObjectClass(ObjectManager $om, ClassMetadata $metadata)
    {
        $factory = $this->getExtensionMetadataFactory($om);
        try {
            $exm = $factory->getExtensionMetadata($metadata);
        } catch (\ReflectionException $e) {
            // entity\document generator is running
            $exm = false; // will not store a cached version, to remap later
        }
        if ($exm) {
            self::$configurations[$this->name][$metadata->name] = $exm;
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

    /**
     * Create default annotation reader for extensions
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $om
     *
     * @return \Doctrine\Common\Annotations\AnnotationReader
     */
    private function getDefaultAnnotationReader(ObjectManager $om)
    {
        if (null === self::$defaultAnnotationReader) {
            if (!$cache = $om->getMetadataFactory()->getCacheDriver()) {
                $cache = new ArrayCache;
            }
            AnnotationRegistry::registerAutoloadNamespace('Gedmo\Mapping\Annotation', __DIR__ . '/../../');
            self::$defaultAnnotationReader = new CachedReader(new AnnotationReader, $cache);
        }
        return self::$defaultAnnotationReader;
    }

    /**
     * Retrieves a FilterCollection instance from the given ObjectManager.
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $om
     * @throws \Gedmo\Exception\InvalidArgumentException
     * @return mixed
     */
    private function getFilterCollectionFromObjectManager(ObjectManager $om)
    {
        if (is_callable(array($om, 'getFilters'))) {
            return $om->getFilters();
        } else if (is_callable(array($om, 'getFilterCollection'))) {
            return $om->getFilterCollection();
        }

        throw new InvalidArgumentException("ObjectManager does not support filters");
    }
}
