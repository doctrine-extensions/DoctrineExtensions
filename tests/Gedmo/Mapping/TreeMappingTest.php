<?php

namespace Gedmo\Tests\Tree;

use Doctrine\ORM\Mapping\Driver\DriverChain;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Gedmo\Mapping\ExtensionMetadataFactory;
use Gedmo\Tests\Mapping\Fixture\Yaml\Category;
use Gedmo\Tests\Mapping\Fixture\Yaml\ClosureCategory;
use Gedmo\Tests\Mapping\Fixture\Yaml\MaterializedPathCategory;
use Gedmo\Tests\Tree\Fixture\Closure\CategoryClosure;
use Gedmo\Tree\TreeListener;

/**
 * These are mapping tests for tree extension
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class TreeMappingTest extends \PHPUnit\Framework\TestCase
{
    public const TEST_YAML_ENTITY_CLASS = Category::class;
    public const YAML_CLOSURE_CATEGORY = ClosureCategory::class;
    public const YAML_MATERIALIZED_PATH_CATEGORY = MaterializedPathCategory::class;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var TreeListener
     */
    private $listener;

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
        $evm = new \Doctrine\Common\EventManager();
        $evm->addEventSubscriber(new TreeListener());
        $this->em = \Doctrine\ORM\EntityManager::create($conn, $config, $evm);
    }

    public function testApcCached()
    {
        $this->em->getClassMetadata(self::YAML_CLOSURE_CATEGORY);
        $this->em->getClassMetadata(CategoryClosure::class);

        $meta = $this->em->getMetadataFactory()->getCacheDriver()->fetch(
            'Gedmo\\Tests\\Tree\\Fixture\\Closure\\CategoryClosure$CLASSMETADATA'
        );
        static::assertTrue($meta->hasAssociation('ancestor'));
        static::assertTrue($meta->hasAssociation('descendant'));
    }

    public function testYamlNestedMapping()
    {
        $this->em->getClassMetadata(self::TEST_YAML_ENTITY_CLASS);
        $cacheId = ExtensionMetadataFactory::getCacheId(
            self::TEST_YAML_ENTITY_CLASS,
            'Gedmo\Tree'
        );
        $config = $this->em->getMetadataFactory()->getCacheDriver()->fetch($cacheId);
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

    public function testYamlClosureMapping()
    {
        $meta = $this->em->getClassMetadata(self::YAML_CLOSURE_CATEGORY);
        $cacheId = ExtensionMetadataFactory::getCacheId(self::YAML_CLOSURE_CATEGORY, 'Gedmo\Tree');
        $config = $this->em->getMetadataFactory()->getCacheDriver()->fetch($cacheId);

        static::assertArrayHasKey('parent', $config);
        static::assertSame('parent', $config['parent']);
        static::assertArrayHasKey('strategy', $config);
        static::assertSame('closure', $config['strategy']);
        static::assertArrayHasKey('closure', $config);
        static::assertSame(CategoryClosure::class, $config['closure']);
    }

    public function testYamlMaterializedPathMapping()
    {
        $meta = $this->em->getClassMetadata(self::YAML_MATERIALIZED_PATH_CATEGORY);
        $config = $this->listener->getConfiguration($this->em, $meta->name);

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
