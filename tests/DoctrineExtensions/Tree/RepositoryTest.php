<?php

namespace DoctrineExtensions\Tree;

use Doctrine\Common\Util\Debug,
    Tree\Fixture\Category,
    Tree\Fixture\Article,
    Tree\Fixture\Comment;

/**
 * These are tests for Tree behavior
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package DoctrineExtensions.Tree
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class RepositoryTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ENTITY_CATEGORY = "Tree\Fixture\Category";
    const TEST_ENTITY_ARTICLE = "Tree\Fixture\Article";
    const TEST_ENTITY_COMMENT = "Tree\Fixture\Comment";
    private $em;

    public function setUp()
    {
        $classLoader = new \Doctrine\Common\ClassLoader('Tree\Fixture', __DIR__ . '/../');
        $classLoader->register();
        
        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
        $config->setQueryCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
        $config->setProxyDir(__DIR__ . '/temp');
        $config->setProxyNamespace('DoctrineExtensions\Tree\Proxies');
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
            $this->em->getClassMetadata(self::TEST_ENTITY_CATEGORY),
            $this->em->getClassMetadata(self::TEST_ENTITY_ARTICLE),
            $this->em->getClassMetadata(self::TEST_ENTITY_COMMENT)
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
