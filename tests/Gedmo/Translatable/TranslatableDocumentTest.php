<?php

namespace Gedmo\Translatable;

use Translatable\Fixture\Document\Article;

/**
 * These are tests for Translatable behavior ODM implementation
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Translatable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TranslatableDocumentTest extends \PHPUnit_Framework_TestCase
{
    const TEST_CLASS_ARTICLE = 'Translatable\Fixture\Document\Article';
    const TEST_CLASS_TRANSLATION = 'Gedmo\Translatable\Document\Translation';
    
    /**
     * @var DocumentManager
     */
    private $dm;
    private $translationListener;
    private $articleId;
    
    public function setUp()
    {
        $config = new \Doctrine\ODM\MongoDB\Configuration();
        $config->setProxyDir(__DIR__ . '/Proxy');
        $config->setProxyNamespace('Gedmo\Translatable\Proxy');
        $config->setHydratorDir(__DIR__ . '/Hydrator');
        $config->setHydratorNamespace('Hydrator');
        $config->setDefaultDB('gedmo_translatable_tests');
        
        
        $config->setLoggerCallable(function(array $log) {
            print_r($log);
        });
        
        
        $reader = new \Doctrine\Common\Annotations\AnnotationReader();
        $reader->setDefaultAnnotationNamespace('Doctrine\ODM\MongoDB\Mapping\\');
        $config->setMetadataDriverImpl(
            new \Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver(
                $reader, 
                array(
                    __DIR__ . '/Fixture/Document', 
                    __DIR__ . '/../../../lib/Gedmo/Translatable/Document'
                )
            )
        );
        
        $evm = new \Doctrine\Common\EventManager();
        $sluggableListener = new \Gedmo\Sluggable\ODM\MongoDB\SluggableListener();
        $evm->addEventSubscriber($sluggableListener);
        $this->translationListener = new \Gedmo\Translatable\ODM\MongoDB\TranslationListener();
        $evm->addEventSubscriber($this->translationListener);
        
        if (!class_exists('Mongo')) {
            $this->markTestSkipped('Missing Mongo extension.');
        }
        
        try {
            $this->dm = \Doctrine\ODM\MongoDB\DocumentManager::create(
                new \Doctrine\MongoDB\Connection(),
                $config, 
                $evm
            );
        
            $this->populate();
        } catch (\MongoException $e) {
            $this->markTestSkipped('Doctrine MongoDB ODM connection problem.');
        }
    }
    
    public function testTranslation()
    {
        // test inserted translations
        $repo = $this->dm->getRepository(self::TEST_CLASS_ARTICLE);
        $article = $repo->findOneByTitle('Title EN');
        
        $transRepo = $this->dm->getRepository(self::TEST_CLASS_TRANSLATION);
        $this->assertTrue($transRepo instanceof Document\Repository\TranslationRepository);
        
        $translations = $transRepo->findTranslations($article);
        $this->assertEquals(1, count($translations));
        
        $this->assertArrayHasKey('en_us', $translations);
        $this->assertArrayHasKey('title', $translations['en_us']);
        $this->assertEquals('Title EN', $translations['en_us']['title']);
        
        $this->assertArrayHasKey('code', $translations['en_us']);
        $this->assertEquals('Code EN', $translations['en_us']['code']);
        
        $this->assertArrayHasKey('slug', $translations['en_us']);
        $this->assertEquals('title-en-code-en', $translations['en_us']['slug']);
        
        // test second translations
        $this->translationListener->setTranslatableLocale('de_de');
        $article->setTitle('Title DE');
        $article->setCode('Code DE');
        
        $this->dm->persist($article);
        $this->dm->flush();
        $this->dm->clear();
        
        $article = $repo->find($this->articleId);
        $translations = $transRepo->findTranslations($article);
        $this->assertEquals(2, count($translations));
        
        $this->assertArrayHasKey('de_de', $translations);
        $this->assertArrayHasKey('title', $translations['de_de']);
        $this->assertEquals('Title DE', $translations['de_de']['title']);
        
        $this->assertArrayHasKey('code', $translations['de_de']);
        $this->assertEquals('Code DE', $translations['de_de']['code']);
        
        $this->assertArrayHasKey('slug', $translations['de_de']);
        $this->assertEquals('title-de-code-de', $translations['de_de']['slug']);
        
        // test value update
        $this->dm->clear();
        $this->translationListener->setTranslatableLocale('en_us');
        $article = $repo->find($this->articleId);
        
        $this->assertEquals('Title EN', $article->getTitle());
        $this->assertEquals('Code EN', $article->getCode());
        $this->assertEquals('title-en-code-en', $article->getSlug());
        
        // test removal of translations
        $this->dm->remove($article);
        $this->dm->flush();
        $this->dm->clear();

        $article = $repo->find($this->articleId);
        $this->assertTrue(is_null($article));
        
        $translations = $transRepo->findTranslationsByObjectId($this->articleId);
        $this->assertEquals(0, count($translations));
    }
    
    private function populate()
    {
        $qb = $this->dm->createQueryBuilder(self::TEST_CLASS_ARTICLE);
        $q = $qb->remove()
            ->getQuery();
        $q->execute();
        
        $qb = $this->dm->createQueryBuilder(self::TEST_CLASS_TRANSLATION);
        $q = $qb->remove()
            ->getQuery();
        $q->execute();
        
        $art0 = new Article();
        $art0->setTitle('Title EN');
        $art0->setCode('Code EN');
        
        $this->dm->persist($art0);
        $this->dm->flush();
        $this->articleId = $art0->getId();
        $this->dm->clear();
    }
}