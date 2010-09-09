<?php

namespace DoctrineExtensions\Sluggable;

use Doctrine\Common\Util\Debug,
    DoctrineExtensions\Translatable\Translatable,
    DoctrineExtensions\Translatable\Entity\Translation,
    DoctrineExtensions\Translatable\TranslationListener;

/**
 * These are tests for translatable behavior
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package DoctrineExtensions.Translatable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TranslatableSlugTest extends \PHPUnit_Framework_TestCase
{
    private $articleId;
    private $em;
    private $translationListener;
    const TEST_CLASS = 'DoctrineExtensions\Sluggable\TranslatableArticle';

    public function setUp()
    {
        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
        $config->setQueryCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
        $config->setProxyDir(__DIR__ . '/temp');
        $config->setProxyNamespace('DoctrineExtensions\Sluggable\Proxies');
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
        $schemaTool->createSchema(array(
            $this->em->getClassMetadata(self::TEST_CLASS),
            $this->em->getClassMetadata('DoctrineExtensions\Translatable\Entity\Translation')
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
    	$article = $this->em->find(self::TEST_CLASS, $this->articleId);
    	$this->assertTrue($article instanceof Translatable && $article instanceof Sluggable);
    	$this->assertEquals($article->getSlug(), 'the-title-my-code');
    	$repo = $this->em->getRepository('DoctrineExtensions\Translatable\Entity\Translation');        
        
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
    }
    
    public function testSecondTranslations()
    {
    	$article = $this->em->find(self::TEST_CLASS, $this->articleId);
        $article->setTranslatableLocale('de_de');
        $article->setCode('code in de');
        $article->setTitle('title in de');
        
        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();
        
        $repo = $this->em->getRepository('DoctrineExtensions\Translatable\Entity\Translation');
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
}

/**
 * @Entity
 */
class TranslatableArticle implements Sluggable, Translatable
{
    /** @Id @GeneratedValue @Column(type="integer") */
    private $id;

    /**
     * @Column(name="title", type="string", length=64)
     */
    private $title;

    /**
     * @Column(name="code", type="string", length=16)
     */
    private $code;
    
    /**
     * @Column(name="slug", type="string", length=128)
     */
    private $slug;
    
    /*
     * Used locale to override Translation listener`s locale
     */
    private $_locale;

    public function getId()
    {
        return $this->id;
    }
    
    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setCode($code)
    {
        $this->code = $code;
    }

    public function getCode()
    {
        return $this->code;
    }
    
    public function getSluggableConfiguration()
    {
        $config = new Configuration();
        $config->setSluggableFields(array('title', 'code'));
        $config->setSlugField('slug');
        return $config;
    }
    
    public function getSlug()
    {
        return $this->slug;
    }
    
    public function setSlug($slug)
    {
        $this->slug = $slug;
    }
    
    public function getTranslatableFields()
    {
        return array('title', 'code', 'slug');
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
