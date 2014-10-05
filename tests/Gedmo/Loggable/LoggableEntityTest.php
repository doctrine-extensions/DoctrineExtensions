<?php

namespace Gedmo\Loggable;

use Tool\BaseTestCaseORM;
use Doctrine\Common\EventManager;
use Loggable\Fixture\Entity\Article;
use Loggable\Fixture\Entity\RelatedArticle;
use Loggable\Fixture\Entity\Comment;

/**
 * These are tests for loggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class LoggableEntityTest extends BaseTestCaseORM
{
    const ARTICLE = 'Loggable\Fixture\Entity\Article';
    const COMMENT = 'Loggable\Fixture\Entity\Comment';
    const RELATED_ARTICLE = 'Loggable\Fixture\Entity\RelatedArticle';
    const COMMENT_LOG = 'Loggable\Fixture\Entity\Log\Comment';

    private $articleId;
    private $LoggableListener;

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager();
        $this->LoggableListener = new LoggableListener();
        $this->LoggableListener->setUsername('jules');
        $evm->addEventSubscriber($this->LoggableListener);

        $this->em = $this->getMockSqliteEntityManager($evm);
    }

    /**
     * @test
     */
    public function shouldHandleClonedEntity()
    {
        $art0 = new Article();
        $art0->setTitle('Title');

        $this->em->persist($art0);
        $this->em->flush();

        $art1 = clone $art0;
        $art1->setTitle('Cloned');
        $this->em->persist($art1);
        $this->em->flush();

        $logRepo = $this->em->getRepository('Gedmo\Loggable\Entity\LogEntry');
        $logs = $logRepo->findAll();
        $this->assertSame(2, count($logs));
        $this->assertSame('create', $logs[0]->getAction());
        $this->assertSame('create', $logs[1]->getAction());
        $this->assertTrue($logs[0]->getObjectId() !== $logs[1]->getObjectId());
    }

    public function testLoggable()
    {
        $logRepo = $this->em->getRepository('Gedmo\Loggable\Entity\LogEntry');
        $articleRepo = $this->em->getRepository(self::ARTICLE);
        $this->assertCount(0, $logRepo->findAll());

        $art0 = new Article();
        $art0->setTitle('Title');

        $this->em->persist($art0);
        $this->em->flush();

        $log = $logRepo->findOneByObjectId($art0->getId());

        $this->assertNotNull($log);
        $this->assertEquals('create', $log->getAction());
        $this->assertEquals(get_class($art0), $log->getObjectClass());
        $this->assertEquals('jules', $log->getUsername());
        $this->assertEquals(1, $log->getVersion());
        $data = $log->getData();
        $this->assertCount(1, $data);
        $this->assertArrayHasKey('title', $data);
        $this->assertEquals($data['title'], 'Title');

        // test update
        $article = $articleRepo->findOneByTitle('Title');

        $article->setTitle('New');
        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();

        $log = $logRepo->findOneBy(array('version' => 2, 'objectId' => $article->getId()));
        $this->assertEquals('update', $log->getAction());

        // test delete
        $article = $articleRepo->findOneByTitle('New');
        $this->em->remove($article);
        $this->em->flush();
        $this->em->clear();

        $log = $logRepo->findOneBy(array('version' => 3, 'objectId' => 1));
        $this->assertEquals('remove', $log->getAction());
        $this->assertNull($log->getData());
    }

    public function testVersionControl()
    {
        $this->populate();
        $commentLogRepo = $this->em->getRepository(self::COMMENT_LOG);
        $commentRepo = $this->em->getRepository(self::COMMENT);

        $comment = $commentRepo->find(1);
        $this->assertEquals('m-v5', $comment->getMessage());
        $this->assertEquals('s-v3', $comment->getSubject());
        $this->assertEquals(2, $comment->getArticle()->getId());

        // test revert
        $commentLogRepo->revert($comment, 3);
        $this->assertEquals('s-v3', $comment->getSubject());
        $this->assertEquals('m-v2', $comment->getMessage());
        $this->assertEquals(1, $comment->getArticle()->getId());
        $this->em->persist($comment);
        $this->em->flush();

        // test get log entries
        $logEntries = $commentLogRepo->getLogEntries($comment);
        $this->assertCount(6, $logEntries);
        $latest = $logEntries[0];
        $this->assertEquals('update', $latest->getAction());
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::ARTICLE,
            self::COMMENT,
            self::COMMENT_LOG,
            self::RELATED_ARTICLE,
            'Gedmo\Loggable\Entity\LogEntry',
        );
    }

    private function populate()
    {
        $article = new RelatedArticle();
        $article->setTitle('a1-t-v1');
        $article->setContent('a1-c-v1');

        $comment = new Comment();
        $comment->setArticle($article);
        $comment->setMessage('m-v1');
        $comment->setSubject('s-v1');

        $this->em->persist($article);
        $this->em->persist($comment);
        $this->em->flush();

        $comment->setMessage('m-v2');
        $this->em->persist($comment);
        $this->em->flush();

        $comment->setSubject('s-v3');
        $this->em->persist($comment);
        $this->em->flush();

        $article2 = new RelatedArticle();
        $article2->setTitle('a2-t-v1');
        $article2->setContent('a2-c-v1');

        $comment->setArticle($article2);
        $this->em->persist($article2);
        $this->em->persist($comment);
        $this->em->flush();

        $comment->setMessage('m-v5');
        $this->em->persist($comment);
        $this->em->flush();
        $this->em->clear();
    }
}
