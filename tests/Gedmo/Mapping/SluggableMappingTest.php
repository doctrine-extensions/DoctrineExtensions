<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\Util\Debug,
    Doctrine\ORM\Mapping\Driver\YamlDriver,
    Doctrine\ORM\Mapping\Driver\DriverChain,
    Mapping\Fixture\Yaml\Category,
    Gedmo\Mapping\ExtensionMetadataFactory;

/**
 * These are mapping tests for sluggable extension
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Mapping
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SluggableMappingTest extends \PHPUnit_Framework_TestCase
{
    const TEST_YAML_ENTITY_CLASS = 'Mapping\Fixture\Yaml\Category';
    const SLUGGABLE = 'Mapping\Fixture\Sluggable';
    private $em;

    public function setUp()
    {
        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
        $config->setQueryCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
        $config->setProxyDir(TESTS_TEMP_DIR);
        $config->setProxyNamespace('Gedmo\Mapping\Proxy');
        $chainDriverImpl = new DriverChain;
        $chainDriverImpl->addDriver(
            new YamlDriver(array(__DIR__ . '/Driver/Yaml')),
            'Mapping\Fixture\Yaml'
        );
        $reader = new \Doctrine\Common\Annotations\AnnotationReader();
        \Doctrine\Common\Annotations\AnnotationRegistry::registerAutoloadNamespace(
            'Gedmo\\Mapping\\Annotation',
            VENDOR_PATH . '/../lib'
        );
        $chainDriverImpl->addDriver(
            new \Doctrine\ORM\Mapping\Driver\AnnotationDriver($reader),
            'Mapping\Fixture'
        );
        $config->setMetadataDriverImpl($chainDriverImpl);

        $conn = array(
            'driver' => 'pdo_sqlite',
            'memory' => true,
        );

        $evm = new \Doctrine\Common\EventManager();
        $evm->addEventSubscriber(new SluggableListener());
        $this->em = \Doctrine\ORM\EntityManager::create($conn, $config, $evm);
    }

    public function testYamlMapping()
    {
        $meta = $this->em->getClassMetadata(self::TEST_YAML_ENTITY_CLASS);
        $cacheId = ExtensionMetadataFactory::getCacheId(
            self::TEST_YAML_ENTITY_CLASS,
            'Gedmo\Sluggable'
        );
        $config = $this->em->getMetadataFactory()->getCacheDriver()->fetch($cacheId);
        $this->assertArrayHasKey('slugs', $config);
        $this->assertArrayHasKey('slug', $config['slugs']);
        $this->assertEquals('slug', $config['slugs']['slug']['slug']);
        $this->assertArrayHasKey('fields', $config['slugs']['slug']);
        $this->assertCount(1, $config['slugs']['slug']['fields']);
        $this->assertEquals('title', $config['slugs']['slug']['fields'][0]);

        $this->assertArrayHasKey('style', $config['slugs']['slug']);
        $this->assertEquals('camel', $config['slugs']['slug']['style']);
        $this->assertArrayHasKey('separator', $config['slugs']['slug']);
        $this->assertEquals('_', $config['slugs']['slug']['separator']);
        $this->assertArrayHasKey('unique', $config['slugs']['slug']);
        $this->assertTrue($config['slugs']['slug']['unique']);
        $this->assertArrayHasKey('updatable', $config['slugs']['slug']);
        $this->assertTrue($config['slugs']['slug']['updatable']);
    }
}
