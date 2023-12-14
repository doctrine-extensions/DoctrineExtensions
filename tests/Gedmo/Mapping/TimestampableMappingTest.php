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
use Gedmo\Tests\Mapping\Fixture\Category as AnnotatedCategory;
use Gedmo\Tests\Mapping\Fixture\Xml\Timestampable;
use Gedmo\Tests\Mapping\Fixture\Yaml\Category as YamlCategory;
use Gedmo\Timestampable\TimestampableListener;

/**
 * These are mapping tests for timestampable extension
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class TimestampableMappingTest extends ORMMappingTestCase
{
    private EntityManager $em;

    protected function setUp(): void
    {
        parent::setUp();

        $listener = new TimestampableListener();
        $listener->setCacheItemPool($this->cache);

        $this->em = $this->getBasicEntityManager();
        $this->em->getEventManager()->addEventSubscriber($listener);
    }

    /**
     * @return \Generator<string, array{class-string}>
     *
     * @note the XML fixture has a different mapping from the other configs, so it is tested separately
     */
    public static function dataTimestampableObject(): \Generator
    {
        if (PHP_VERSION_ID >= 80000) {
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
     * @dataProvider dataTimestampableObject
     */
    public function testTimestampableMapping(string $className): void
    {
        // Force metadata class loading.
        $this->em->getClassMetadata($className);
        $cacheId = ExtensionMetadataFactory::getCacheId($className, 'Gedmo\Timestampable');
        $config = $this->cache->getItem($cacheId)->get();

        static::assertArrayHasKey('create', $config);
        static::assertSame('created', $config['create'][0]);
        static::assertArrayHasKey('update', $config);
        static::assertSame('updated', $config['update'][0]);
        static::assertArrayHasKey('change', $config);
        $onChange = $config['change'][0];

        static::assertSame('changed', $onChange['field']);
        static::assertSame('title', $onChange['trackedField']);
        static::assertSame('Test', $onChange['value']);
    }

    public function testTimestampableXmlMapping(): void
    {
        $className = Timestampable::class;

        // Force metadata class loading.
        $this->em->getClassMetadata($className);
        $cacheId = ExtensionMetadataFactory::getCacheId($className, 'Gedmo\Timestampable');
        $config = $this->cache->getItem($cacheId)->get();

        static::assertArrayHasKey('create', $config);
        static::assertSame('created', $config['create'][0]);
        static::assertArrayHasKey('update', $config);
        static::assertSame('updated', $config['update'][0]);
        static::assertArrayHasKey('change', $config);
        $onChange = $config['change'][0];

        static::assertSame('published', $onChange['field']);
        static::assertSame('status.title', $onChange['trackedField']);
        static::assertSame('Published', $onChange['value']);
    }
}
