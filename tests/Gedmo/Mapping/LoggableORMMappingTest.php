<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Gedmo\Loggable\Entity\LogEntry;
use Gedmo\Loggable\LoggableListener;
use Gedmo\Mapping\ExtensionMetadataFactory;
use Gedmo\Tests\Mapping\Fixture\Loggable as AnnotatedLoggable;
use Gedmo\Tests\Mapping\Fixture\LoggableComposite as AnnotatedLoggableComposite;
use Gedmo\Tests\Mapping\Fixture\LoggableCompositeRelation as AnnotatedLoggableCompositeRelation;
use Gedmo\Tests\Mapping\Fixture\LoggableWithEmbedded as AnnotatedLoggableWithEmbedded;
use Gedmo\Tests\Mapping\Fixture\Xml\Loggable as XmlLoggable;
use Gedmo\Tests\Mapping\Fixture\Xml\LoggableComposite as XmlLoggableComposite;
use Gedmo\Tests\Mapping\Fixture\Xml\LoggableCompositeRelation as XmlLoggableCompositeRelation;
use Gedmo\Tests\Mapping\Fixture\Xml\LoggableWithEmbedded as XmlLoggableWithEmbedded;
use Gedmo\Tests\Mapping\Fixture\Yaml\Loggable as YamlLoggable;
use Gedmo\Tests\Mapping\Fixture\Yaml\LoggableComposite as YamlLoggableComposite;
use Gedmo\Tests\Mapping\Fixture\Yaml\LoggableCompositeRelation as YamlLoggableCompositeRelation;
use Gedmo\Tests\Mapping\Fixture\Yaml\LoggableWithEmbedded as YamlLoggableWithEmbedded;

/**
 * These are mapping tests for the loggable extension
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class LoggableORMMappingTest extends ORMMappingTestCase
{
    private EntityManager $em;

    protected function setUp(): void
    {
        parent::setUp();

        $listener = new LoggableListener();
        $listener->setCacheItemPool($this->cache);

        $this->em = $this->getBasicEntityManager();
        $this->em->getEventManager()->addEventSubscriber($listener);
    }

    /**
     * @return \Generator<string, array{class-string}>
     *
     * @note the XML fixture has a different mapping from the other configs, so it is tested separately
     */
    public static function dataLoggableObject(): \Generator
    {
        if (PHP_VERSION_ID >= 80000) {
            yield 'Model with attributes' => [AnnotatedLoggable::class];
        }

        if (class_exists(AnnotationDriver::class)) {
            yield 'Model with annotations' => [AnnotatedLoggable::class];
        }

        if (class_exists(YamlDriver::class)) {
            yield 'Model with YAML mapping' => [YamlLoggable::class];
        }
    }

    /**
     * @param class-string $className
     *
     * @dataProvider dataLoggableObject
     */
    public function testLoggableMapping(string $className): void
    {
        // Force metadata class loading.
        $this->em->getClassMetadata($className);
        $cacheId = ExtensionMetadataFactory::getCacheId($className, 'Gedmo\Loggable');
        $config = $this->cache->getItem($cacheId)->get();

        static::assertArrayHasKey('logEntryClass', $config);
        static::assertSame(LogEntry::class, $config['logEntryClass']);
        static::assertArrayHasKey('loggable', $config);
        static::assertTrue($config['loggable']);

        static::assertArrayHasKey('versioned', $config);
        static::assertCount(1, $config['versioned']);
        static::assertContains('title', $config['versioned']);
    }

    public function testLoggableXmlMapping(): void
    {
        $className = XmlLoggable::class;

        // Force metadata class loading.
        $this->em->getClassMetadata($className);
        $cacheId = ExtensionMetadataFactory::getCacheId($className, 'Gedmo\Loggable');
        $config = $this->cache->getItem($cacheId)->get();

        static::assertArrayHasKey('logEntryClass', $config);
        static::assertSame(LogEntry::class, $config['logEntryClass']);
        static::assertArrayHasKey('loggable', $config);
        static::assertTrue($config['loggable']);

        static::assertArrayHasKey('versioned', $config);
        static::assertCount(2, $config['versioned']);
        static::assertContains('title', $config['versioned']);
        static::assertContains('status', $config['versioned']);
    }

    /**
     * @return \Generator<string, array{class-string}>
     */
    public static function dataLoggableObjectWithCompositeKey(): \Generator
    {
        yield 'Model with XML mapping' => [XmlLoggableComposite::class];

        if (PHP_VERSION_ID >= 80000) {
            yield 'Model with attributes' => [AnnotatedLoggableComposite::class];
        }

        if (class_exists(AnnotationDriver::class)) {
            yield 'Model with annotations' => [AnnotatedLoggableComposite::class];
        }

        if (class_exists(YamlDriver::class)) {
            yield 'Model with YAML mapping' => [YamlLoggableComposite::class];
        }
    }

    /**
     * @param class-string $className
     *
     * @dataProvider dataLoggableObjectWithCompositeKey
     */
    public function testLoggableCompositeMapping(string $className): void
    {
        $meta = $this->em->getClassMetadata($className);

        static::assertIsArray($meta->identifier);
        static::assertCount(2, $meta->identifier);

        $cacheId = ExtensionMetadataFactory::getCacheId($className, 'Gedmo\Loggable');
        $config = $this->cache->getItem($cacheId)->get();

        static::assertArrayHasKey('loggable', $config);
        static::assertTrue($config['loggable']);
        static::assertArrayHasKey('logEntryClass', $config);
        static::assertSame(LogEntry::class, $config['logEntryClass']);

        static::assertArrayHasKey('versioned', $config);
        static::assertCount(1, $config['versioned']);
        static::assertContains('title', $config['versioned']);
    }

    /**
     * @return \Generator<string, array{class-string}>
     */
    public static function dataLoggableObjectWithCompositeKeyAndRelation(): \Generator
    {
        yield 'Model with XML mapping' => [XmlLoggableCompositeRelation::class];

        if (PHP_VERSION_ID >= 80000) {
            yield 'Model with attributes' => [AnnotatedLoggableCompositeRelation::class];
        }

        if (class_exists(AnnotationDriver::class)) {
            yield 'Model with annotations' => [AnnotatedLoggableCompositeRelation::class];
        }

        if (class_exists(YamlDriver::class)) {
            yield 'Model with YAML mapping' => [YamlLoggableCompositeRelation::class];
        }
    }

    /**
     * @param class-string $className
     *
     * @dataProvider dataLoggableObjectWithCompositeKeyAndRelation
     */
    public function testLoggableCompositeRelationMapping(string $className): void
    {
        $meta = $this->em->getClassMetadata($className);

        static::assertIsArray($meta->identifier);
        static::assertCount(2, $meta->identifier);

        $cacheId = ExtensionMetadataFactory::getCacheId($className, 'Gedmo\Loggable');
        $config = $this->cache->getItem($cacheId)->get();

        static::assertArrayHasKey('loggable', $config);
        static::assertTrue($config['loggable']);
        static::assertArrayHasKey('logEntryClass', $config);
        static::assertSame(LogEntry::class, $config['logEntryClass']);

        static::assertArrayHasKey('versioned', $config);
        static::assertCount(1, $config['versioned']);
        static::assertContains('title', $config['versioned']);
    }

    /*
     * Each of the mapping drivers handles versioning embedded objects differently, so instead of using a single test case,
     * these will be run as separate cases checking each driver's config appropriately.
     */

    /**
     * @return \Generator<string, array{class-string}>
     */
    public static function dataLoggableObjectWithEmbedded(): \Generator
    {
        if (PHP_VERSION_ID >= 80000) {
            yield 'Model with attributes' => [AnnotatedLoggableWithEmbedded::class];
        }

        if (class_exists(AnnotationDriver::class)) {
            yield 'Model with annotations' => [AnnotatedLoggableWithEmbedded::class];
        }
    }

    /**
     * @param class-string $className
     *
     * @dataProvider dataLoggableObjectWithEmbedded
     */
    public function testLoggableAnnotatedWithEmbedded(string $className): void
    {
        // Force metadata class loading.
        $this->em->getClassMetadata($className);
        $cacheId = ExtensionMetadataFactory::getCacheId($className, 'Gedmo\Loggable');
        $config = $this->cache->getItem($cacheId)->get();

        static::assertArrayHasKey('logEntryClass', $config);
        static::assertSame(LogEntry::class, $config['logEntryClass']);
        static::assertArrayHasKey('loggable', $config);
        static::assertTrue($config['loggable']);

        static::assertArrayHasKey('versioned', $config);
        static::assertCount(1, $config['versioned']);
        static::assertContains('title', $config['versioned']);
    }

    public function testLoggableXmlWithEmbedded(): void
    {
        $className = XmlLoggableWithEmbedded::class;

        // Force metadata class loading.
        $this->em->getClassMetadata($className);
        $cacheId = ExtensionMetadataFactory::getCacheId($className, 'Gedmo\Loggable');
        $config = $this->cache->getItem($cacheId)->get();

        static::assertArrayHasKey('logEntryClass', $config);
        static::assertSame(LogEntry::class, $config['logEntryClass']);
        static::assertArrayHasKey('loggable', $config);
        static::assertTrue($config['loggable']);

        static::assertArrayHasKey('versioned', $config);
        static::assertCount(3, $config['versioned']);
        static::assertContains('title', $config['versioned']);
        static::assertContains('status', $config['versioned']);
        static::assertContains('embedded', $config['versioned']);
    }

    public function testLoggableYamlWithEmbedded(): void
    {
        if (!class_exists(YamlDriver::class)) {
            static::markTestSkipped('Test case requires the deprecated YAML mapping driver from the ORM.');
        }

        $className = YamlLoggableWithEmbedded::class;

        // Force metadata class loading.
        $this->em->getClassMetadata($className);
        $cacheId = ExtensionMetadataFactory::getCacheId($className, 'Gedmo\Loggable');
        $config = $this->cache->getItem($cacheId)->get();

        static::assertArrayHasKey('logEntryClass', $config);
        static::assertSame(LogEntry::class, $config['logEntryClass']);
        static::assertArrayHasKey('loggable', $config);
        static::assertTrue($config['loggable']);

        static::assertArrayHasKey('versioned', $config);
        static::assertCount(2, $config['versioned']);
        static::assertContains('title', $config['versioned']);
        static::assertContains('embedded.subtitle', $config['versioned']);
    }
}
