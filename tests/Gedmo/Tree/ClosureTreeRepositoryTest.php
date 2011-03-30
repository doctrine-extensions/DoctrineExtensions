<?php

namespace Gedmo\Tree;

use Doctrine\Common\Util\Debug;
use Tree\Fixture\Closure\Category;
use Tool\Logging\DBAL\QueryAnalyzer;

/**
 * These are tests for Tree behavior
 * 
 * @author Gustavo Adrian <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Tree
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class ClosureTreeRepositoryTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ENTITY_CLASS = "Tree\Fixture\Closure\Category";
    const TEST_CLOSURE_CLASS = "Tree\Fixture\Closure\CategoryClosure";
    const TEST_BASE_CLOSURE_CLASS = "Gedmo\Tree\Entity\AbstractClosure";
    private $em;

    public function setUp()
    {        
        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
        $config->setQueryCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
        $config->setProxyDir(TESTS_TEMP_DIR);
        $config->setProxyNamespace('Gedmo\Tree\Proxies');
        $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver());

        $conn = array(
            'driver' => 'pdo_sqlite',
            'memory' => true,
        );
        
        /*$conn = array(
            'driver' => 'pdo_mysql',
            'host' => '127.0.0.1',
            'dbname' => 'closure',
            'user' => 'root',
            'password' => ''
        );*/
        
        $evm = new \Doctrine\Common\EventManager();
        $treeListener = new TreeListener(TreeListener::TYPE_CLOSURE);
        $evm->addEventSubscriber($treeListener);
        $this->em = \Doctrine\ORM\EntityManager::create($conn, $config, $evm);
        
        $schema	= array(
            $this->em->getClassMetadata(self::TEST_BASE_CLOSURE_CLASS),
            $this->em->getClassMetadata(self::TEST_CLOSURE_CLASS),
            $this->em->getClassMetadata(self::TEST_ENTITY_CLASS),
        );
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $schemaTool->dropSchema( $schema );
        $schemaTool->createSchema( $schema );
        
        $this->analyzer = new QueryAnalyzer(
            $this->em->getConnection()->getDatabasePlatform()
        );
        $config->setSQLLogger($this->analyzer);
        
        $this->populate();
    }
    
    public function test_childCount_returnsNumberOfChilds()
    {        
        $repo = $this->em->getRepository( self::TEST_ENTITY_CLASS );
        $food = $repo->findOneByTitle( 'Food' );
		$closureRepo = $this->em->getRepository( self::TEST_CLOSURE_CLASS );
        $childCount = $closureRepo->childCount( $food );
        
        $this->assertEquals( $childCount, 4 );
    }
	
    public function test_childCount_returnsNumberOfDirectChilds()
    {        
        $repo = $this->em->getRepository(self::TEST_ENTITY_CLASS);
        $food = $repo->findOneByTitle('Food');
		$closureRepo = $this->em->getRepository(self::TEST_CLOSURE_CLASS);
        $childCount = $closureRepo->childCount($food, true);
		
		$this->assertEquals($childCount, 2);
    }
    
    
    
    private function populate()
    {
        $root = new Category();
        $root->setTitle("Food");
        $this->food = $root;
        
        $root2 = new Category();
        $root2->setTitle("Sports");
        $this->sports = $root2;
        
        $child = new Category();
        $child->setTitle("Fruits");
        $child->setParent($root);
        $this->fruits = $child;
        
        $child2 = new Category();
        $child2->setTitle("Vegitables");
        $child2->setParent($root);
        $this->vegitables = $child2;
        
        $childsChild = new Category();
        $childsChild->setTitle("Carrots");
        $childsChild->setParent($child2);
        $this->carrots = $childsChild;
        
        $potatoes = new Category();
        $potatoes->setTitle("Potatoes");
        $potatoes->setParent($child2);
        $this->potatoes = $potatoes;
        
        $this->em->persist($this->food);
        $this->em->persist($this->sports);
        $this->em->persist($this->fruits);
        $this->em->persist($this->vegitables);
        $this->em->persist($this->carrots);
        $this->em->persist($this->potatoes);
		
        $this->em->flush();
        $this->em->clear();
    }
}
