<?php

namespace Gedmo\Loggable;

use Loggable\Fixture\Document\Article,
    Loggable\Fixture\Document\Comment;

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
    const TEST_CLASS_COMMENT = 'Loggable\Fixture\Document\Comment';

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
        $loggableListener::setUser('jules');
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
    }

    protected function tearDown()
    {
        $this->clearLogs();
    }

    public function testLoggableAllActions()
    {
        $art0 = new Article();

        // action create

        $art0->setTitle('My Title');
        $this->dm->persist($art0);
        $this->dm->flush();

        $logs = $this->getLogs();
        $this->assertEquals(1, count($logs), 'a log is created');
        $log = $logs->getSingleResult();

        $this->assertNotEquals(null, $log);
        $this->assertTrue($log instanceof Document\HistoryLog, 'a log instance of Document\HistoryLog');

        $this->assertEquals('create', $log->getAction());
        $this->assertEquals(get_class($art0), $log->getObjectClass());
        $this->assertEquals($art0->getId(), $log->getForeignKey());
        $this->assertEquals('jules', $log->getUser());

        $this->clearLogs();

        // action update

        $art0->setTitle('Another Title');
        $this->dm->persist($art0);
        $this->dm->flush();

        $logs = $this->getLogs();
        $this->assertEquals(1, count($logs), 'a log is created');
        $log = $logs->getSingleResult();
        $this->assertNotEquals(null, $log);
        $this->assertTrue($log instanceof Document\HistoryLog, 'a log instance of Document\HistoryLog');

        $this->assertEquals('update', $log->getAction());
        $this->assertEquals(get_class($art0), $log->getObjectClass());
        $this->assertEquals($art0->getId(), $log->getForeignKey());
        $this->assertEquals('jules', $log->getUser());

        $this->clearLogs();

        // action delete

        $articleId = $art0->getId();
        $this->dm->remove($art0);
        $this->dm->flush();

        $logs = $this->getLogs();
        $this->assertEquals(1, count($logs), 'a log is created');
        $log = $logs->getSingleResult();
        $this->assertNotEquals(null, $log);
        $this->assertTrue($log instanceof Document\HistoryLog, 'a log instance of Document\HistoryLog');

        $this->assertEquals('delete', $log->getAction());
        $this->assertEquals(get_class($art0), $log->getObjectClass());
        $this->assertEquals($articleId, $log->getForeignKey());
        $this->assertEquals('jules', $log->getUser());
    }

    public function testLoggableNotAllowedAction()
    {
        $comment = new Comment();
        $comment->setTitle('My Title');

        $this->dm->persist($comment);
        $this->dm->flush();
        $this->assertEquals(1, $this->getLogs()->count());
        $this->clearLogs();

        $comment->setTitle('Another Title');
        $this->dm->persist($comment);
        $this->dm->flush();
        $this->assertEquals(0, $this->getLogs()->count());
    }

    private function getLogs()
    {
        return $this->dm->createQueryBuilder('Gedmo\Loggable\Document\HistoryLog')
            ->select()
            ->getQuery()
            ->execute()
        ;
    }

    private function clearLogs()
    {
        $this->dm->createQueryBuilder('Gedmo\Loggable\Document\HistoryLog')
            ->remove()
            ->getQuery()
            ->execute()
        ;
    }
}