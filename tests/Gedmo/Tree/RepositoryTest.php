<?php

namespace Gedmo\Tree;

use Doctrine\Common\Util\Debug,
    Tree\Fixture\Category;

/**
 * These are tests for Tree behavior
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Tree
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class RepositoryTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ENTITY_CATEGORY = "Tree\Fixture\Category";
    
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
            $this->em->getClassMetadata(self::TEST_ENTITY_CATEGORY)
        ));
        $this->_populate();
    }
    
    public function testBasicFunctions()
    {
        $vegies = $this->em->getRepository(self::TEST_ENTITY_CATEGORY)
            ->findOneByTitle('Vegitables');
            
        $food = $this->em->getRepository(self::TEST_ENTITY_CATEGORY)
            ->findOneByTitle('Food');
        
        // test childCount    
        
        $childCount = $this->em->getRepository(self::TEST_ENTITY_CATEGORY)
            ->childCount($vegies);
        $this->assertEquals(2, $childCount);
        
        $childCount = $this->em->getRepository(self::TEST_ENTITY_CATEGORY)
            ->childCount($food);
        $this->assertEquals(4, $childCount);
        
        $childCount = $this->em->getRepository(self::TEST_ENTITY_CATEGORY)
            ->childCount($food, true);
        $this->assertEquals(2, $childCount);
        
        $childCount = $this->em->getRepository(self::TEST_ENTITY_CATEGORY)
            ->childCount();
        $this->assertEquals(6, $childCount);
        
        // test children
        
        $children = $this->em->getRepository(self::TEST_ENTITY_CATEGORY)
            ->children($vegies);
            
        $this->assertEquals(2, count($children));
        $this->assertEquals('Carrots', $children[0]->getTitle());
        $this->assertEquals('Potatoes', $children[1]->getTitle());
        
        $children = $this->em->getRepository(self::TEST_ENTITY_CATEGORY)
            ->children($food);
        
        $this->assertEquals(4, count($children));
        $this->assertEquals('Fruits', $children[0]->getTitle());
        $this->assertEquals('Vegitables', $children[1]->getTitle());
        $this->assertEquals('Carrots', $children[2]->getTitle());
        $this->assertEquals('Potatoes', $children[3]->getTitle());
        
        $children = $this->em->getRepository(self::TEST_ENTITY_CATEGORY)
            ->children($food, true);
        
        $this->assertEquals(2, count($children));
        $this->assertEquals('Fruits', $children[0]->getTitle());
        $this->assertEquals('Vegitables', $children[1]->getTitle());
        
        $children = $this->em->getRepository(self::TEST_ENTITY_CATEGORY)
            ->children();
        
        $this->assertEquals(6, count($children));
        
        // path
        
        $path = $this->em->getRepository(self::TEST_ENTITY_CATEGORY)
            ->getPath($vegies);
            
        $this->assertEquals(2, count($path));
        $this->assertEquals('Food', $path[0]->getTitle());
        $this->assertEquals('Vegitables', $path[1]->getTitle());
        
        $carrots = $this->em->getRepository(self::TEST_ENTITY_CATEGORY)
            ->findOneByTitle('Carrots');
            
        $path = $this->em->getRepository(self::TEST_ENTITY_CATEGORY)
            ->getPath($carrots);
            
        $this->assertEquals(3, count($path));
        $this->assertEquals('Food', $path[0]->getTitle());
        $this->assertEquals('Vegitables', $path[1]->getTitle());
        $this->assertEquals('Carrots', $path[2]->getTitle());
        
        // leafs
        
        $leafs = $this->em->getRepository(self::TEST_ENTITY_CATEGORY)
            ->getLeafs();

        $this->assertEquals(4, count($leafs));
        $this->assertEquals('Fruits', $leafs[0]->getTitle());
        $this->assertEquals('Carrots', $leafs[1]->getTitle());
        $this->assertEquals('Potatoes', $leafs[2]->getTitle());
        $this->assertEquals('Sports', $leafs[3]->getTitle());
    }
    
    public function testAdvancedFunctions()
    {
        $this->_populateMore();
        $onions = $this->em->getRepository(self::TEST_ENTITY_CATEGORY)
            ->findOneByTitle('Onions');
        $repo = $this->em->getRepository(self::TEST_ENTITY_CATEGORY);
        $meta = $this->em->getClassMetadata(self::TEST_ENTITY_CATEGORY);
        
        $left = $meta->getReflectionProperty('lft')->getValue($onions);
        $right = $meta->getReflectionProperty('rgt')->getValue($onions);
        
        $this->assertEquals($left, 11);
        $this->assertEquals($right, 12);
        
        // move up onions by one position
        
        $repo->moveUp($onions, 1);
        $this->em->refresh($onions);
        
        $left = $meta->getReflectionProperty('lft')->getValue($onions);
        $right = $meta->getReflectionProperty('rgt')->getValue($onions);
        
        $this->assertEquals($left, 9);
        $this->assertEquals($right, 10);
        
        // move down onions by one position
        $repo->moveDown($onions, 1);
        $this->em->refresh($onions);
        
        $left = $meta->getReflectionProperty('lft')->getValue($onions);
        $right = $meta->getReflectionProperty('rgt')->getValue($onions);
        
        $this->assertEquals($left, 11);
        $this->assertEquals($right, 12);
        
        // move to the up onions on this level
        
        $repo->moveUp($onions, true);
        $this->em->refresh($onions);
        
        $left = $meta->getReflectionProperty('lft')->getValue($onions);
        $right = $meta->getReflectionProperty('rgt')->getValue($onions);
        
        $this->assertEquals($left, 5);
        $this->assertEquals($right, 6);
        
        // test tree reordering
        
        $this->em->clear(); // clear all cached nodes
        
        // reorder tree by title
        
        $repo->reorder(null, 'title');
        $this->em->clear(); // clear all cached nodes
        
        $node = $this->em->getRepository(self::TEST_ENTITY_CATEGORY)
            ->findOneByTitle('Cabbages');
        $left = $meta->getReflectionProperty('lft')->getValue($node);
        $right = $meta->getReflectionProperty('rgt')->getValue($node);
        
        $this->assertEquals($left, 5);
        $this->assertEquals($right, 6);

        $node = $this->em->getRepository(self::TEST_ENTITY_CATEGORY)
            ->findOneByTitle('Carrots');
        $left = $meta->getReflectionProperty('lft')->getValue($node);
        $right = $meta->getReflectionProperty('rgt')->getValue($node);
        
        $this->assertEquals($left, 7);
        $this->assertEquals($right, 8);
        
        $node = $this->em->getRepository(self::TEST_ENTITY_CATEGORY)
            ->findOneByTitle('Onions');
        $left = $meta->getReflectionProperty('lft')->getValue($node);
        $right = $meta->getReflectionProperty('rgt')->getValue($node);
        
        $this->assertEquals($left, 9);
        $this->assertEquals($right, 10);
        
        $node = $this->em->getRepository(self::TEST_ENTITY_CATEGORY)
            ->findOneByTitle('Potatoes');
        $left = $meta->getReflectionProperty('lft')->getValue($node);
        $right = $meta->getReflectionProperty('rgt')->getValue($node);
        
        $this->assertEquals($left, 11);
        $this->assertEquals($right, 12);
        
        // test removal with reparenting
        
        $this->em->clear(); // clear all cached nodes
        $vegies = $this->em->getRepository(self::TEST_ENTITY_CATEGORY)
            ->findOneByTitle('Vegitables');
            
        $repo->removeFromTree($vegies);
        
        $this->em->clear(); // clear all cached nodes
        
        $vegies = $this->em->getRepository(self::TEST_ENTITY_CATEGORY)
            ->findOneByTitle('Vegitables');
            
        $this->assertTrue($vegies === null);
        
        $node = $this->em->getRepository(self::TEST_ENTITY_CATEGORY)
            ->findOneByTitle('Fruits');
        $left = $meta->getReflectionProperty('lft')->getValue($node);
        $right = $meta->getReflectionProperty('rgt')->getValue($node);
        
        $this->assertEquals($left, 2);
        $this->assertEquals($right, 3);
        $this->assertEquals('Food', $node->getParent()->getTitle());
        
        $node = $this->em->getRepository(self::TEST_ENTITY_CATEGORY)
            ->findOneByTitle('Cabbages');
        $left = $meta->getReflectionProperty('lft')->getValue($node);
        $right = $meta->getReflectionProperty('rgt')->getValue($node);
        
        $this->assertEquals($left, 4);
        $this->assertEquals($right, 5);
        $this->assertEquals('Food', $node->getParent()->getTitle());
    }
    
    public function testVerificationAndRecover()
    {
        $repo = $this->em->getRepository(self::TEST_ENTITY_CATEGORY);
        $meta = $this->em->getClassMetadata(self::TEST_ENTITY_CATEGORY);
        $this->_populateMore();
        // test verification of tree
        
        $this->assertTrue($repo->verify());
        
        // now lets brake something
        
        $dql = 'UPDATE ' . self::TEST_ENTITY_CATEGORY . ' node';
        $dql .= ' SET node.lft = 1';
        $dql .= ' WHERE node.id = 8';
        $q = $this->em->createQuery($dql);
        $q->getSingleScalarResult();
        
        $this->em->clear(); // must clear cached entities
        
        // verify again
        
        $result = $repo->verify();
        $this->assertTrue(is_array($result));
        
        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey(1, $result);
        $this->assertArrayHasKey(2, $result);
        
        $duplicate = $result[0];
        $missing = $result[1];
        $invalidLeft = $result[2];
        
        $this->assertEquals('index [1], duplicate', $duplicate);
        $this->assertEquals('index [11], missing', $missing);
        $this->assertEquals('node [8] left is less than parent`s [4] left value', $invalidLeft);
        
        // test recover functionality
        
        $repo->recover();
        $this->em->clear(); // must clear cached entities
        
        $this->assertTrue($repo->verify());
    }
    
    protected function _populateMore()
    {
        $vegies = $this->em->getRepository(self::TEST_ENTITY_CATEGORY)
            ->findOneByTitle('Vegitables');
            
        $cabbages = new Category();
        $cabbages->setParent($vegies);
        $cabbages->setTitle('Cabbages');
        
        $onions = new Category();
        $onions->setParent($vegies);
        $onions->setTitle('Onions');
        
        $this->em->persist($cabbages);
        $this->em->persist($onions);
        $this->em->flush();
        $this->em->clear();
    }
    
    protected function _populate()
    {
        $root = new Category();
        $root->setTitle("Food");
        
        $root2 = new Category();
        $root2->setTitle("Sports");
        
        $child = new Category();
        $child->setTitle("Fruits");
        $child->setParent($root);
        
        $child2 = new Category();
        $child2->setTitle("Vegitables");
        $child2->setParent($root);
        
        $childsChild = new Category();
        $childsChild->setTitle("Carrots");
        $childsChild->setParent($child2);
        
        $potatoes = new Category();
        $potatoes->setTitle("Potatoes");
        $potatoes->setParent($child2);
        
        $this->em->persist($root);
        $this->em->persist($root2);
        $this->em->persist($child);
        $this->em->persist($child2);
        $this->em->persist($childsChild);
        $this->em->persist($potatoes);
        $this->em->flush();
        $this->em->clear();
    }
}
