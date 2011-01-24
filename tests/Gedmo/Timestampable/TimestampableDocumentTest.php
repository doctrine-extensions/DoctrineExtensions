<?php

namespace Gedmo\Timestampable;

use Timestampable\Fixture\Document\Article,
    Timestampable\Fixture\Document\Type;

/**
 * These are tests for Timestampable behavior ODM implementation
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Timestampable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TimestampableDocumentTest extends \PHPUnit_Framework_TestCase
{
    const TEST_CLASS_ARTICLE = 'Timestampable\Fixture\Document\Article';
    const TEST_CLASS_TYPE = 'Timestampable\Fixture\Document\Type';
    
    /**
     * @var DocumentManager
     */
    private $dm;
    
    public function setUp()
    {
        $config = new \Doctrine\ODM\MongoDB\Configuration();
        $config->setProxyDir(__DIR__ . '/Proxy');
        $config->setProxyNamespace('Gedmo\Timestampable\Proxy');
        $config->setHydratorDir(__DIR__ . '/Hydrator');
        $config->setHydratorNamespace('Hydrator');
        $config->setDefaultDB('gedmo_timestampable_tests');
        
        
        $config->setLoggerCallable(function(array $log) {
            print_r($log);
        });
        
        
        $reader = new \Doctrine\Common\Annotations\AnnotationReader();
        $reader->setDefaultAnnotationNamespace('Doctrine\ODM\MongoDB\Mapping\\');
        $config->setMetadataDriverImpl(
            new \Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver(
                $reader, 
                array(__DIR__ . '/Fixture/Document')
            )
        );
        
        $evm = new \Doctrine\Common\EventManager();
        $timestampableListener = new \Gedmo\Timestampable\ODM\MongoDB\TimestampableListener();
        $evm->addEventSubscriber($timestampableListener);
        
        if (!extension_loaded('mongo')) {
            $this->markTestSkipped('Mongo extension is not loaded in PHP.');
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
    
    public function testTimestampable()
    {
        $repo = $this->dm->getRepository(self::TEST_CLASS_ARTICLE);
        $article = $repo->findOneByTitle('Timestampable Article');
        
        $date = new \DateTime();
        $this->assertEquals(
            time(), 
            (string)$article->getCreated()
        );
        $this->assertEquals(
            $date->format('Y-m-d H:i:s'), 
            $article->getUpdated()->format('Y-m-d H:i:s')
        );
        
        $published = new Type;
        $published->setIdentifier('published');
        $published->setTitle('Published');
        
        $article->setType($published);
        $this->dm->persist($article);
        $this->dm->persist($published);
        $this->dm->flush();
        $this->dm->clear();
        
        $article = $repo->findOneByTitle('Timestampable Article');
        $date = new \DateTime();
        $this->assertEquals(
            $date->format('Y-m-d H:i:s'), 
            $article->getPublished()->format('Y-m-d H:i:s')
        );
    }
    
    private function populate()
    {
        $qb = $this->dm->createQueryBuilder(self::TEST_CLASS_ARTICLE);
        $qb->remove()->getQuery()->execute();
        
        $qb = $this->dm->createQueryBuilder(self::TEST_CLASS_TYPE);
        $qb->remove()->getQuery()->execute();
        
        $art0 = new Article();
        $art0->setTitle('Timestampable Article');
        
        $this->dm->persist($art0);
        $this->dm->flush();
        $this->dm->clear();
    }
}