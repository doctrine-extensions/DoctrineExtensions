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
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Gedmo\Loggable\Entity\LogEntry;
use Gedmo\Loggable\LoggableListener;
use Gedmo\Mapping\ExtensionMetadataFactory;
use Gedmo\Tests\Mapping\Fixture\Category as AnnotatedCategory;
use Gedmo\Tests\Mapping\Fixture\LoggableComposite as AnnotatedLoggableComposite;
use Gedmo\Tests\Mapping\Fixture\LoggableCompositeRelation as AnnotatedLoggableCompositeRelation;
use Gedmo\Tests\Mapping\Fixture\Xml\LoggableComposite as XmlLoggableComposite;
use Gedmo\Tests\Mapping\Fixture\Xml\LoggableCompositeRelation as XmlLoggableCompositeRelation;
use Gedmo\Tests\Mapping\Fixture\Yaml\Category as YamlCategory;
use Gedmo\Tests\Mapping\Fixture\Yaml\LoggableComposite as YamlLoggableComposite;
use Gedmo\Tests\Mapping\Fixture\Yaml\LoggableCompositeRelation as YamlLoggableCompositeRelation;

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
     */
    public static function dataLoggableObject(): \Generator
    {
        if (PHP_VERSION_ID >= 80000 && class_exists(AttributeDriver::class)) {
            yield 'Model with attributes' => [AnnotatedCategory::class];
        }

        if (class_exists(AnnotationDriver::class)) {
            yield 'Model with annotations' => [AnnotatedCategory::class];
        }

        if (class_exists(YamlDriver::class)) {
            yield 'Model with YAML mapping' => [YamlCategory::class];
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

        static::assertArrayHasKey('loggable', $config);
        static::assertTrue($config['loggable']);
        static::assertArrayHasKey('logEntryClass', $config);
        static::assertSame(LogEntry::class, $config['logEntryClass']);
    }

    /**
     * @return \Generator<string, array{class-string}>
     */
    public static function dataLoggableObjectWithCompositeKey(): \Generator
    {
        yield 'Model with XML mapping' => [XmlLoggableComposite::class];

        if (PHP_VERSION_ID >= 80000 && class_exists(AttributeDriver::class)) {
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
    }

    /**
     * @return \Generator<string, array{class-string}>
     */
    public static function dataLoggableObjectWithCompositeKeyAndRelation(): \Generator
    {
        yield 'Model with XML mapping' => [XmlLoggableCompositeRelation::class];

        if (PHP_VERSION_ID >= 80000 && class_exists(AttributeDriver::class)) {
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
    }
}
