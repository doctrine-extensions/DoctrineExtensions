<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Mapping;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\PsrCachedReader;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\EventArgs;
use Doctrine\Common\EventSubscriber;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata as DocumentClassMetadata;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo as EntityClassMetadata;
use Doctrine\Persistence\Mapping\AbstractClassMetadataFactory;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Gedmo\Exception\InvalidArgumentException;
use Gedmo\Mapping\Driver\AttributeReader;
use Gedmo\Mapping\Event\AdapterInterface;
use Gedmo\ReferenceIntegrity\Mapping\Validator as ReferenceIntegrityValidator;
use Gedmo\Uploadable\FilenameGenerator\FilenameGeneratorInterface;
use Gedmo\Uploadable\Mapping\Validator as MappingValidator;
use Psr\Cache\CacheItemPoolInterface;
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
 */
abstract class MappedEventSubscriber implements EventSubscriber
{
    /**
     * Static List of cached object configurations
     * leaving it static for reasons to look into
     * other listener configuration
     *
     * @var array<string, array<string, array<string, mixed>>>
     *
     * @phpstan-var array<string, array<class-string, array<string, mixed>>>
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
     * @var array<int, ExtensionMetadataFactory>
     */
    private $extensionMetadataFactory = [];

    /**
     * List of event adapters used for this listener
     *
     * @var array<string, AdapterInterface>
     */
    private $adapters = [];

    /**
     * Custom annotation reader
     *
     * @var Reader|AttributeReader|object|null
     */
    private $annotationReader;

    /**
     * @var PsrCachedReader|null
     */
    private static $defaultAnnotationReader;

    /**
     * @var CacheItemPoolInterface|null
     */
    private $cacheItemPool;

    public function __construct()
    {
        $parts = explode('\\', $this->getNamespace());
        $this->name = end($parts);
    }

    /**
     * Get the configuration for specific object class
     * if cache driver is present it scans it also
     *
     * @param string $class
     *
     * @phpstan-param class-string $class
     *
     * @return array<string, mixed>
     *
     * @phpstan-return array{
     *  useObjectClass?: class-string,
     *  referenceIntegrity?: array<string, array<string, value-of<ReferenceIntegrityValidator::INTEGRITY_ACTIONS>>>,
     *  filePathField?: string,
     *  uploadable?: bool,
     *  fileNameField?: string,
     *  allowOverwrite?: bool,
     *  appendNumber?: bool,
     *  maxSize?: float,
     *  path?: string,
     *  pathMethod?: string,
     *  allowedTypes?: string[],
     *  disallowedTypes?: string[],
     *  filenameGenerator?: MappingValidator::FILENAME_GENERATOR_*|class-string<FilenameGeneratorInterface>,
     *  fileMimeTypeField?: string,
     *  fileSizeField?: string,
     *  callback?: string,
     * }
     */
    public function getConfiguration(ObjectManager $objectManager, $class)
    {
        if (isset(self::$configurations[$this->name][$class])) {
            return self::$configurations[$this->name][$class];
        }

        $config = [];

        $cacheItemPool = $this->getCacheItemPool($objectManager);

        $cacheId = ExtensionMetadataFactory::getCacheId($class, $this->getNamespace());
        $cacheItem = $cacheItemPool->getItem($cacheId);

        if ($cacheItem->isHit()) {
            $config = $cacheItem->get();
            self::$configurations[$this->name][$class] = $config;
        } else {
            // re-generate metadata on cache miss
            $this->loadMetadataForObjectClass($objectManager, $objectManager->getClassMetadata($class));
            if (isset(self::$configurations[$this->name][$class])) {
                $config = self::$configurations[$this->name][$class];
            }
        }

        $objectClass = $config['useObjectClass'] ?? $class;
        if ($objectClass !== $class) {
            $this->getConfiguration($objectManager, $objectClass);
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
        $oid = spl_object_id($objectManager);
        if (!isset($this->extensionMetadataFactory[$oid])) {
            if (null === $this->annotationReader) {
                // create default annotation reader for extensions
                $this->annotationReader = $this->getDefaultAnnotationReader();
            }
            $this->extensionMetadataFactory[$oid] = new ExtensionMetadataFactory(
                $objectManager,
                $this->getNamespace(),
                $this->annotationReader,
                $this->getCacheItemPool($objectManager)
            );
        }

        return $this->extensionMetadataFactory[$oid];
    }

    /**
     * Set the annotation reader instance
     *
     * When originally implemented, `Doctrine\Common\Annotations\Reader` was not available,
     * therefore this method may accept any object implementing these methods from the interface:
     *
     *     getClassAnnotations([reflectionClass])
     *     getClassAnnotation([reflectionClass], [name])
     *     getPropertyAnnotations([reflectionProperty])
     *     getPropertyAnnotation([reflectionProperty], [name])
     *
     * @param Reader|AttributeReader|object $reader
     *
     * @return void
     *
     * NOTE Providing any object is deprecated, as of 4.0 a `Doctrine\Common\Annotations\Reader` or `Gedmo\Mapping\Driver\AttributeReader` will be required
     */
    public function setAnnotationReader($reader)
    {
        if (!$reader instanceof Reader && !$reader instanceof AttributeReader) {
            trigger_deprecation(
                'gedmo/doctrine-extensions',
                '3.11',
                'Providing an annotation reader which does not implement %s or is not an instance of %s to %s() is deprecated.',
                Reader::class,
                AttributeReader::class,
                __METHOD__
            );
        }

        $this->annotationReader = $reader;
    }

    final public function setCacheItemPool(CacheItemPoolInterface $cacheItemPool): void
    {
        $this->cacheItemPool = $cacheItemPool;
    }

    /**
     * Scans the objects for extended annotations
     * event subscribers must subscribe to loadClassMetadata event
     *
     * @param ClassMetadata $metadata
     *
     * @return void
     */
    public function loadMetadataForObjectClass(ObjectManager $objectManager, $metadata)
    {
        assert($metadata instanceof DocumentClassMetadata || $metadata instanceof EntityClassMetadata);

        $factory = $this->getExtensionMetadataFactory($objectManager);

        try {
            $config = $factory->getExtensionMetadata($metadata);
        } catch (\ReflectionException $e) {
            // entity\document generator is running
            $config = []; // will not store a cached version, to remap later
        }
        if ([] !== $config) {
            self::$configurations[$this->name][$metadata->getName()] = $config;
        }
    }

    /**
     * Get an event adapter to handle event specific
     * methods
     *
     * @throws InvalidArgumentException if event is not recognized
     *
     * @return AdapterInterface
     */
    protected function getEventAdapter(EventArgs $args)
    {
        $class = get_class($args);
        if (preg_match('@Doctrine\\\([^\\\]+)@', $class, $m) && in_array($m[1], ['ODM', 'ORM'], true)) {
            if (!isset($this->adapters[$m[1]])) {
                $adapterClass = $this->getNamespace().'\\Mapping\\Event\\Adapter\\'.$m[1];
                if (!\class_exists($adapterClass)) {
                    $adapterClass = 'Gedmo\\Mapping\\Event\\Adapter\\'.$m[1];
                }
                $this->adapters[$m[1]] = new $adapterClass();
            }
            $this->adapters[$m[1]]->setEventArgs($args);

            return $this->adapters[$m[1]];
        }

        throw new InvalidArgumentException('Event mapper does not support event arg class: '.$class);
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
     * Sets the value for a mapped field
     *
     * @param object $object
     * @param string $field
     * @param mixed  $oldValue
     * @param mixed  $newValue
     *
     * @return void
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

    /**
     * Create default annotation reader for extensions
     */
    private function getDefaultAnnotationReader(): Reader
    {
        if (null === self::$defaultAnnotationReader) {
            self::$defaultAnnotationReader = new PsrCachedReader(new AnnotationReader(), new ArrayAdapter());
        }

        return self::$defaultAnnotationReader;
    }

    private function getCacheItemPool(ObjectManager $objectManager): CacheItemPoolInterface
    {
        if (null !== $this->cacheItemPool) {
            return $this->cacheItemPool;
        }

        // TODO: The user should configure its own cache, we are using the one from Doctrine for BC. We should deprecate using
        // the one from Doctrine when the bundle offers an easy way to configure this cache, otherwise users using the bundle
        // will see lots of deprecations without an easy way to avoid them.

        if ($objectManager instanceof EntityManagerInterface || $objectManager instanceof DocumentManager) {
            $metadataFactory = $objectManager->getMetadataFactory();
            $getCache = \Closure::bind(static function (AbstractClassMetadataFactory $metadataFactory): ?CacheItemPoolInterface {
                return $metadataFactory->getCache();
            }, null, \get_class($metadataFactory));

            $metadataCache = $getCache($metadataFactory);

            if (null !== $metadataCache) {
                $this->cacheItemPool = $metadataCache;

                return $this->cacheItemPool;
            }
        }

        $this->cacheItemPool = new ArrayAdapter();

        return $this->cacheItemPool;
    }
}
