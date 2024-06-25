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
use Gedmo\Mapping\ExtensionMetadataFactory;
use Gedmo\Revisionable\Entity\Revision;
use Gedmo\Revisionable\RevisionableListener;
use Gedmo\Tests\Mapping\Fixture\Revisionable as AnnotatedRevisionable;
use Gedmo\Tests\Mapping\Fixture\RevisionableComposite as AnnotatedRevisionableComposite;
use Gedmo\Tests\Mapping\Fixture\RevisionableCompositeRelation as AnnotatedRevisionableCompositeRelation;
use Gedmo\Tests\Mapping\Fixture\RevisionableWithEmbedded as AnnotatedRevisionableWithEmbedded;
use Gedmo\Tests\Mapping\Fixture\Xml\Revisionable as XmlRevisionable;
use Gedmo\Tests\Mapping\Fixture\Xml\RevisionableComposite as XmlRevisionableComposite;
use Gedmo\Tests\Mapping\Fixture\Xml\RevisionableCompositeRelation as XmlRevisionableCompositeRelation;
use Gedmo\Tests\Mapping\Fixture\Xml\RevisionableWithEmbedded as XmlRevisionableWithEmbedded;
use Gedmo\Tests\Mapping\Fixture\Yaml\Revisionable as YamlRevisionable;
use Gedmo\Tests\Mapping\Fixture\Yaml\RevisionableComposite as YamlRevisionableComposite;
use Gedmo\Tests\Mapping\Fixture\Yaml\RevisionableCompositeRelation as YamlRevisionableCompositeRelation;
use Gedmo\Tests\Mapping\Fixture\Yaml\RevisionableWithEmbedded as YamlRevisionableWithEmbedded;

/**
 * These are mapping tests for the revisionable extension
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class RevisionableORMMappingTest extends ORMMappingTestCase
{
    private EntityManager $em;

    protected function setUp(): void
    {
        parent::setUp();

        $listener = new RevisionableListener();
        $listener->setCacheItemPool($this->cache);

        $this->em = $this->getBasicEntityManager();
        $this->em->getEventManager()->addEventSubscriber($listener);
    }

    /**
     * @return \Generator<string, array{class-string}>
     */
    public static function dataRevisionableObject(): \Generator
    {
        yield 'Model with XML mapping' => [XmlRevisionable::class];

        if (PHP_VERSION_ID >= 80000) {
            yield 'Model with attributes' => [AnnotatedRevisionable::class];
        } elseif (class_exists(AnnotationDriver::class)) {
            yield 'Model with annotations' => [AnnotatedRevisionable::class];
        }

        if (class_exists(YamlDriver::class)) {
            yield 'Model with YAML mapping' => [YamlRevisionable::class];
        }
    }

    /**
     * @param class-string $className
     *
     * @dataProvider dataRevisionableObject
     */
    public function testRevisionableMapping(string $className): void
    {
        // Force metadata class loading.
        $this->em->getClassMetadata($className);
        $cacheId = ExtensionMetadataFactory::getCacheId($className, 'Gedmo\Revisionable');
        $config = $this->cache->getItem($cacheId)->get();

        static::assertArrayNotHasKey('revisionableClass', $config);
        static::assertArrayHasKey('revisionable', $config);
        static::assertTrue($config['revisionable']);

        static::assertArrayHasKey('versioned', $config);
        static::assertCount(1, $config['versioned']);
        static::assertContains('title', $config['versioned']);
    }

    /**
     * @return \Generator<string, array{class-string}>
     */
    public static function dataRevisionableObjectWithCompositeKey(): \Generator
    {
        yield 'Model with XML mapping' => [XmlRevisionableComposite::class];

        if (PHP_VERSION_ID >= 80000) {
            yield 'Model with attributes' => [AnnotatedRevisionableComposite::class];
        } elseif (class_exists(AnnotationDriver::class)) {
            yield 'Model with annotations' => [AnnotatedRevisionableComposite::class];
        }

        if (class_exists(YamlDriver::class)) {
            yield 'Model with YAML mapping' => [YamlRevisionableComposite::class];
        }
    }

    /**
     * @param class-string $className
     *
     * @dataProvider dataRevisionableObjectWithCompositeKey
     */
    public function testRevisionableCompositeMapping(string $className): void
    {
        $meta = $this->em->getClassMetadata($className);

        static::assertIsArray($meta->identifier);
        static::assertCount(2, $meta->identifier);

        $cacheId = ExtensionMetadataFactory::getCacheId($className, 'Gedmo\Revisionable');
        $config = $this->cache->getItem($cacheId)->get();

        static::assertArrayHasKey('revisionable', $config);
        static::assertTrue($config['revisionable']);
        static::assertArrayHasKey('revisionClass', $config);
        static::assertSame(Revision::class, $config['revisionClass']);

        static::assertArrayHasKey('versioned', $config);
        static::assertCount(1, $config['versioned']);
        static::assertContains('title', $config['versioned']);
    }

    /**
     * @return \Generator<string, array{class-string}>
     */
    public static function dataRevisionableObjectWithCompositeKeyAndRelation(): \Generator
    {
        yield 'Model with XML mapping' => [XmlRevisionableCompositeRelation::class];

        if (PHP_VERSION_ID >= 80000) {
            yield 'Model with attributes' => [AnnotatedRevisionableCompositeRelation::class];
        } elseif (class_exists(AnnotationDriver::class)) {
            yield 'Model with annotations' => [AnnotatedRevisionableCompositeRelation::class];
        }

        if (class_exists(YamlDriver::class)) {
            yield 'Model with YAML mapping' => [YamlRevisionableCompositeRelation::class];
        }
    }

    /**
     * @param class-string $className
     *
     * @dataProvider dataRevisionableObjectWithCompositeKeyAndRelation
     */
    public function testRevisionableCompositeRelationMapping(string $className): void
    {
        $meta = $this->em->getClassMetadata($className);

        static::assertIsArray($meta->identifier);
        static::assertCount(2, $meta->identifier);

        $cacheId = ExtensionMetadataFactory::getCacheId($className, 'Gedmo\Revisionable');
        $config = $this->cache->getItem($cacheId)->get();

        static::assertArrayHasKey('revisionable', $config);
        static::assertTrue($config['revisionable']);
        static::assertArrayNotHasKey('revisionClass', $config);

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
    public static function dataRevisionableObjectWithEmbedded(): \Generator
    {
        if (PHP_VERSION_ID >= 80000) {
            yield 'Model with attributes' => [AnnotatedRevisionableWithEmbedded::class];
        } elseif (class_exists(AnnotationDriver::class)) {
            yield 'Model with annotations' => [AnnotatedRevisionableWithEmbedded::class];
        }
    }

    /**
     * @param class-string $className
     *
     * @dataProvider dataRevisionableObjectWithEmbedded
     */
    public function testRevisionableAnnotatedWithEmbedded(string $className): void
    {
        // Force metadata class loading.
        $this->em->getClassMetadata($className);
        $cacheId = ExtensionMetadataFactory::getCacheId($className, 'Gedmo\Revisionable');
        $config = $this->cache->getItem($cacheId)->get();

        static::assertArrayHasKey('revisionable', $config);
        static::assertTrue($config['revisionable']);
        static::assertArrayHasKey('revisionClass', $config);
        static::assertSame(Revision::class, $config['revisionClass']);

        static::assertArrayHasKey('versioned', $config);
        static::assertCount(1, $config['versioned']);
        static::assertContains('title', $config['versioned']);
    }

    public function testRevisionableXmlWithEmbedded(): void
    {
        $className = XmlRevisionableWithEmbedded::class;

        // Force metadata class loading.
        $this->em->getClassMetadata($className);
        $cacheId = ExtensionMetadataFactory::getCacheId($className, 'Gedmo\Revisionable');
        $config = $this->cache->getItem($cacheId)->get();

        static::assertArrayHasKey('revisionable', $config);
        static::assertTrue($config['revisionable']);
        static::assertArrayHasKey('revisionClass', $config);
        static::assertSame(Revision::class, $config['revisionClass']);

        static::assertArrayHasKey('versioned', $config);
        static::assertCount(2, $config['versioned']);
        static::assertContains('title', $config['versioned']);
        static::assertContains('embedded', $config['versioned']);
    }

    public function testRevisionableYamlWithEmbedded(): void
    {
        if (!class_exists(YamlDriver::class)) {
            static::markTestSkipped('Test case requires the deprecated YAML mapping driver from the ORM.');
        }

        $className = YamlRevisionableWithEmbedded::class;

        // Force metadata class loading.
        $this->em->getClassMetadata($className);
        $cacheId = ExtensionMetadataFactory::getCacheId($className, 'Gedmo\Revisionable');
        $config = $this->cache->getItem($cacheId)->get();

        static::assertArrayHasKey('revisionable', $config);
        static::assertTrue($config['revisionable']);
        static::assertArrayHasKey('revisionClass', $config);
        static::assertSame(Revision::class, $config['revisionClass']);

        static::assertArrayHasKey('versioned', $config);
        static::assertCount(2, $config['versioned']);
        static::assertContains('title', $config['versioned']);
        static::assertContains('embedded.subtitle', $config['versioned']);
    }
}
