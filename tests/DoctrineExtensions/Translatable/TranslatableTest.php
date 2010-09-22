<?php

namespace DoctrineExtensions\Translatable;

use Doctrine\Common\Util\Debug;

/**
 * These are tests for translatable behavior
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package DoctrineExtensions.Translatable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TranslatableTest extends \PHPUnit_Framework_TestCase
{
    private $articleId;
    private $translatableListener;
    /**
     * @var EntityManager
     */
    private $em;

    public function setUp()
    {
        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
        $config->setQueryCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
        $config->setProxyDir(__DIR__ . '/temp');
        $config->setProxyNamespace('DoctrineExtensions\Translatable\Proxies');
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
        $schemaTool->createSchema(array(
            $this->em->getClassMetadata('DoctrineExtensions\Translatable\Article'),
            $this->em->getClassMetadata('DoctrineExtensions\Translatable\Comment'),
            $this->em->getClassMetadata('DoctrineExtensions\Translatable\Entity\Translation'),
        ));
    }
    
    public function testFixtureGeneratedTranslations()
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
        
    	$repo = $this->em->getRepository('DoctrineExtensions\Translatable\Entity\Translation');
    	$this->assertTrue($repo instanceof Repository\TranslationRepository);
    	
        $article = $this->em->find('DoctrineExtensions\Translatable\Article', $this->articleId);
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
        	$this->assertTrue($comment instanceof Translatable);
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
            'DoctrineExtensions\Translatable\Article', 
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
        
        $repo = $this->em->getRepository('DoctrineExtensions\Translatable\Entity\Translation');
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
            'DoctrineExtensions\Translatable\Article', 
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
        
        $repo = $this->em->getRepository('DoctrineExtensions\Translatable\Entity\Translation');
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
            $this->assertTrue($comment instanceof Translatable);
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
            'DoctrineExtensions\Translatable\Article', 
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
    }
}

/**
 * @Entity
 */
class Article implements Translatable
{
    /** @Id @GeneratedValue @Column(type="integer") */
    private $id;

    /**
     * @Column(name="title", type="string", length=128)
     */
    private $title;

    /**
     * @Column(name="content", type="text")
     */
    private $content;
    
    /*
     * Used locale to override Translation listener`s locale
     */
    private $_locale;
    
    /**
     * @OneToMany(targetEntity="Comment", mappedBy="article")
     */
    private $comments;

    public function getId()
    {
        return $this->id;
    }

    public function addComment(Comment $comment)
    {
        $comment->setArticle($this);
        $this->comments[] = $comment;
    }

    public function getComments()
    {
        return $this->comments;
    }
    
    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setContent($content)
    {
        $this->content = $content;
    }

    public function getContent()
    {
        return $this->content;
    }
    
    public function getTranslatableFields()
    {
        return array('title', 'content');
    }
    
    public function setTranslatableLocale($locale)
    {
        $this->_locale = $locale;
    }
    
    public function getTranslatableLocale()
    {
        return $this->_locale;
    }
}

/**
 * @Entity
 */
class Comment implements Translatable
{
    /** @Id @GeneratedValue @Column(type="integer") */
    private $id;

    /**
     * @Column(name="subject", type="string", length=128)
     */
    private $subject;

    /**
     * @Column(name="message", type="text")
     */
    private $message;
    
    /**
     * @ManyToOne(targetEntity="Article", inversedBy="comments")
     */
    private $article;
    
    /*
     * Used locale to override Translation listener`s locale
     */
    private $_locale;

    public function setArticle($article)
    {
        $this->article = $article;
    }

    public function getId()
    {
        return $this->id;
    }
    
    public function setSubject($subject)
    {
        $this->subject = $subject;
    }

    public function getSubject()
    {
        return $this->subject;
    }

    public function setMessage($message)
    {
        $this->message = $message;
    }

    public function getMessage()
    {
        return $this->message;
    }
    
    public function getTranslatableFields()
    {
        return array('subject', 'message');
    }
    
    public function setTranslatableLocale($locale)
    {
        $this->_locale = $locale;
    }
    
    public function getTranslatableLocale()
    {
        return $this->_locale;
    }
}
