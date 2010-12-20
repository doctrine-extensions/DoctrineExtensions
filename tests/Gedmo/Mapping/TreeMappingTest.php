<?php

namespace Gedmo\Tree;

use Doctrine\Common\Util\Debug,
    Doctrine\ORM\Mapping\Driver\YamlDriver,
    Doctrine\ORM\Mapping\Driver\DriverChain,
    Mapping\Fixture\Yaml\Category,
    Gedmo\Mapping\ExtensionMetadataFactory;

/**
 * These are mapping tests for tree extension
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Mapping
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TreeMappingTest extends \PHPUnit_Framework_TestCase
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
        $evm->addEventSubscriber(new TreeListener());
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
    }
}
