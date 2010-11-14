<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\Util\Debug,
    Gedmo\Translatable\Translatable,
    Gedmo\Translatable\Entity\Translation,
    Gedmo\Translatable\TranslationListener,
    Sluggable\Fixture\TranslatableArticle,
    Sluggable\Fixture\Comment,
    Sluggable\Fixture\Page;

/**
 * These are tests for translatable behavior
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Translatable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TranslatableSlugTest extends \PHPUnit_Framework_TestCase
{
    private $articleId;
    private $em;
    private $translationListener;
    
    const TEST_CLASS_ARTICLE = 'Sluggable\Fixture\TranslatableArticle';
    const TEST_CLASS_COMMENT = 'Sluggable\Fixture\Comment';
    const TEST_CLASS_PAGE = 'Sluggable\Fixture\Page';
    const TRANSLATION_CLASS = 'Gedmo\Translatable\Entity\Translation';

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
        $this->translationListener = new TranslationListener();
        $this->translationListener->setTranslatableLocale('en_us');
        $evm->addEventSubscriber($sluggableListener);
        $evm->addEventSubscriber($this->translationListener);
        $this->em = \Doctrine\ORM\EntityManager::create($conn, $config, $evm);

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $schemaTool->dropSchema(array());
        $schemaTool->createSchema(array(
            $this->em->getClassMetadata(self::TEST_CLASS_ARTICLE),
            $this->em->getClassMetadata(self::TEST_CLASS_COMMENT),
            $this->em->getClassMetadata(self::TEST_CLASS_PAGE),
            $this->em->getClassMetadata(self::TRANSLATION_CLASS)
        ));

        $article = new TranslatableArticle();
        $article->setTitle('the title');
        $article->setCode('my code');
        
        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();
        $this->articleId = $article->getId();
    }
    
    public function testSlugAndTranslation()
    {
        $article = $this->em->find(self::TEST_CLASS_ARTICLE, $this->articleId);
        $this->assertTrue($article instanceof Translatable && $article instanceof Sluggable);
        $this->assertEquals($article->getSlug(), 'the-title-my-code');
        $repo = $this->em->getRepository(self::TRANSLATION_CLASS);        
        
        $translations = $repo->findTranslations($article);
        $this->assertEquals(count($translations), 1);
        $this->assertArrayHasKey('en_us', $translations);
        $this->assertEquals(3, count($translations['en_us']));
        
        $this->assertArrayHasKey('code', $translations['en_us']);
        $this->assertEquals('my code', $translations['en_us']['code']);
        
        $this->assertArrayHasKey('title', $translations['en_us']);
        $this->assertEquals('the title', $translations['en_us']['title']);
        
        $this->assertArrayHasKey('slug', $translations['en_us']);
        $this->assertEquals('the-title-my-code', $translations['en_us']['slug']);

        $article = $this->em->find(self::TEST_CLASS_ARTICLE, $this->articleId);
        $article->setTranslatableLocale('de_de');
        $article->setCode('code in de');
        $article->setTitle('title in de');
        
        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();
        
        $repo = $this->em->getRepository(self::TRANSLATION_CLASS);
        $translations = $repo->findTranslations($article);
        $this->assertEquals(count($translations), 2);
        $this->assertArrayHasKey('de_de', $translations);
        $this->assertEquals(3, count($translations['de_de']));
        
        $this->assertArrayHasKey('code', $translations['de_de']);
        $this->assertEquals('code in de', $translations['de_de']['code']);
        
        $this->assertArrayHasKey('title', $translations['de_de']);
        $this->assertEquals('title in de', $translations['de_de']['title']);
        
        $this->assertArrayHasKey('slug', $translations['de_de']);
        $this->assertEquals('title-in-de-code-in-de', $translations['de_de']['slug']);
    }
    
    public function testConcurrentChanges()
    {
        $page = new Page;
        $page->setContent('cont test');
        
        $a0Page = new Page;
        $a0Page->setContent('bi vv');
        
        $article0 = $this->em->find(self::TEST_CLASS_ARTICLE, $this->articleId);
        $article0->setCode('cell');
        $article0->setTitle('xx gg');
        $a0Page->addArticle($article0);
        
        $a0Comment = new Comment;
        $a0Comment->setMessage('the xx message');
        $article0->addComment($a0Comment);
        $this->em->persist($a0Comment);
        $this->em->persist($article0);
        $this->em->persist($a0Page);
        
        $article1 = new TranslatableArticle();
        $article1->setTitle('art1 test');
        $article1->setCode('cd1 test');
        
        $article2 = new TranslatableArticle();
        $article2->setTitle('art2 test');
        $article2->setCode('cd2 test');
        
        $page->addArticle($article1);
        $page->addArticle($article2);
        
        $comment1 = new Comment;
        $comment1->setMessage('mes1-test');
        $comment2 = new Comment;
        $comment2->setMessage('mes2 test');
        
        $article1->addComment($comment1);
        $article2->addComment($comment2);
        
        $this->em->persist($page);
        $this->em->persist($article1);
        $this->em->persist($article2);
        $this->em->persist($comment1);
        $this->em->persist($comment2);
        $this->em->flush();
        $this->em->clear();
        
        $this->assertEquals($page->getSlug(), 'Cont_Test');
    }
}
