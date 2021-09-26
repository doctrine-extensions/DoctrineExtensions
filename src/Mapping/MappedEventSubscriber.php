<?php

namespace Gedmo\Mapping;

use function class_exists;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\PsrCachedReader;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Psr6\CacheAdapter;
use Doctrine\Common\EventArgs;
use Doctrine\Common\EventSubscriber;
use Doctrine\Persistence\ObjectManager;
use Gedmo\Mapping\Event\AdapterInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * This is extension of event subscriber class and is
 * used specifically for handling the extension metadata
 * mapping for extensions.
 *
 * It dries up some reusable code which is common for
 * all extensions who maps additional metadata through
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
    protected static $configurations = [];

    /**
     * Listener name, etc: sluggable
     *
     * @var string
     */
    protected $name;

    /**
     * ExtensionMetadataFactory used to read the extension
     * metadata through the extension drivers
     *
     * @var ExtensionMetadataFactory
     */
    private $extensionMetadataFactory = [];

    /**
     * List of event adapters used for this listener
     *
     * @var array
     */
    private $adapters = [];

    /**
     * Custom annotation reader
     *
     * @var object
     */
    private $annotationReader;

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
     * Get an event adapter to handle event specific
     * methods
     *
     * @throws \Gedmo\Exception\InvalidArgumentException - if event is not recognized
     *
     * @return \Gedmo\Mapping\Event\AdapterInterface
     */
    protected function getEventAdapter(EventArgs $args)
    {
        $class = get_class($args);
        if (preg_match('@Doctrine\\\([^\\\]+)@', $class, $m) && in_array($m[1], ['ODM', 'ORM'])) {
            if (!isset($this->adapters[$m[1]])) {
                $adapterClass = $this->getNamespace().'\\Mapping\\Event\\Adapter\\'.$m[1];
                if (!class_exists($adapterClass)) {
                    $adapterClass = 'Gedmo\\Mapping\\Event\\Adapter\\'.$m[1];
                }
                $this->adapters[$m[1]] = new $adapterClass();
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
     * @param string $class
     *
     * @return array
     */
    public function getConfiguration(ObjectManager $objectManager, $class)
    {
        $config = [];
        if (isset(self::$configurations[$this->name][$class])) {
            $config = self::$configurations[$this->name][$class];
        } else {
            $factory = $objectManager->getMetadataFactory();
            $cacheDriver = $factory->getCacheDriver();
            if ($cacheDriver) {
                $cacheId = ExtensionMetadataFactory::getCacheId($class, $this->getNamespace());
                if (false !== ($cached = $cacheDriver->fetch($cacheId))) {
                    self::$configurations[$this->name][$class] = $cached;
                    $config = $cached;
                } else {
                    // re-generate metadata on cache miss
                    $this->loadMetadataForObjectClass($objectManager, $factory->getMetadataFor($class));
                    if (isset(self::$configurations[$this->name][$class])) {
                        $config = self::$configurations[$this->name][$class];
                    }
                }

                $objectClass = isset($config['useObjectClass']) ? $config['useObjectClass'] : $class;
                if ($objectClass !== $class) {
                    $this->getConfiguration($objectManager, $objectClass);
                }
            }
        }

        return $config;
    }

    /**
     * Get extended metadata mapping reader
     *
     * @return ExtensionMetadataFactory
     */
    public function getExtensionMetadataFactory(ObjectManager $objectManager)
    {
        $oid = spl_object_hash($objectManager);
        if (!isset($this->extensionMetadataFactory[$oid])) {
            if (is_null($this->annotationReader)) {
                // create default annotation reader for extensions
                $this->annotationReader = $this->getDefaultAnnotationReader();
            }
            $this->extensionMetadataFactory[$oid] = new ExtensionMetadataFactory(
                $objectManager,
                $this->getNamespace(),
                $this->annotationReader
            );
        }

        return $this->extensionMetadataFactory[$oid];
    }

    /**
     * Set annotation reader class
     * since older doctrine versions do not provide an interface
     * it must provide these methods:
     *     getClassAnnotations([reflectionClass])
     *     getClassAnnotation([reflectionClass], [name])
     *     getPropertyAnnotations([reflectionProperty])
     *     getPropertyAnnotation([reflectionProperty], [name])
     *
     * @param Reader $reader - annotation reader class
     */
    public function setAnnotationReader($reader)
    {
        $this->annotationReader = $reader;
    }

    /**
     * Scans the objects for extended annotations
     * event subscribers must subscribe to loadClassMetadata event
     *
     * @param object $metadata
     *
     * @return void
     */
    public function loadMetadataForObjectClass(ObjectManager $objectManager, $metadata)
    {
        $factory = $this->getExtensionMetadataFactory($objectManager);
        try {
            $config = $factory->getExtensionMetadata($metadata);
        } catch (\ReflectionException $e) {
            // entity\document generator is running
            $config = false; // will not store a cached version, to remap later
        }
        if ($config) {
            self::$configurations[$this->name][$metadata->name] = $config;
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
     * @return \Doctrine\Common\Annotations\AnnotationReader
     */
    private function getDefaultAnnotationReader()
    {
        if (null === self::$defaultAnnotationReader) {
            AnnotationRegistry::registerAutoloadNamespace('Gedmo\\Mapping\\Annotation', __DIR__.'/../../');

            $reader = new AnnotationReader();

            if (class_exists(ArrayAdapter::class)) {
                $reader = new PsrCachedReader($reader, new ArrayAdapter());
            } elseif (class_exists(ArrayCache::class)) {
                $reader = new PsrCachedReader($reader, CacheAdapter::wrap(new ArrayCache()));
            }

            self::$defaultAnnotationReader = $reader;
        }

        return self::$defaultAnnotationReader;
    }

    /**
     * Sets the value for a mapped field
     *
     * @param object $object
     * @param string $field
     * @param mixed  $oldValue
     * @param mixed  $newValue
     */
    protected function setFieldValue(AdapterInterface $adapter, $object, $field, $oldValue, $newValue)
    {
        $manager = $adapter->getObjectManager();
        $meta = $manager->getClassMetadata(get_class($object));
        $uow = $manager->getUnitOfWork();

        $meta->getReflectionProperty($field)->setValue($object, $newValue);
        $uow->propertyChanged($object, $field, $oldValue, $newValue);
        $adapter->recomputeSingleObjectChangeSet($uow, $meta, $object);
    }
}
