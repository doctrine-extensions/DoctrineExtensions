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
        $this->assertArrayHasKey('slugFields', $config);
        $this->assertEquals('slug', $config['slugFields']['slug']['slug']);
        $this->assertArrayHasKey('fields', $config);
        $this->assertEquals(1, count($config['fields']['slug']));
        $this->assertEquals('title', $config['fields']['slug'][0]['field']);
        $this->assertEquals('slug', $config['fields']['slug'][0]['slugField']);
        $this->assertEquals(1, $config['fields']['slug'][0]['position']);
        $this->assertArrayHasKey('style', $config['slugFields']['slug']);
        $this->assertEquals('camel', $config['slugFields']['slug']['style']);
        $this->assertArrayHasKey('separator', $config['slugFields']['slug']);
        $this->assertEquals('_', $config['slugFields']['slug']['separator']);
        $this->assertArrayHasKey('unique', $config['slugFields']['slug']);
        $this->assertTrue($config['slugFields']['slug']['unique']);
        $this->assertArrayHasKey('updatable', $config['slugFields']['slug']);
        $this->assertTrue($config['slugFields']['slug']['updatable']);
    }

    public function testSlugHandlerMapping()
    {
        $meta = $this->em->getClassMetadata(self::SLUGGABLE);
        $cacheId = ExtensionMetadataFactory::getCacheId(
            self::SLUGGABLE,
            'Gedmo\Sluggable'
        );
        $config = $this->em->getMetadataFactory()->getCacheDriver()->fetch($cacheId);

        $this->assertArrayHasKey('handlers', $config);
        $handlers = $config['handlers'];
        $this->assertEquals(2, count($handlers));
        $this->assertArrayHasKey('Gedmo\Sluggable\Handler\TreeSlugHandler', $handlers);
        $this->assertArrayHasKey('Gedmo\Sluggable\Handler\RelativeSlugHandler', $handlers);

        $first = $handlers['Gedmo\Sluggable\Handler\TreeSlugHandler'];
        $this->assertEquals(3, count($first));
        $this->assertArrayHasKey('parentRelation', $first);
        $this->assertArrayHasKey('targetField', $first);
        $this->assertArrayHasKey('separator', $first);
        $this->assertEquals('parent', $first['parentRelation']);
        $this->assertEquals('title', $first['targetField']);
        $this->assertEquals('/', $first['separator']);

        $second = $handlers['Gedmo\Sluggable\Handler\RelativeSlugHandler'];
        $this->assertEquals(3, count($second));
        $this->assertArrayHasKey('relationField', $second);
        $this->assertArrayHasKey('relativeSlugField', $second);
        $this->assertArrayHasKey('separator', $second);
        $this->assertEquals('user', $second['relationField']);
        $this->assertEquals('slug', $second['relativeSlugField']);
        $this->assertEquals('/', $second['separator']);
    }
}
