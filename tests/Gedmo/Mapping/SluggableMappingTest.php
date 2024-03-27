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
use Gedmo\Mapping\ExtensionMetadataFactory;
use Gedmo\Sluggable\Handler\RelativeSlugHandler;
use Gedmo\Sluggable\Handler\TreeSlugHandler;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Tests\Mapping\Fixture\Sluggable as AnnotatedSluggable;
use Gedmo\Tests\Mapping\Fixture\Xml\Sluggable as XmlSluggable;

/**
 * These are mapping tests for sluggable extension
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class SluggableMappingTest extends ORMMappingTestCase
{
    private EntityManager $em;

    protected function setUp(): void
    {
        parent::setUp();

        $listener = new SluggableListener();
        $listener->setCacheItemPool($this->cache);

        $this->em = $this->getBasicEntityManager();
        $this->em->getEventManager()->addEventSubscriber($listener);
    }

    /**
     * @return \Generator<string, array{class-string}>
     */
    public static function dataSluggableObject(): \Generator
    {
        yield 'Model with XML mapping' => [XmlSluggable::class];
        yield 'Model with attributes' => [AnnotatedSluggable::class];
    }

    /**
     * @param class-string $className
     *
     * @dataProvider dataSluggableObject
     */
    public function testSluggableMapping(string $className): void
    {
        // Force metadata class loading.
        $this->em->getClassMetadata($className);
        $cacheId = ExtensionMetadataFactory::getCacheId($className, 'Gedmo\Sluggable');
        $config = $this->cache->getItem($cacheId)->get();

        static::assertArrayHasKey('slugs', $config);
        static::assertArrayHasKey('slug', $config['slugs']);
        static::assertSame('slug', $config['slugs']['slug']['slug']);
        static::assertArrayHasKey('fields', $config['slugs']['slug']);
        static::assertCount(3, $config['slugs']['slug']['fields']);
        static::assertSame('title', $config['slugs']['slug']['fields'][0]);
        static::assertSame('ean', $config['slugs']['slug']['fields'][1]);
        static::assertSame('code', $config['slugs']['slug']['fields'][2]);

        static::assertArrayHasKey('style', $config['slugs']['slug']);
        static::assertSame('camel', $config['slugs']['slug']['style']);
        static::assertArrayHasKey('separator', $config['slugs']['slug']);
        static::assertSame('_', $config['slugs']['slug']['separator']);
        static::assertArrayHasKey('unique', $config['slugs']['slug']);
        static::assertTrue($config['slugs']['slug']['unique']);
        static::assertArrayHasKey('updatable', $config['slugs']['slug']);
        static::assertFalse($config['slugs']['slug']['updatable']);

        static::assertArrayHasKey('handlers', $config['slugs']['slug']);
        $handlers = $config['slugs']['slug']['handlers'];
        static::assertCount(2, $handlers);
        static::assertArrayHasKey(TreeSlugHandler::class, $handlers);
        static::assertArrayHasKey(RelativeSlugHandler::class, $handlers);

        $first = $handlers[TreeSlugHandler::class];
        static::assertCount(2, $first);
        static::assertArrayHasKey('parentRelationField', $first);
        static::assertArrayHasKey('separator', $first);
        static::assertSame('parent', $first['parentRelationField']);
        static::assertSame('/', $first['separator']);

        $second = $handlers[RelativeSlugHandler::class];
        static::assertCount(3, $second);
        static::assertArrayHasKey('relationField', $second);
        static::assertArrayHasKey('relationSlugField', $second);
        static::assertArrayHasKey('separator', $second);
        static::assertSame('parent', $second['relationField']);
        static::assertSame('test', $second['relationSlugField']);
        static::assertSame('-', $second['separator']);
    }
}
