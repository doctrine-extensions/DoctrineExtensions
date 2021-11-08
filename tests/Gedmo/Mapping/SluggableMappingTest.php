<?php

namespace Gedmo\Tests\Sluggable;

use Doctrine\ORM\Mapping\Driver\DriverChain;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Gedmo\Mapping\ExtensionMetadataFactory;
use Gedmo\Sluggable\SluggableListener;

/**
 * These are mapping tests for sluggable extension
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SluggableMappingTest extends \PHPUnit\Framework\TestCase
{
    public const TEST_YAML_ENTITY_CLASS = 'Gedmo\Tests\Mapping\Fixture\Yaml\Category';
    public const SLUGGABLE = 'Gedmo\Tests\Mapping\Fixture\Sluggable';
    private $em;

    protected function setUp(): void
    {
        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache());
        $config->setQueryCacheImpl(new \Doctrine\Common\Cache\ArrayCache());
        $config->setProxyDir(TESTS_TEMP_DIR);
        $config->setProxyNamespace('Gedmo\Mapping\Proxy');
        $chainDriverImpl = new DriverChain();
        $chainDriverImpl->addDriver(
            new YamlDriver([__DIR__.'/Driver/Yaml']),
            'Gedmo\Tests\Mapping\Fixture\Yaml'
        );
        $reader = new \Doctrine\Common\Annotations\AnnotationReader();
        \Doctrine\Common\Annotations\AnnotationRegistry::registerAutoloadNamespace(
            'Gedmo\\Mapping\\Annotation',
            VENDOR_PATH.'/../lib'
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
    public function shouldBeAbleToMapSluggableUsingYamlDriver()
    {
        $meta = $this->em->getClassMetadata(self::TEST_YAML_ENTITY_CLASS);
        $cacheId = ExtensionMetadataFactory::getCacheId(
            self::TEST_YAML_ENTITY_CLASS,
            'Gedmo\Sluggable'
        );
        $config = $this->em->getMetadataFactory()->getCacheDriver()->fetch($cacheId);

        static::assertArrayHasKey('slugs', $config);
        static::assertArrayHasKey('slug', $config['slugs']);
        static::assertEquals('slug', $config['slugs']['slug']['slug']);
        static::assertArrayHasKey('fields', $config['slugs']['slug']);
        static::assertCount(1, $config['slugs']['slug']['fields']);
        static::assertEquals('title', $config['slugs']['slug']['fields'][0]);

        static::assertArrayHasKey('style', $config['slugs']['slug']);
        static::assertEquals('camel', $config['slugs']['slug']['style']);
        static::assertArrayHasKey('separator', $config['slugs']['slug']);
        static::assertEquals('_', $config['slugs']['slug']['separator']);
        static::assertArrayHasKey('unique', $config['slugs']['slug']);
        static::assertTrue($config['slugs']['slug']['unique']);
        static::assertArrayHasKey('updatable', $config['slugs']['slug']);
        static::assertTrue($config['slugs']['slug']['updatable']);

        static::assertArrayHasKey('handlers', $config['slugs']['slug']);
        $handlers = $config['slugs']['slug']['handlers'];
        static::assertCount(2, $handlers);
        static::assertArrayHasKey('Gedmo\Sluggable\Handler\TreeSlugHandler', $handlers);
        static::assertArrayHasKey('Gedmo\Sluggable\Handler\RelativeSlugHandler', $handlers);

        $first = $handlers['Gedmo\Sluggable\Handler\TreeSlugHandler'];
        static::assertCount(2, $first);
        static::assertArrayHasKey('parentRelationField', $first);
        static::assertArrayHasKey('separator', $first);
        static::assertEquals('parent', $first['parentRelationField']);
        static::assertEquals('/', $first['separator']);

        $second = $handlers['Gedmo\Sluggable\Handler\RelativeSlugHandler'];
        static::assertCount(3, $second);
        static::assertArrayHasKey('relationField', $second);
        static::assertArrayHasKey('relationSlugField', $second);
        static::assertArrayHasKey('separator', $second);
        static::assertEquals('parent', $second['relationField']);
        static::assertEquals('slug', $second['relationSlugField']);
        static::assertEquals('/', $second['separator']);
    }

    /**
     * @test
     */
    public function shouldBeAbleToMapSluggableUsingAnnotationDriver()
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
        static::assertArrayHasKey('Gedmo\Sluggable\Handler\TreeSlugHandler', $handlers);
        static::assertArrayHasKey('Gedmo\Sluggable\Handler\RelativeSlugHandler', $handlers);

        $first = $handlers['Gedmo\Sluggable\Handler\TreeSlugHandler'];
        static::assertCount(2, $first);
        static::assertArrayHasKey('parentRelationField', $first);
        static::assertArrayHasKey('separator', $first);
        static::assertEquals('parent', $first['parentRelationField']);
        static::assertEquals('/', $first['separator']);

        $second = $handlers['Gedmo\Sluggable\Handler\RelativeSlugHandler'];
        static::assertCount(3, $second);
        static::assertArrayHasKey('relationField', $second);
        static::assertArrayHasKey('relationSlugField', $second);
        static::assertArrayHasKey('separator', $second);
        static::assertEquals('user', $second['relationField']);
        static::assertEquals('slug', $second['relationSlugField']);
        static::assertEquals('/', $second['separator']);
    }
}
