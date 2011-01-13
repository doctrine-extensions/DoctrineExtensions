<?php

namespace Gedmo\Tree;

use Doctrine\Common\Util\Debug;

/**
 * These are tests for Tree behavior
 * Based on reported github issue #12 
 * JOINED table inheritance mapping bug on Tree;
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Tree
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class MultiInheritanceTest2 extends \PHPUnit_Framework_TestCase
{
    const TEST_ENTITY_USER_CLASS = "Tree\Fixture\User";
    const TEST_ENTITY_USERGROUP_CLASS = "Tree\Fixture\UserGroup";
    const TEST_ENTITY_ROLE_CLASS = "Tree\Fixture\Role";
    
    private $em;
    
    public function setUp()
    {        
        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
        $config->setQueryCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
        $config->setProxyDir(__DIR__ . '/Proxy');
        $config->setProxyNamespace('Gedmo\Tree\Proxies');
        $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver());

        $conn = array(
            'driver' => 'pdo_sqlite',
            'memory' => true,
        );

        //$config->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());
        
        $evm = new \Doctrine\Common\EventManager();
        $evm->addEventSubscriber(new \Gedmo\Tree\TreeListener());
        $this->em = \Doctrine\ORM\EntityManager::create($conn, $config, $evm);

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $schemaTool->dropSchema(array());
        $schemaTool->createSchema(array(
            $this->em->getClassMetadata(self::TEST_ENTITY_ROLE_CLASS),
            $this->em->getClassMetadata(self::TEST_ENTITY_USER_CLASS),
            $this->em->getClassMetadata(self::TEST_ENTITY_USERGROUP_CLASS)
        ));
        
        $this->_populate();
    }
    
    public function testConsistence()
    {
        $admins = $this->em->getRepository(self::TEST_ENTITY_USERGROUP_CLASS)->findOneByName('Admins');
        $user3 = new \Tree\Fixture\User('user3@test.com', 'secret');
        $user3->init();
        $user3->setParent($admins);
        
        $this->em->persist($user3);
        $this->em->flush();
        $this->em->clear();
        
        // run tree consistence checks
        
        $everyBody = $this->em->getRepository(self::TEST_ENTITY_USERGROUP_CLASS)->findOneByName('Everybody');
        $this->assertEquals(1, $everyBody->getLeft());
        $this->assertEquals(14, $everyBody->getRight());
        $this->assertEquals(0, $everyBody->getLevel());
        
        $admins = $this->em->getRepository(self::TEST_ENTITY_USERGROUP_CLASS)->findOneByName('Admins');
        $this->assertEquals(2, $admins->getLeft());
        $this->assertEquals(7, $admins->getRight());
        $this->assertEquals(1, $admins->getLevel());
        
        $visitors = $this->em->getRepository(self::TEST_ENTITY_USERGROUP_CLASS)->findOneByName('Visitors');
        $this->assertEquals(8, $visitors->getLeft());
        $this->assertEquals(13, $visitors->getRight());
        $this->assertEquals(1, $visitors->getLevel());
        
        $user0 = $this->em->getRepository(self::TEST_ENTITY_USER_CLASS)->findOneByEmail('user0@test.com');
        $this->assertEquals(3, $user0->getLeft());
        $this->assertEquals(4, $user0->getRight());
        $this->assertEquals(2, $user0->getLevel());
        
        $user1 = $this->em->getRepository(self::TEST_ENTITY_USER_CLASS)->findOneByEmail('user1@test.com');
        $this->assertEquals(9, $user1->getLeft());
        $this->assertEquals(10, $user1->getRight());
        $this->assertEquals(2, $user1->getLevel());
        
        $user2 = $this->em->getRepository(self::TEST_ENTITY_USER_CLASS)->findOneByEmail('user2@test.com');
        $this->assertEquals(11, $user2->getLeft());
        $this->assertEquals(12, $user2->getRight());
        $this->assertEquals(2, $user2->getLevel());
        
        $user3 = $this->em->getRepository(self::TEST_ENTITY_USER_CLASS)->findOneByEmail('user3@test.com');
        $this->assertEquals(5, $user3->getLeft());
        $this->assertEquals(6, $user3->getRight());
        $this->assertEquals(2, $user3->getLevel());
    }
    
    private function _populate()
    {
        $everyBody = new \Tree\Fixture\UserGroup('Everybody');
        $admins = new \Tree\Fixture\UserGroup('Admins');
        $admins->setParent($everyBody);
        $visitors = new \Tree\Fixture\UserGroup('Visitors');
        $visitors->setParent($everyBody);
        
        $user0 = new \Tree\Fixture\User('user0@test.com', 'secret');
        $user0->init();
        $user0->setParent($admins);
        $user1 = new \Tree\Fixture\User('user1@test.com', 'secret');
        $user1->init();
        $user1->setParent($visitors);
        $user2 = new \Tree\Fixture\User('user2@test.com', 'secret');
        $user2->init();
        $user2->setParent($visitors);
        
        $this->em->persist($everyBody);
        $this->em->persist($admins);
        $this->em->persist($visitors);
        $this->em->persist($user0);
        $this->em->persist($user1);
        $this->em->persist($user2);
        $this->em->flush();
        $this->em->clear();
    }
}