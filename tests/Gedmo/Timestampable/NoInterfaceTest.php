<?php

namespace Gedmo\Timestampable;

use Doctrine\Common\Util\Debug,
    Timestampable\Fixture\WithoutInterface;

/**
 * These are tests for Timestampable behavior
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Timestampable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class NoInterfaceTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ENTITY_CLASS = "Timestampable\Fixture\WithoutInterface";
    private $em;

    public function setUp()
    {        
        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
        $config->setQueryCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
        $config->setProxyDir(__DIR__ . '/Proxy');
        $config->setProxyNamespace('Gedmo\Timestampable\Proxies');
        $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver());

        $conn = array(
            'driver' => 'pdo_sqlite',
            'memory' => true,
        );

        //$config->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());
        
        $evm = new \Doctrine\Common\EventManager();
        $timestampableListener = new TimestampableListener();
        $evm->addEventSubscriber($timestampableListener);
        $this->em = \Doctrine\ORM\EntityManager::create($conn, $config, $evm);

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $schemaTool->dropSchema(array());
        $schemaTool->createSchema(array(
            $this->em->getClassMetadata(self::TEST_ENTITY_CLASS)
        ));
    }
    
    public function testTimestampableNoInterface()
    {        
        $test = new WithoutInterface();
        $test->setTitle('Test');
        
        $date = new \DateTime('now');
        $this->em->persist($test);
        $this->em->flush();
        $this->em->clear();
        
        $test = $this->em->getRepository(self::TEST_ENTITY_CLASS)->findOneByTitle('Test');
        $this->assertEquals(
            $date->format('Y-m-d 00:00:00'), 
            $test->getCreated()->format('Y-m-d H:i:s')
        );
        $this->assertEquals(
            $date->format('Y-m-d H:i:s'), 
            $test->getUpdated()->format('Y-m-d H:i:s')
        );
    }
}
