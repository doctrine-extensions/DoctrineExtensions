<?php

namespace Gedmo\Loggable;

use Loggable\Fixture\Document\Article;

/**
 * These are tests for loggable behavior
 *
 * @author Boussekeyt Jules <jules.boussekeyt@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @package Gedmo.Loggable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class LoggableDocumentTest extends \PHPUnit_Framework_TestCase
{
    const TEST_CLASS_ARTICLE = 'Loggable\Fixture\Document\Article';

    /**
     * @var DocumentManager
     */
    private $dm;

    public function setUp()
    {
        $config = new \Doctrine\ODM\MongoDB\Configuration();
        $config->setProxyDir(__DIR__ . '/Proxy');
        $config->setProxyNamespace('Gedmo\Loggable\Proxies');
        $config->setHydratorDir(__DIR__ . '/Hydrator');
        $config->setHydratorNamespace('Hydrator');
        $config->setDefaultDB('gedmo_loggable_tests');


        $config->setLoggerCallable(function(array $log) {
            print_r($log);
        });


        $reader = new \Doctrine\Common\Annotations\AnnotationReader();
        $reader->setDefaultAnnotationNamespace('Doctrine\ODM\MongoDB\Mapping\\');
        $config->setMetadataDriverImpl(
            new \Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver($reader, __DIR__ . '/Document')
        );

        $evm = new \Doctrine\Common\EventManager();
        $loggableListener = new ODM\MongoDB\LoggableListener();
        Configuration::setUser('jules');
        $evm->addEventSubscriber($loggableListener);

        if (!class_exists('Mongo')) {
            $this->markTestSkipped('Missing Mongo extension.');
        }

        try {
            $this->dm = \Doctrine\ODM\MongoDB\DocumentManager::create(
                new \Doctrine\MongoDB\Connection(),
                $config,
                $evm
            );
        } catch (\MongoException $e) {
            $this->markTestSkipped('Doctrine MongoDB ODM connection problem.');
        }

        // if previous test failed
        $this->clearLogs();
    }

    public function testLogGeneration()
    {
        $collection = $this->dm->getDocumentCollection('Gedmo\Loggable\Document\Log');

        $this->assertEquals(0, $collection->count());

        $art0 = new Article();
        $art0->setTitle('My Title');

        $this->dm->persist($art0);
        $this->dm->flush();

        $log = $this->dm->getRepository('Gedmo\Loggable\Document\Log')->findOneBy(array());
        $this->assertNotEquals(null, $log);
        
        $this->assertEquals('create', $log->getAction());
        $this->assertEquals((string) $art0, $log->getObject());
        $this->assertEquals('jules', $log->getUser());

        $this->clearLogs();
    }

    private function clearLogs()
    {
        $this->dm->getDocumentCollection('Gedmo\Loggable\Document\Log')->drop();
    }
}