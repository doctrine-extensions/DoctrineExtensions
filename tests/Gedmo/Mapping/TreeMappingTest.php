<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping;

use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Gedmo\Mapping\ExtensionMetadataFactory;
use Gedmo\Tests\Mapping\Fixture\Yaml\Category;
use Gedmo\Tests\Mapping\Fixture\Yaml\ClosureCategory;
use Gedmo\Tests\Mapping\Fixture\Yaml\MaterializedPathCategory;
use Gedmo\Tests\Tree\Fixture\Closure\CategoryClosureWithoutMapping;
use Gedmo\Tree\TreeListener;

/**
 * These are mapping tests for tree extension
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class TreeMappingTest extends ORMMappingTestCase
{
    public const TEST_YAML_ENTITY_CLASS = Category::class;
    public const YAML_CLOSURE_CATEGORY = ClosureCategory::class;
    public const YAML_MATERIALIZED_PATH_CATEGORY = MaterializedPathCategory::class;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var TreeListener
     */
    private $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $config = $this->getBasicConfiguration();
        $chainDriverImpl = new MappingDriverChain();
        $chainDriverImpl->addDriver(
            new YamlDriver([__DIR__.'/Driver/Yaml']),
            'Gedmo\Tests\Mapping\Fixture\Yaml'
        );
        $chainDriverImpl->addDriver(
            $config->newDefaultAnnotationDriver([], false),
            'Gedmo\Tests\Tree\Fixture'
        );
        $chainDriverImpl->addDriver(
            $config->newDefaultAnnotationDriver([], false),
            'Gedmo\Tree'
        );
        $config->setMetadataDriverImpl($chainDriverImpl);

        $conn = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        $this->listener = new TreeListener();
        $this->listener->setCacheItemPool($this->cache);
        $evm = new EventManager();
        $evm->addEventSubscriber($this->listener);
        $this->em = EntityManager::create($conn, $config, $evm);
    }

    /**
     * @group legacy
     *
     * @see https://github.com/doctrine/persistence/pull/144
     * @see \Doctrine\Persistence\Mapping\AbstractClassMetadataFactory::getCacheKey()
     */
    public function testApcCached(): void
    {
        $this->em->getClassMetadata(self::YAML_CLOSURE_CATEGORY);
        $this->em->getClassMetadata(CategoryClosureWithoutMapping::class);

        $meta = $this->em->getConfiguration()->getMetadataCache()->getItem(
            'Gedmo__Tests__Tree__Fixture__Closure__CategoryClosureWithoutMapping__CLASSMETADATA__'
        )->get();
        static::assertNotFalse($meta);
        static::assertTrue($meta->hasAssociation('ancestor'));
        static::assertTrue($meta->hasAssociation('descendant'));
    }

    public function testYamlNestedMapping(): void
    {
        $this->em->getClassMetadata(self::TEST_YAML_ENTITY_CLASS);
        $cacheId = ExtensionMetadataFactory::getCacheId(
            self::TEST_YAML_ENTITY_CLASS,
            'Gedmo\Tree'
        );
        $config = $this->cache->getItem($cacheId)->get();
        static::assertArrayHasKey('left', $config);
        static::assertSame('left', $config['left']);
        static::assertArrayHasKey('right', $config);
        static::assertSame('right', $config['right']);
        static::assertArrayHasKey('parent', $config);
        static::assertSame('parent', $config['parent']);
        static::assertArrayHasKey('level', $config);
        static::assertSame('level', $config['level']);
        static::assertArrayHasKey('root', $config);
        static::assertSame('rooted', $config['root']);
        static::assertArrayHasKey('strategy', $config);
        static::assertSame('nested', $config['strategy']);
    }

    /**
     * @group legacy
     */
    public function testYamlClosureMapping(): void
    {
        $meta = $this->em->getClassMetadata(self::YAML_CLOSURE_CATEGORY);
        $cacheId = ExtensionMetadataFactory::getCacheId(self::YAML_CLOSURE_CATEGORY, 'Gedmo\Tree');
        $config = $this->cache->getItem($cacheId)->get();

        static::assertArrayHasKey('parent', $config);
        static::assertSame('parent', $config['parent']);
        static::assertArrayHasKey('strategy', $config);
        static::assertSame('closure', $config['strategy']);
        static::assertArrayHasKey('closure', $config);
        static::assertSame(CategoryClosureWithoutMapping::class, $config['closure']);
    }

    public function testYamlMaterializedPathMapping(): void
    {
        $meta = $this->em->getClassMetadata(self::YAML_MATERIALIZED_PATH_CATEGORY);
        $config = $this->listener->getConfiguration($this->em, $meta->getName());

        static::assertArrayHasKey('strategy', $config);
        static::assertSame('materializedPath', $config['strategy']);
        static::assertArrayHasKey('parent', $config);
        static::assertSame('parent', $config['parent']);
        static::assertArrayHasKey('activate_locking', $config);
        static::assertTrue($config['activate_locking']);
        static::assertArrayHasKey('locking_timeout', $config);
        static::assertSame(3, $config['locking_timeout']);
        static::assertArrayHasKey('level', $config);
        static::assertSame('level', $config['level']);
        static::assertArrayHasKey('path', $config);
        static::assertSame('path', $config['path']);
        static::assertArrayHasKey('path_separator', $config);
        static::assertSame(',', $config['path_separator']);
    }
}
