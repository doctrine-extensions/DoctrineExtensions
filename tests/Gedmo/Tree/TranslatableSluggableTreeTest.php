<?php

namespace Gedmo\Tree;

use Doctrine\Common\Util\Debug,
    Tree\Fixture\BehavioralCategory,
    Tree\Fixture\Article,
    Tree\Fixture\Comment,
    Gedmo\Translatable\TranslationListener,
    Gedmo\Translatable\Entity\Translation,
    Gedmo\Sluggable\SluggableListener;

/**
 * These are tests for Tree behavior
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Tree
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TranslatableSluggableTreeTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ENTITY_CATEGORY = "Tree\Fixture\BehavioralCategory";
    const TEST_ENTITY_ARTICLE = "Tree\Fixture\Article";
    const TEST_ENTITY_COMMENT = "Tree\Fixture\Comment";
    const TEST_ENTITY_TRANSLATION = "Gedmo\Translatable\Entity\Translation";
    
    private $em;
    private $translationListener;

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
        $sluggableListener = new SluggableListener();
        $this->translationListener = new TranslationListener();
        $this->translationListener->setTranslatableLocale('en_us');
        $evm->addEventSubscriber($treeListener);
        $evm->addEventSubscriber($sluggableListener);
        $evm->addEventSubscriber($this->translationListener);
        $this->em = \Doctrine\ORM\EntityManager::create($conn, $config, $evm);

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $schemaTool->dropSchema(array());
        $schemaTool->createSchema(array(
            $this->em->getClassMetadata(self::TEST_ENTITY_CATEGORY),
            $this->em->getClassMetadata(self::TEST_ENTITY_ARTICLE),
            $this->em->getClassMetadata(self::TEST_ENTITY_COMMENT),
            $this->em->getClassMetadata(self::TEST_ENTITY_TRANSLATION)
        ));
        $this->_populate();
    }
    
    public function testNestedBehaviors()
    {
        $vegies = $this->em->getRepository(self::TEST_ENTITY_CATEGORY)
            ->findOneByTitle('Vegitables');
        
        $childCount = $this->em->getRepository(self::TEST_ENTITY_CATEGORY)
            ->childCount($vegies);
        $this->assertEquals(2, $childCount);
        
        // test slug
        
        $this->assertEquals('vegitables', $vegies->getSlug());
        
        // run second translation test
        
        $this->translationListener->setTranslatableLocale('de_de');
        $vegies->setTitle('Deutschebles');
        $this->em->persist($vegies);
        $this->em->flush();
        $this->em->clear();
        
        $this->translationListener->setTranslatableLocale('en_us');
        
        $vegies = $this->em->getRepository(self::TEST_ENTITY_CATEGORY)
            ->find($vegies->getId());
            
        $translations = $this->em->getRepository(self::TEST_ENTITY_TRANSLATION)
            ->findTranslations($vegies);
            
        $this->assertEquals(2, count($translations));
        $this->assertArrayHasKey('de_de', $translations);
        $this->assertArrayHasKey('en_us', $translations);
        
        $this->assertArrayHasKey('title', $translations['de_de']);
        $this->assertEquals('Deutschebles', $translations['de_de']['title']);
        
        $this->assertArrayHasKey('slug', $translations['de_de']);
        $this->assertEquals('deutschebles', $translations['de_de']['slug']);
        
        $this->assertArrayHasKey('title', $translations['en_us']);
        $this->assertEquals('Vegitables', $translations['en_us']['title']);
        
        $this->assertArrayHasKey('slug', $translations['en_us']);
        $this->assertEquals('vegitables', $translations['en_us']['slug']);
    }
    
    protected function _populate()
    {
        $root = new BehavioralCategory();
        $root->setTitle("Food");
        
        $root2 = new BehavioralCategory();
        $root2->setTitle("Sports");
        
        $child = new BehavioralCategory();
        $child->setTitle("Fruits");
        $child->setParent($root);
        
        $child2 = new BehavioralCategory();
        $child2->setTitle("Vegitables");
        $child2->setParent($root);
        
        $childsChild = new BehavioralCategory();
        $childsChild->setTitle("Carrots");
        $childsChild->setParent($child2);
        
        $potatoes = new BehavioralCategory();
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
