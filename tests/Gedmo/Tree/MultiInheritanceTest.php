<?php

namespace Gedmo\Tree;

use Doctrine\Common\Util\Debug;

/**
 * These are tests for Tree behavior
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Tree
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class MultiInheritanceTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ENTITY_CLASS = "Tree\Fixture\Node";
    const TEST_BASE_NODE_CLASS = "Tree\Fixture\BaseNode";
    const TEST_TREE_NODE_CLASS = "Tree\Fixture\ANode";
    const TEST_ENTITY_TRANSLATION = "Gedmo\Translatable\Entity\Translation";
    private $em;

    public function setUp()
    {
        $classLoader = new \Doctrine\Common\ClassLoader('Tree\Fixture', __DIR__ . '/../');
        $classLoader->register();
        
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
            $this->em->getClassMetadata(self::TEST_TREE_NODE_CLASS),
            $this->em->getClassMetadata(self::TEST_BASE_NODE_CLASS),
            $this->em->getClassMetadata(self::TEST_ENTITY_CLASS),
            $this->em->getClassMetadata(self::TEST_ENTITY_TRANSLATION)
        ));
        
        $this->_populate();
    }
    
    public function testInheritance()
    {        
        
    }
    
    private function _populate()
    {
        $root = new \Tree\Fixture\Node();
        $root->setTitle("Food");
        $root->setIdentifier('food');
        
        $root2 = new \Tree\Fixture\Node();
        $root2->setTitle("Sports");
        $root2->setIdentifier('sport');
        
        $child = new \Tree\Fixture\Node();
        $child->setTitle("Fruits");
        $child->setParent($root);
        $child->setIdentifier('fruit');
        
        $child2 = new \Tree\Fixture\Node();
        $child2->setTitle("Vegitables");
        $child2->setParent($root);
        $child2->setIdentifier('vegie');
        
        $childsChild = new \Tree\Fixture\Node();
        $childsChild->setTitle("Carrots");
        $childsChild->setParent($child2);
        $childsChild->setIdentifier('carrot');
        
        $potatoes = new \Tree\Fixture\Node();
        $potatoes->setTitle("Potatoes");
        $potatoes->setParent($child2);
        $potatoes->setIdentifier('potatoe');
        
        $cabbages = new \Tree\Fixture\BaseNode();
        $cabbages->setIdentifier('cabbage');
        $cabbages->setParent($child2);
        
        $this->em->persist($root);
        $this->em->persist($root2);
        $this->em->persist($child);
        $this->em->persist($child2);
        $this->em->persist($childsChild);
        $this->em->persist($potatoes);
        $this->em->persist($cabbages);
        $this->em->flush();
        $this->em->clear();
    }
}
