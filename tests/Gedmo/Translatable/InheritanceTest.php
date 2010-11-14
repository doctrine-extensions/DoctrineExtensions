<?php

namespace Gedmo\Translatable;

use Doctrine\Common\Util\Debug,
    Translatable\Fixture\TemplatedArticle;

/**
 * These are tests for translatable behavior
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Translatable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class InheritanceTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ENTITY_CLASS = 'Translatable\Fixture\TemplatedArticle';
    const TRANSLATION_CLASS = 'Gedmo\Translatable\Entity\Translation';
    
    private $translatableListener;
    private $em;

    public function setUp()
    {
        $classLoader = new \Doctrine\Common\ClassLoader('Translatable\Fixture', __DIR__ . '/../');
        $classLoader->register();
        
        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
        $config->setQueryCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
        $config->setProxyDir(__DIR__ . '/Proxy');
        $config->setProxyNamespace('Gedmo\Translatable\Proxies');
        $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver());

        $conn = array(
            'driver' => 'pdo_sqlite',
            'memory' => true,
        );

        //$config->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());
        
        $evm = new \Doctrine\Common\EventManager();
        $this->translatableListener = new TranslationListener();
        $this->translatableListener->setTranslatableLocale('en_us');
        $evm->addEventSubscriber($this->translatableListener);
        $this->em = \Doctrine\ORM\EntityManager::create($conn, $config, $evm);

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $schemaTool->dropSchema(array());
        $schemaTool->createSchema(array(
            $this->em->getClassMetadata(self::TEST_ENTITY_CLASS),
            $this->em->getClassMetadata(self::TRANSLATION_CLASS)
        ));
    }
    
    public function testTranslations()
    {
        $article = new TemplatedArticle();
        $article->setName('name in en');
        $article->setContent('content in en');
        $article->setTitle('title in en');
        
        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();
        
        $repo = $this->em->getRepository(self::TRANSLATION_CLASS);
        $this->assertTrue($repo instanceof Repository\TranslationRepository);
        
        $translations = $repo->findTranslations($article);
        $this->assertEquals(1, count($translations));
        $this->assertArrayHasKey('en_us', $translations);
        $this->assertEquals(3, count($translations['en_us']));
        
        $this->assertArrayHasKey('name', $translations['en_us']);
        $this->assertEquals('name in en', $translations['en_us']['name']);
        
        $this->assertArrayHasKey('title', $translations['en_us']);
        $this->assertEquals('title in en', $translations['en_us']['title']);
        
        $this->assertArrayHasKey('content', $translations['en_us']);
        $this->assertEquals('content in en', $translations['en_us']['content']);
        // test second translations
        $article = $this->em->getRepository(self::TEST_ENTITY_CLASS)->find(1);
        $this->translatableListener->setTranslatableLocale('de_de');
        $article->setName('name in de');
        $article->setContent('content in de');
        $article->setTitle('title in de');
        
        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();
        
        $translations = $repo->findTranslations($article);
        $this->assertEquals(2, count($translations));
        $this->assertArrayHasKey('de_de', $translations);
        
        $this->assertArrayHasKey('name', $translations['de_de']);
        $this->assertEquals('name in de', $translations['de_de']['name']);
        
        $this->assertArrayHasKey('title', $translations['de_de']);
        $this->assertEquals('title in de', $translations['de_de']['title']);
        
        $this->assertArrayHasKey('content', $translations['de_de']);
        $this->assertEquals('content in de', $translations['de_de']['content']);
        
        $this->translatableListener->setTranslatableLocale('en_us');
    }
}
