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
    const TEST_ENTITY_CLASS = 'Sluggable\Fixture\Document\Article';
    
    /**
     * @var DocumentManager
     */
    private $dm;
    
    public function setUp()
    {
        $config = new \Doctrine\ODM\MongoDB\Configuration();
        $config->setProxyDir(__DIR__ . '/Proxy');
        $config->setProxyNamespace('Gedmo\Sluggable\Proxies');
        $config->setHydratorDir(__DIR__ . '/Hydrator');
        $config->setHydratorNamespace('Hydrator');
        $config->setDefaultDB('doctrine_odm_sluggable_tests');
        
        
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
        $this->dm = \Doctrine\ODM\MongoDB\DocumentManager::create(
            new \Doctrine\MongoDB\Connection(),
            $config, 
            $evm
        );
        
    }
    
    public function testSluggable()
    {
        $art0 = new Article();
        $art0->setTitle('hello');
        $art0->setCode('code');
        
        $this->dm->persist($art0);
        $this->dm->flush();
        
        $this->assertEquals('hello-code', $art0->getSlug());
        
        $repo = $this->dm->getRepository(self::TEST_ENTITY_CLASS);
        $art = $repo->findOneByTitle('hello');
        $art->setTitle('New Title');
        
        $this->dm->persist($art);
        $this->dm->flush();
        
        $art = $repo->findOneByTitle('New Title');
        $this->assertEquals('new-title-code', $art->getSlug());
    }
}