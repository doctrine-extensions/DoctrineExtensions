<?php

namespace Gedmo\Tree;

use Doctrine\Common\Util\Debug;
use Tree\Fixture\Closure\Category;
use Tool\Logging\DBAL\QueryAnalyzer;

/**
 * These are tests for Tree behavior
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Tree
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class ClosureTreeTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ENTITY_CLASS = "Tree\Fixture\Closure\Category";
    const TEST_CLOSURE_CLASS = "Tree\Fixture\Closure\CategoryClosure";
    const TEST_BASE_CLOSURE_CLASS = "Gedmo\Tree\Entity\AbstractClosure";
    
    /**
     * @var EntityManager
     */
    private $em;
    
    /**
     * @var QueryAnalyzer
     */
    private $analyzer;

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
        
        /*$conn = array(
            'driver' => 'pdo_mysql',
            'host' => '127.0.0.1',
            'dbname' => 'test',
            'user' => 'root',
            'password' => 'nimda'
        );*/
        
        $evm = new \Doctrine\Common\EventManager();
        $treeListener = new TreeListener(TreeListener::TYPE_CLOSURE);
        $evm->addEventSubscriber($treeListener);
        $this->em = \Doctrine\ORM\EntityManager::create($conn, $config, $evm);
        
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $schemaTool->dropSchema(array());
        $schemaTool->createSchema(array(
            $this->em->getClassMetadata(self::TEST_BASE_CLOSURE_CLASS),
            $this->em->getClassMetadata(self::TEST_CLOSURE_CLASS),
            $this->em->getClassMetadata(self::TEST_ENTITY_CLASS),
        ));
        
        $this->analyzer = new QueryAnalyzer(
            $this->em->getConnection()->getDatabasePlatform()
        );
        $config->setSQLLogger($this->analyzer);
        
        $this->populate();
    }
    
    public function tearDown()
    {
        $this->analyzer->dumpResult();
    }
    
    public function testTheTree()
    {        
        $repo = $this->em->getRepository(self::TEST_ENTITY_CLASS);
        $node = $repo->findOneByTitle('Vegitables');
        $node->setParent($repo->findOneByTitle('Fruits'));
        
        //$this->em->persist($node);
        //$this->em->flush();
    }
    
    private function populate()
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
