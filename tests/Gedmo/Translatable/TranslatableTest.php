<?php

namespace Gedmo\Translatable;

use Doctrine\Common\Util\Debug,
    Translatable\Fixture\Article,
    Translatable\Fixture\Comment;

/**
 * These are tests for translatable behavior
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Translatable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TranslatableTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ENTITY_CLASS_ARTICLE = 'Translatable\Fixture\Article';
    const TEST_ENTITY_CLASS_COMMENT = 'Translatable\Fixture\Comment';
    private $articleId;
    private $translatableListener;
    private $em;

    public function setUp()
    {        
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
            $this->em->getClassMetadata(self::TEST_ENTITY_CLASS_ARTICLE),
            $this->em->getClassMetadata(self::TEST_ENTITY_CLASS_COMMENT),
            $this->em->getClassMetadata('Gedmo\Translatable\Entity\Translation'),
        ));
        $this->populate();
    }
    
    public function testFixtureGeneratedTranslations()
    {
        $repo = $this->em->getRepository('Gedmo\Translatable\Entity\Translation');
        $this->assertTrue($repo instanceof Repository\TranslationRepository);
        
        $article = $this->em->find(self::TEST_ENTITY_CLASS_ARTICLE, $this->articleId);
        $this->assertTrue($article instanceof Translatable);
        
        $translations = $repo->findTranslations($article);
        $this->assertEquals(count($translations), 1);
        $this->assertArrayHasKey('en_us', $translations);
        
        $this->assertArrayHasKey('content', $translations['en_us']);
        $this->assertEquals('content in en', $translations['en_us']['content']);
        
        $this->assertArrayHasKey('title', $translations['en_us']);
        $this->assertEquals('title in en', $translations['en_us']['title']);
        
        $comments = $article->getComments();
        $this->assertEquals(count($comments), 2);
        foreach ($comments as $num => $comment) {
            $translations = $repo->findTranslations($comment);
            
            $this->assertEquals(count($translations), 1);
            $this->assertArrayHasKey('en_us', $translations);
            
            $number = $num + 1;
            $this->assertArrayHasKey('subject', $translations['en_us']);
            $expected = "subject{$number} in en";
            $this->assertEquals($expected, $translations['en_us']['subject']);
            
            $this->assertArrayHasKey('message', $translations['en_us']);
            $expected = "message{$number} in en";
            $this->assertEquals($expected, $translations['en_us']['message']);
        }
        // test default locale
        $this->translatableListener->setDefaultLocale('en_us');
        $article = $this->em->find(
            self::TEST_ENTITY_CLASS_ARTICLE, 
            $this->articleId
        );
        $article->setTranslatableLocale('de_de');
        $article->setContent('content in de');
        $article->setTitle('title in de');
        
        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();
        
        $qb = $this->em->createQueryBuilder();
        $qb->select('art')
            ->from(get_class($article), 'art')
            ->where('art.id = :id');
        $q = $qb->getQuery();
        $result = $q->execute(
            array('id' => $article->getId()),
            \Doctrine\ORM\Query::HYDRATE_ARRAY
        );
        $this->assertEquals(1, count($result));
        $this->assertEquals($result[0]['title'], 'title in en');
        $this->assertEquals($result[0]['content'], 'content in en');
        
        $repo = $this->em->getRepository('Gedmo\Translatable\Entity\Translation');
        $translations = $repo->findTranslations($article);
        $this->assertEquals(count($translations), 2);
        $this->assertArrayHasKey('de_de', $translations);
        
        $this->assertArrayHasKey('content', $translations['de_de']);
        $this->assertEquals('content in de', $translations['de_de']['content']);
        
        $this->assertArrayHasKey('title', $translations['de_de']);
        $this->assertEquals('title in de', $translations['de_de']['title']);
        $this->translatableListener->setDefaultLocale('');
        // test second translations
        $article = $this->em->find(
            self::TEST_ENTITY_CLASS_ARTICLE, 
            $this->articleId
        );
        $this->translatableListener->setDefaultLocale('en_us');
        $article->setTranslatableLocale('de_de');
        $article->setContent('content in de');
        $article->setTitle('title in de');
        
        $comments = $article->getComments();
        foreach ($comments as $comment) {
            $number = preg_replace("@[^\d]+@", '', $comment->getSubject());
            $comment->setTranslatableLocale('de_de');
            $comment->setSubject("subject{$number} in de");
            $comment->setMessage("message{$number} in de");
            $this->em->persist($comment);
        }
        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();
        
        $repo = $this->em->getRepository('Gedmo\Translatable\Entity\Translation');
        $translations = $repo->findTranslations($article);
        $this->assertEquals(count($translations), 2);
        $this->assertArrayHasKey('de_de', $translations);
        
        $this->assertArrayHasKey('content', $translations['de_de']);
        $this->assertEquals('content in de', $translations['de_de']['content']);
        
        $this->assertArrayHasKey('title', $translations['de_de']);
        $this->assertEquals('title in de', $translations['de_de']['title']);
        
        $comments = $article->getComments();
        $this->assertEquals(count($comments), 2);
        foreach ($comments as $comment) {
            $translations = $repo->findTranslations($comment);
            
            $this->assertEquals(count($translations), 2);
            $this->assertArrayHasKey('de_de', $translations);
            
            $number = preg_replace("@[^\d]+@", '', $comment->getSubject());
            $this->assertArrayHasKey('subject', $translations['de_de']);
            $expected = "subject{$number} in de";
            $this->assertEquals($expected, $translations['de_de']['subject']);
            
            $this->assertArrayHasKey('message', $translations['de_de']);
            $expected = "message{$number} in de";
            $this->assertEquals($expected, $translations['de_de']['message']);
        }
        
        $this->translatableListener->setTranslatableLocale('en_us');
        $article = $this->em->find(
            self::TEST_ENTITY_CLASS_ARTICLE, 
            $this->articleId
        );
        $this->assertEquals($article->getTitle(), 'title in en');
        $this->assertEquals($article->getContent(), 'content in en');
        
        $comments = $article->getComments();
        foreach ($comments as $comment) {
            $number = preg_replace("@[^\d]+@", '', $comment->getSubject());
            
            $this->assertEquals($comment->getSubject(), "subject{$number} in en");
            $this->assertEquals($comment->getMessage(), "message{$number} in en");
        }
        // test deletion
        $article = $this->em->find(self::TEST_ENTITY_CLASS_ARTICLE, $this->articleId);
        $this->em->remove($article);
        $this->em->flush();
        $this->em->clear();

        $translations = $repo->findTranslationsByEntityId($this->articleId);
        $this->assertEquals(count($translations), 0);
    }
    
    /**
     * Translation fallback, related to issue #9 on github
     */
    public function testTranslationFallback()
    {
        $this->translatableListener->setTranslationFallback(false);
        $this->translatableListener->setTranslatableLocale('ru_RU');
        
        $article = $this->em->find(self::TEST_ENTITY_CLASS_ARTICLE, $this->articleId);
        $this->assertFalse((bool)$article->getTitle());
        $this->assertFalse((bool)$article->getContent());
        
        foreach ($article->getComments() as $comment) {
            $this->assertFalse((bool)$comment->getSubject());
            $this->assertFalse((bool)$comment->getMessage());
        }
        $this->em->clear();
        $this->translatableListener->setTranslationFallback(true);
        $article = $this->em->find(self::TEST_ENTITY_CLASS_ARTICLE, $this->articleId);
        
        $this->assertEquals($article->getTitle(), 'title in en');
        $this->assertEquals($article->getContent(), 'content in en');
    }
    
    private function populate()
    {
        $article = new Article();
        $article->setTitle('title in en');
        $article->setContent('content in en');
        
        $comment1 = new Comment();
        $comment1->setSubject('subject1 in en');
        $comment1->setMessage('message1 in en');
        
        $comment2 = new Comment();
        $comment2->setSubject('subject2 in en');
        $comment2->setMessage('message2 in en');
        
        $article->addComment($comment1);
        $article->addComment($comment2);

        $this->em->persist($article);
        $this->em->persist($comment1);
        $this->em->persist($comment2);
        $this->em->flush();
        $this->articleId = $article->getId();
        $this->em->clear();
    }
}