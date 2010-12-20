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
    private $em;

    public function setUp()
    {        
        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
        $config->setQueryCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
        $config->setProxyDir(__DIR__ . '/Proxy');
        $config->setProxyNamespace('Gedmo\Mapping\Proxy');
        $chainDriverImpl = new DriverChain;
        $chainDriverImpl->addDriver(
            new YamlDriver(array(__DIR__ . '/Driver/Yaml')), 
            'Mapping\Fixture\Yaml'
        );
        $config->setMetadataDriverImpl($chainDriverImpl);

        $conn = array(
            'driver' => 'pdo_sqlite',
            'memory' => true,
        );

        //$config->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());
        
        $evm = new \Doctrine\Common\EventManager();
        $evm->addEventSubscriber(new SluggableListener());
        $this->em = \Doctrine\ORM\EntityManager::create($conn, $config, $evm);

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $schemaTool->dropSchema(array());
        $schemaTool->createSchema(array(
            $this->em->getClassMetadata(self::TEST_YAML_ENTITY_CLASS)
        ));
    }
    
    public function testYamlMapping()
    {
        $meta = $this->em->getClassMetadata(self::TEST_YAML_ENTITY_CLASS);
        $cacheId = ExtensionMetadataFactory::getCacheId(
            self::TEST_YAML_ENTITY_CLASS, 
            'Gedmo\Sluggable'
        );
        $config = $this->em->getMetadataFactory()->getCacheDriver()->fetch($cacheId);
        $this->assertArrayHasKey('slug', $config);
        $this->assertEquals('slug', $config['slug']);
        $this->assertArrayHasKey('fields', $config);
        $this->assertEquals(1, count($config['fields']));
        $this->assertEquals('title', $config['fields'][0]);
        $this->assertArrayHasKey('style', $config);
        $this->assertEquals('camel', $config['style']);
        $this->assertArrayHasKey('separator', $config);
        $this->assertEquals('_', $config['separator']);
        $this->assertArrayHasKey('unique', $config);
        $this->assertTrue($config['unique']);
        $this->assertArrayHasKey('updatable', $config);
        $this->assertTrue($config['updatable']);
    }
}
