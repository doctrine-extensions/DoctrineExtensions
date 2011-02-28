<?php

namespace Gedmo\Loggable;

use Doctrine\Common\Util\Debug,
    Loggable\Fixture\Entity\Article,
    Loggable\Fixture\Entity\Comment;

/**
 * These are tests for loggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Loggable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class LoggableEntityTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ENTITY_CLASS_ARTICLE = 'Loggable\Fixture\Entity\Article';
    const TEST_ENTITY_CLASS_COMMENT = 'Loggable\Fixture\Entity\Comment';

    private $articleId;
    private $LoggableListener;
    private $em;

    public function setUp()
    {
        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
        $config->setQueryCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
        $config->setProxyDir(__DIR__ . '/Proxy');
        $config->setProxyNamespace('Gedmo\Loggable\Proxies');
        $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver());

        $conn = array(
            'driver' => 'pdo_sqlite',
            'memory' => true,
        );

        //$config->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());

        $evm = new \Doctrine\Common\EventManager();
        $this->LoggableListener = new ORM\LoggableListener();
        $this->LoggableListener->setUser('jules');
        $evm->addEventSubscriber($this->LoggableListener);
        $this->em = \Doctrine\ORM\EntityManager::create($conn, $config, $evm);

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $schemaTool->dropSchema(array());
        $schemaTool->createSchema(array(
            $this->em->getClassMetadata(self::TEST_ENTITY_CLASS_ARTICLE),
            $this->em->getClassMetadata(self::TEST_ENTITY_CLASS_COMMENT),
            $this->em->getClassMetadata('Gedmo\Loggable\Entity\HistoryLog'),
        ));
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
        $this->em->persist($art0);
        $this->em->flush();

        $logs = $this->getLogs();
        $this->assertEquals(1, count($logs), 'a log is created');
        $log = $logs[0];
        $this->assertNotEquals(null, $log);
        $this->assertTrue($log instanceof Entity\HistoryLog, 'a log instance of Entity\HistoryLog');

        $this->assertEquals('create', $log->getAction());
        $this->assertEquals(get_class($art0), $log->getObjectClass());
        $this->assertEquals($art0->getId(), $log->getForeignKey());
        $this->assertEquals('jules', $log->getUser());

        $this->clearLogs();

        // action update

        $art0->setTitle('Another Title');
        $this->em->persist($art0);
        $this->em->flush();

        $logs = $this->getLogs();
        $this->assertEquals(1, count($logs), 'a log is created');
        $log = $logs[0];
        $this->assertNotEquals(null, $log);
        $this->assertTrue($log instanceof Entity\HistoryLog, 'a log instance of Entity\HistoryLog');

        $this->assertEquals('update', $log->getAction());
        $this->assertEquals(get_class($art0), $log->getObjectClass());
        $this->assertEquals($art0->getId(), $log->getForeignKey());
        $this->assertEquals('jules', $log->getUser());

        $this->clearLogs();

        // action delete

        $articleId = $art0->getId();
        $this->em->remove($art0);
        $this->em->flush();

        $logs = $this->getLogs();
        $this->assertEquals(1, count($logs), 'a log is created');
        $log = $logs[0];
        $this->assertNotEquals(null, $log);
        $this->assertTrue($log instanceof Entity\HistoryLog, 'a log instance of Entity\HistoryLog');

        $this->assertEquals('delete', $log->getAction());
        $this->assertEquals(get_class($art0), $log->getObjectClass());
        $this->assertEquals($articleId, $log->getForeignKey());
        $this->assertEquals('jules', $log->getUser());
    }

    public function testLoggableNotAllowedAction()
    {
        $comment = new Comment();
        $comment->setTitle('My Title');

        $this->em->persist($comment);
        $this->em->flush();
        $this->assertEquals(1, count($this->getLogs()));
        $this->clearLogs();
        
        $this->em->remove($comment);
        $this->em->flush();
        $this->assertEquals(0, count($this->getLogs()));
    }

    private function getLogs()
    {
        return $this->em->createQueryBuilder()
            ->select('log')
            ->from('Gedmo\Loggable\Entity\HistoryLog', 'log')
            ->getQuery()
            ->execute(array())
        ;
    }

    private function clearLogs()
    {
        $this->em->createQueryBuilder()
            ->delete('Gedmo\Loggable\Entity\HistoryLog', 'log')
            ->getQuery()
            ->execute()
        ;
    }
}