<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping;

use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Gedmo\Mapping\ExtensionMetadataFactory;
use Gedmo\Sluggable\Handler\RelativeSlugHandler;
use Gedmo\Sluggable\Handler\TreeSlugHandler;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Tests\Mapping\Fixture\Sluggable;
use Gedmo\Tests\Mapping\Fixture\Yaml\Category;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * These are mapping tests for sluggable extension
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class SluggableMappingTest extends \PHPUnit\Framework\TestCase
{
    public const TEST_YAML_ENTITY_CLASS = Category::class;
    public const SLUGGABLE = Sluggable::class;
    private $em;

    protected function setUp(): void
    {
        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCache(new ArrayAdapter());
        $config->setQueryCache(new ArrayAdapter());
        $config->setProxyDir(TESTS_TEMP_DIR);
        $config->setProxyNamespace('Gedmo\Mapping\Proxy');
        $chainDriverImpl = new MappingDriverChain();
        $chainDriverImpl->addDriver(
            new YamlDriver([__DIR__.'/Driver/Yaml']),
            'Gedmo\Tests\Mapping\Fixture\Yaml'
        );
        $reader = new \Doctrine\Common\Annotations\AnnotationReader();
        \Doctrine\Common\Annotations\AnnotationRegistry::registerAutoloadNamespace(
            'Gedmo\\Mapping\\Annotation',
            dirname(VENDOR_PATH).'/src'
        );
        $chainDriverImpl->addDriver(
            new \Doctrine\ORM\Mapping\Driver\AnnotationDriver($reader),
            'Gedmo\Tests\Mapping\Fixture'
        );
        $config->setMetadataDriverImpl($chainDriverImpl);

        $conn = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        $evm = new \Doctrine\Common\EventManager();
        $evm->addEventSubscriber(new SluggableListener());
        $this->em = \Doctrine\ORM\EntityManager::create($conn, $config, $evm);
    }

    /**
     * @test
     */
    public function shouldBeAbleToMapSluggableUsingYamlDriver(): void
    {
        $meta = $this->em->getClassMetadata(self::TEST_YAML_ENTITY_CLASS);
        $cacheId = ExtensionMetadataFactory::getCacheId(
            self::TEST_YAML_ENTITY_CLASS,
            'Gedmo\Sluggable'
        );
        $config = $this->em->getMetadataFactory()->getCacheDriver()->fetch($cacheId);

        static::assertArrayHasKey('slugs', $config);
        static::assertArrayHasKey('slug', $config['slugs']);
        static::assertSame('slug', $config['slugs']['slug']['slug']);
        static::assertArrayHasKey('fields', $config['slugs']['slug']);
        static::assertCount(1, $config['slugs']['slug']['fields']);
        static::assertSame('title', $config['slugs']['slug']['fields'][0]);

        static::assertArrayHasKey('style', $config['slugs']['slug']);
        static::assertSame('camel', $config['slugs']['slug']['style']);
        static::assertArrayHasKey('separator', $config['slugs']['slug']);
        static::assertSame('_', $config['slugs']['slug']['separator']);
        static::assertArrayHasKey('unique', $config['slugs']['slug']);
        static::assertTrue($config['slugs']['slug']['unique']);
        static::assertArrayHasKey('updatable', $config['slugs']['slug']);
        static::assertTrue($config['slugs']['slug']['updatable']);

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
        static::assertSame('slug', $second['relationSlugField']);
        static::assertSame('/', $second['separator']);
    }

    /**
     * @test
     */
    public function shouldBeAbleToMapSluggableUsingAnnotationDriver(): void
    {
        $meta = $this->em->getClassMetadata(self::SLUGGABLE);
        $cacheId = ExtensionMetadataFactory::getCacheId(
            self::SLUGGABLE,
            'Gedmo\Sluggable'
        );
        $config = $this->em->getMetadataFactory()->getCacheDriver()->fetch($cacheId);

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
        static::assertSame('user', $second['relationField']);
        static::assertSame('slug', $second['relationSlugField']);
        static::assertSame('/', $second['separator']);
    }
}
