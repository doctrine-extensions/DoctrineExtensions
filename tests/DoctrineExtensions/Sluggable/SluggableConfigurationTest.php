<?php

namespace DoctrineExtensions\Sluggable;

use Doctrine\Common\Util\Debug;

/**
 * These are tests for translatable behavior
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package DoctrineExtensions.Translatable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SluggableConfigurationTest extends \PHPUnit_Framework_TestCase
{
    private $articleId;
    private $em;

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
        $evm->addEventSubscriber($sluggableListener);
        $this->em = \Doctrine\ORM\EntityManager::create($conn, $config, $evm);

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $schemaTool->createSchema(array(
            $this->em->getClassMetadata('DoctrineExtensions\Sluggable\ArticleTest')
        ));

        $article = new ArticleTest();
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
            'DoctrineExtensions\Sluggable\ArticleTest', 
            $this->articleId
        );
        
        $this->assertTrue($article instanceof Sluggable);
        $this->assertEquals($article->getSlug(), 'the-title-my-code');
    }
    
    public function testNonUniqueSlugGeneration()
    {
    	for ($i = 0; $i < 5; $i++) {
	    	$article = new ArticleTest();
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
    	$article = new ArticleTest();
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
            'DoctrineExtensions\Sluggable\ArticleTest', 
            $this->articleId
        );
        $article->setTitle('the title updated');
        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();
        
        $this->assertEquals($article->getSlug(), 'the-title-my-code');
    }
}

/**
 * @Entity
 */
class ArticleTest implements Sluggable
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
        $config->setIsUpdatable(false);
        $config->setIsUnique(false);
        $config->setLength(32);
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
}
