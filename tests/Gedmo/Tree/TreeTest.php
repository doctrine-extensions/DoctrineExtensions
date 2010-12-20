<?php

namespace Gedmo\Tree;

use Doctrine\Common\Util\Debug;
use Tree\Fixture\Category;

/**
 * These are tests for Tree behavior
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Tree
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TreeTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ENTITY_CLASS = "Tree\Fixture\Category";
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
        $treeListener = new TreeListener();
        $evm->addEventSubscriber($treeListener);
        $this->em = \Doctrine\ORM\EntityManager::create($conn, $config, $evm);

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $schemaTool->dropSchema(array());
        $schemaTool->createSchema(array(
            $this->em->getClassMetadata(self::TEST_ENTITY_CLASS)
        ));
    }
    
    public function testTheTree()
    {        
        $meta = $this->em->getClassMetadata(self::TEST_ENTITY_CLASS);
        
        $root = new Category();
        $root->setTitle("Root");
        $this->assertTrue($root instanceof Node);
        
        $this->em->persist($root);
        $this->em->flush();
        $this->em->clear();
        
        $root = $this->em->getRepository(self::TEST_ENTITY_CLASS)->find(1);
        $left = $meta->getReflectionProperty('lft')->getValue($root);
        $right = $meta->getReflectionProperty('rgt')->getValue($root);
        
        $this->assertEquals($left, 1);
        $this->assertEquals($right, 2);
        
        $child = new Category();
        $child->setTitle("child");
        $child->setParent($root);
        
        $this->em->persist($child);
        $this->em->flush();
        $this->em->clear();
        
        $root = $this->em->getRepository(self::TEST_ENTITY_CLASS)->find(1);
        $left = $meta->getReflectionProperty('lft')->getValue($root);
        $right = $meta->getReflectionProperty('rgt')->getValue($root);
        $level = $meta->getReflectionProperty('level')->getValue($root);
        
        $this->assertEquals($left, 1);
        $this->assertEquals($right, 4);
        $this->assertEquals($level, 0);
        
        $child = $this->em->getRepository(self::TEST_ENTITY_CLASS)->find(2);
        $left = $meta->getReflectionProperty('lft')->getValue($child);
        $right = $meta->getReflectionProperty('rgt')->getValue($child);
        $level = $meta->getReflectionProperty('level')->getValue($child);
        
        $this->assertEquals($left, 2);
        $this->assertEquals($right, 3);
        $this->assertEquals($level, 1);
        
        $child2 = new Category();
        $child2->setTitle("child2");
        $child2->setParent($root);
        
        $this->em->persist($child2);
        $this->em->flush();
        $this->em->clear();
        
        $root = $this->em->getRepository(self::TEST_ENTITY_CLASS)->find(1);
        $left = $meta->getReflectionProperty('lft')->getValue($root);
        $right = $meta->getReflectionProperty('rgt')->getValue($root);
        $level = $meta->getReflectionProperty('level')->getValue($root);
        
        $this->assertEquals($left, 1);
        $this->assertEquals($right, 6);
        $this->assertEquals($level, 0);
        
        $child2 = $this->em->getRepository(self::TEST_ENTITY_CLASS)->find(3);
        $left = $meta->getReflectionProperty('lft')->getValue($child2);
        $right = $meta->getReflectionProperty('rgt')->getValue($child2);
        $level = $meta->getReflectionProperty('level')->getValue($child2);
        
        $this->assertEquals($left, 4);
        $this->assertEquals($right, 5);
        $this->assertEquals($level, 1);
        
        $childsChild = new Category();
        $childsChild->setTitle("childs2_child");
        $childsChild->setParent($child2);
        
        $this->em->persist($childsChild);
        $this->em->flush();
        $this->em->clear();
        
        $child2 = $this->em->getRepository(self::TEST_ENTITY_CLASS)->find(3);
        $left = $meta->getReflectionProperty('lft')->getValue($child2);
        $right = $meta->getReflectionProperty('rgt')->getValue($child2);
        $level = $meta->getReflectionProperty('level')->getValue($child2);
        
        $this->assertEquals($left, 4);
        $this->assertEquals($right, 7);
        $this->assertEquals($level, 1);

        $level = $meta->getReflectionProperty('level')->getValue($childsChild);

        $this->assertEquals($level, 2);
        
        // test updates to nodes, parent changes
        
        $childsChild = $this->em->getRepository(self::TEST_ENTITY_CLASS)->find(4);
        $child = $this->em->getRepository(self::TEST_ENTITY_CLASS)->find(2);
        $childsChild->setTitle('childs_child');
        $childsChild->setParent($child);
        
        $this->em->persist($childsChild);
        $this->em->flush();
        $this->em->clear();
        
        $child = $this->em->getRepository(self::TEST_ENTITY_CLASS)->find(2);
        $left = $meta->getReflectionProperty('lft')->getValue($child);
        $right = $meta->getReflectionProperty('rgt')->getValue($child);
        $level = $meta->getReflectionProperty('level')->getValue($child);
        
        $this->assertEquals($left, 2);
        $this->assertEquals($right, 5);
        $this->assertEquals($level, 1);
        
        // test deletion
        
        $this->em->remove($child);
        $this->em->flush();
        $this->em->clear();
        
        $root = $this->em->getRepository(self::TEST_ENTITY_CLASS)->find(1);
        $left = $meta->getReflectionProperty('lft')->getValue($root);
        $right = $meta->getReflectionProperty('rgt')->getValue($root);
        
        $this->assertEquals($left, 1);
        $this->assertEquals($right, 4);
    }
}
