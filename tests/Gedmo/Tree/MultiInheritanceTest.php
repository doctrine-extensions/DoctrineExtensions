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
        $evm->addEventSubscriber(new \Gedmo\Timestampable\TimestampableListener());
        $evm->addEventSubscriber(new \Gedmo\Sluggable\SluggableListener());
        $evm->addEventSubscriber(new \Gedmo\Tree\TreeListener());
        $translationListener = new \Gedmo\Translatable\TranslationListener();
        $translationListener->setTranslatableLocale('en_us');
        $evm->addEventSubscriber($translationListener);
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
        $meta = $this->em->getClassMetadata(self::TEST_ENTITY_CLASS);
        $repo = $this->em->getRepository(self::TEST_ENTITY_CLASS);
        
        $food = $repo->findOneByIdentifier('food');
        $left = $meta->getReflectionProperty('lft')->getValue($food);
        $right = $meta->getReflectionProperty('rgt')->getValue($food);
        
        $this->assertEquals(1, $left);
        $this->assertTrue($food->getCreated() !== null);
        $this->assertTrue($food->getUpdated() !== null);
        
        $translationRepo = $this->em->getRepository(self::TEST_ENTITY_TRANSLATION);
        $translations = $translationRepo->findTranslations($food);
        
        $this->assertEquals(1, count($translations));
        $this->assertEquals('food', $food->getSlug());
        $this->assertEquals(2, count($translations['en_us']));
    }
    
    /**
     * Test case for github issue#7
     * Child count is invalid resulting in SINGLE_TABLE and JOINED 
     * inheritance mapping. Also getChildren, getPath results are invalid
     */
    public function testCaseGithubIssue7()
    {
        $repo = $this->em->getRepository(self::TEST_ENTITY_CLASS);
        $vegies = $repo->findOneByTitle('Vegitables');

        $count = $repo->childCount($vegies, true/*direct*/);
        $this->assertEquals(3, $count);
        
        $children = $repo->children($vegies, true);
        $this->assertEquals(3, count($children));
        
        // node repository will not find it
        $cabbage = $this->em->getRepository('Tree\Fixture\BaseNode')->findOneByIdentifier('cabbage');
        $path = $repo->getPath($cabbage);
        $this->assertEquals(3, count($path));
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
