<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\Util\Debug,
    Sluggable\Fixture\ConfigurationArticle;

/**
 * These are tests for translatable behavior
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Translatable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SluggableConfigurationTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ENTITY_CLASS = 'Sluggable\Fixture\ConfigurationArticle';
    
    private $articleId;
    private $em;

    public function setUp()
    {        
        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
        $config->setQueryCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
        $config->setProxyDir(__DIR__ . '/Proxy');
        $config->setProxyNamespace('Gedmo\Sluggable\Proxies');
        $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver());

        $conn = array(
            'driver' => 'pdo_sqlite',
            'memory' => true,
        );

        //$config->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());
        
        $evm = new \Doctrine\Common\EventManager();
        $sluggableListener = new SluggableListener();
        $evm->addEventSubscriber($sluggableListener);
        $this->em = \Doctrine\ORM\EntityManager::create($conn, $config, $evm);

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $schemaTool->dropSchema(array());
        $schemaTool->createSchema(array(
            $this->em->getClassMetadata(self::TEST_ENTITY_CLASS)
        ));

        $article = new ConfigurationArticle();
        $article->setTitle('the title');
        $article->setCode('my code');
        
        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();
        $this->articleId = $article->getId();
    }
    
    public function testInsertedNewSlug()
    {
        $article = $this->em->find(
            self::TEST_ENTITY_CLASS, 
            $this->articleId
        );
        
        $this->assertTrue($article instanceof Sluggable);
        $this->assertEquals($article->getSlug(), 'the-title-my-code');
    }
    
    public function testNonUniqueSlugGeneration()
    {
        for ($i = 0; $i < 5; $i++) {
            $article = new ConfigurationArticle();
            $article->setTitle('the title');
            $article->setCode('my code');
            
            $this->em->persist($article);
            $this->em->flush();
            $this->em->clear();
            $this->assertEquals($article->getSlug(), 'the-title-my-code');
        }
    }
    
    public function testSlugLimit()
    {
        $long = 'the title the title the title the title the';
        $article = new ConfigurationArticle();
        $article->setTitle($long);
        $article->setCode('my code');
            
        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();

        $shorten = $article->getSlug();
        $this->assertEquals(strlen($shorten), 32);
    }
    
    public function testNonUpdatableSlug()
    {
        $article = $this->em->find(
            self::TEST_ENTITY_CLASS, 
            $this->articleId
        );
        $article->setTitle('the title updated');
        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();
        
        $this->assertEquals($article->getSlug(), 'the-title-my-code');
    }
}

