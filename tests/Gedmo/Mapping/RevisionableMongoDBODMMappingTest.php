<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping;

use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;
use Doctrine\ODM\MongoDB\Mapping\Driver\AttributeDriver;
use Gedmo\Mapping\ExtensionMetadataFactory;
use Gedmo\Revisionable\Document\Revision;
use Gedmo\Revisionable\RevisionableListener;
use Gedmo\Tests\Mapping\Fixture\Document\EmbeddedRevisionable as AnnotatedEmbeddedRevisionable;
use Gedmo\Tests\Mapping\Fixture\Document\Revisionable as AnnotatedRevisionable;
use Gedmo\Tests\Mapping\Fixture\Document\RevisionableWithEmbedded as AnnotatedRevisionableWithEmbedded;

/**
 * These are mapping tests for the revisionable extension
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @requires extension mongodb
 */
final class RevisionableMongoDBODMMappingTest extends MongoDBODMMappingTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $listener = new RevisionableListener();
        $listener->setCacheItemPool($this->cache);

        $this->dm->getEventManager()->addEventSubscriber($listener);
    }

    /**
     * @return \Generator<string, array{class-string}>
     */
    public static function dataRevisionableObject(): \Generator
    {
        if (PHP_VERSION_ID >= 80000 && class_exists(AttributeDriver::class)) {
            yield 'Model with attributes' => [AnnotatedRevisionable::class];
        } elseif (class_exists(AnnotationDriver::class)) {
            yield 'Model with annotations' => [AnnotatedRevisionable::class];
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
        $this->dm->getClassMetadata($className);
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
     * @return \Generator<string, array{class-string, class-string}>
     */
    public static function dataRevisionableObjectWithEmbedded(): \Generator
    {
        if (PHP_VERSION_ID >= 80000 && class_exists(AttributeDriver::class)) {
            yield 'Model with attributes' => [AnnotatedRevisionableWithEmbedded::class, AnnotatedEmbeddedRevisionable::class];
        } elseif (class_exists(AnnotationDriver::class)) {
            yield 'Model with annotations' => [AnnotatedRevisionableWithEmbedded::class, AnnotatedEmbeddedRevisionable::class];
        }
    }

    /**
     * @param class-string $className
     * @param class-string $embeddedClassName
     *
     * @dataProvider dataRevisionableObjectWithEmbedded
     */
    public function testRevisionableWithEmbedded(string $className, string $embeddedClassName): void
    {
        // Force metadata class loading.
        $this->dm->getClassMetadata($className);
        $this->dm->getClassMetadata($embeddedClassName);

        /*
         * Inspect the base class
         */

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

        /*
         * Inspect the embedded class
         */

        $cacheId = ExtensionMetadataFactory::getCacheId($embeddedClassName, 'Gedmo\Revisionable');
        $config = $this->cache->getItem($cacheId)->get();

        static::assertArrayHasKey('versioned', $config);
        static::assertCount(1, $config['versioned']);
        static::assertContains('subtitle', $config['versioned']);
    }
}
