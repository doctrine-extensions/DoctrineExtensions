<?php

namespace Gedmo\Tree;

use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Doctrine\ORM\Mapping\Driver\DriverChain;
use Gedmo\Mapping\ExtensionMetadataFactory;

/**
 * These are mapping tests for tree extension
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TreeMappingTest extends \PHPUnit_Framework_TestCase
{
    const TEST_YAML_ENTITY_CLASS = 'Mapping\Fixture\Yaml\Category';
    const YAML_CLOSURE_CATEGORY = 'Mapping\Fixture\Yaml\ClosureCategory';
    const YAML_MATERIALIZED_PATH_CATEGORY = 'Mapping\Fixture\Yaml\MaterializedPathCategory';

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var TreeListener
     */
    private $listener;

    public function setUp()
    {
        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ApcCache());
        $config->setQueryCacheImpl(new \Doctrine\Common\Cache\ArrayCache());
        $config->setProxyDir(TESTS_TEMP_DIR);
        $config->setProxyNamespace('Gedmo\Mapping\Proxy');
        $chainDriverImpl = new DriverChain();
        $chainDriverImpl->addDriver(
            new YamlDriver(array(__DIR__.'/Driver/Yaml')),
            'Mapping\Fixture\Yaml'
        );
        $chainDriverImpl->addDriver(
            $config->newDefaultAnnotationDriver(array(), false),
            'Tree\Fixture'
        );
        $chainDriverImpl->addDriver(
            $config->newDefaultAnnotationDriver(array(), false),
            'Gedmo\Tree'
        );
        $config->setMetadataDriverImpl($chainDriverImpl);

        $conn = array(
            'driver' => 'pdo_sqlite',
            'memory' => true,
        );

        $this->listener = new TreeListener();
        $evm = new \Doctrine\Common\EventManager();
        $evm->addEventSubscriber(new TreeListener());
        $this->em = \Doctrine\ORM\EntityManager::create($conn, $config, $evm);
    }

    public function testApcCached()
    {
        if (!extension_loaded('apc') || !ini_get('apc.enable_cli')) {
            $this->markTestSkipped('APC extension is not loaded.');
        }
        $this->em->getClassMetadata(self::YAML_CLOSURE_CATEGORY);
        $this->em->getClassMetadata('Tree\Fixture\Closure\CategoryClosure');

        $meta = $this->em->getMetadataFactory()->getCacheDriver()->fetch(
            "Tree\\Fixture\\Closure\\CategoryClosure\$CLASSMETADATA"
        );
        $this->assertTrue($meta->hasAssociation('ancestor'));
        $this->assertTrue($meta->hasAssociation('descendant'));
    }

    public function testYamlNestedMapping()
    {
        if (!extension_loaded('apc') || !ini_get('apc.enable_cli')) {
            $this->markTestSkipped('APC extension is not loaded.');
        }
        $this->em->getClassMetadata(self::TEST_YAML_ENTITY_CLASS);
        $cacheId = ExtensionMetadataFactory::getCacheId(
            self::TEST_YAML_ENTITY_CLASS,
            'Gedmo\Tree'
        );
        $config = $this->em->getMetadataFactory()->getCacheDriver()->fetch($cacheId);
        $this->assertArrayHasKey('left', $config);
        $this->assertEquals('lft', $config['left']);
        $this->assertArrayHasKey('right', $config);
        $this->assertEquals('rgt', $config['right']);
        $this->assertArrayHasKey('parent', $config);
        $this->assertEquals('parent', $config['parent']);
        $this->assertArrayHasKey('level', $config);
        $this->assertEquals('lvl', $config['level']);
        $this->assertArrayHasKey('root', $config);
        $this->assertEquals('root', $config['root']);
        $this->assertArrayHasKey('strategy', $config);
        $this->assertEquals('nested', $config['strategy']);
    }

    public function testYamlClosureMapping()
    {
        if (!extension_loaded('apc') || !ini_get('apc.enable_cli')) {
            $this->markTestSkipped('APC extension is not loaded.');
        }
        $meta = $this->em->getClassMetadata(self::YAML_CLOSURE_CATEGORY);
        $cacheId = ExtensionMetadataFactory::getCacheId(self::YAML_CLOSURE_CATEGORY, 'Gedmo\Tree');
        $config = $this->em->getMetadataFactory()->getCacheDriver()->fetch($cacheId);

        $this->assertArrayHasKey('parent', $config);
        $this->assertEquals('parent', $config['parent']);
        $this->assertArrayHasKey('strategy', $config);
        $this->assertEquals('closure', $config['strategy']);
        $this->assertArrayHasKey('closure', $config);
        $this->assertEquals('Tree\\Fixture\\Closure\\CategoryClosure', $config['closure']);
    }

    public function testYamlMaterializedPathMapping()
    {
        if (!extension_loaded('apc') || !ini_get('apc.enable_cli')) {
            $this->markTestSkipped('APC extension is not loaded.');
        }

        $meta = $this->em->getClassMetadata(self::YAML_MATERIALIZED_PATH_CATEGORY);
        $config = $this->listener->getConfiguration($this->em, $meta->name);

        $this->assertArrayHasKey('strategy', $config);
        $this->assertEquals('materializedPath', $config['strategy']);
        $this->assertArrayHasKey('parent', $config);
        $this->assertEquals('parent', $config['parent']);
        $this->assertArrayHasKey('activate_locking', $config);
        $this->assertTrue($config['activate_locking']);
        $this->assertArrayHasKey('locking_timeout', $config);
        $this->assertEquals(3, $config['locking_timeout']);
        $this->assertArrayHasKey('level', $config);
        $this->assertEquals('level', $config['level']);
        $this->assertArrayHasKey('path', $config);
        $this->assertEquals('path', $config['path']);
        $this->assertArrayHasKey('path_separator', $config);
        $this->assertEquals(',', $config['path_separator']);
    }
}
