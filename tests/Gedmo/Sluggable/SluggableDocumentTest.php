<?php

namespace Gedmo\Sluggable;

use Sluggable\Fixture\Document\Article;

/**
 * These are tests for sluggable behavior
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Sluggable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SluggableDocumentTest extends \PHPUnit_Framework_TestCase
{
    const TEST_CLASS_ARTICLE = 'Sluggable\Fixture\Document\Article';
    
    /**
     * @var DocumentManager
     */
    private $dm;
    
    public function setUp()
    {
        $config = new \Doctrine\ODM\MongoDB\Configuration();
        $config->setProxyDir(TESTS_TEMP_DIR);
        $config->setProxyNamespace('Gedmo\Sluggable\Proxies');
        $config->setHydratorDir(TESTS_TEMP_DIR);
        $config->setHydratorNamespace('Hydrator');
        $config->setDefaultDB('gedmo_sluggable_tests');
        
        
        $config->setLoggerCallable(function(array $log) {
            print_r($log);
        });
        
        
        $reader = new \Doctrine\Common\Annotations\AnnotationReader();
        $reader->setDefaultAnnotationNamespace('Doctrine\ODM\MongoDB\Mapping\\');
        $config->setMetadataDriverImpl(
            new \Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver($reader, __DIR__ . '/Fixture/Document')
        );
        
        $evm = new \Doctrine\Common\EventManager();
        $sluggableListener = new ODM\MongoDB\SluggableListener();
        $evm->addEventSubscriber($sluggableListener);
        
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
    
    public function testSlugGeneration()
    {
        // test insert
        $repo = $this->dm->getRepository(self::TEST_CLASS_ARTICLE);
        $article = $repo->findOneByTitle('My Title');
        
        $this->assertEquals('my-title-the-code', $article->getSlug());
        
        // test update
        $article->setTitle('New Title');
        
        $this->dm->persist($article);
        $this->dm->flush();
        $this->dm->clear();
        
        $article = $repo->findOneByTitle('New Title');
        $this->assertEquals('new-title-the-code', $article->getSlug());
    }
    
    public function testUniqueSlugGeneration()
    {
        for ($i = 0; $i < 12; $i++) {
            $article = new Article();
            $article->setTitle('My Title');
            $article->setCode('The Code');
            
            $this->dm->persist($article);
            $this->dm->flush();
            $this->dm->clear();
            $this->assertEquals($article->getSlug(), 'my-title-the-code-' . ($i + 1));
        }
    }
    
    private function populate()
    {
        $qb = $this->dm->createQueryBuilder(self::TEST_CLASS_ARTICLE);
        $q = $qb->remove()
            ->getQuery();
        $q->execute();
        
        $art0 = new Article();
        $art0->setTitle('My Title');
        $art0->setCode('The Code');
        
        $this->dm->persist($art0);
        $this->dm->flush();
        $this->dm->clear();
    }
}