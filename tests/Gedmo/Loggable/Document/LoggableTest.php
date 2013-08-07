<?php

namespace Gedmo\Loggable\Document;

use Gedmo\TestTool\ObjectManagerTestCase;
use Doctrine\Common\EventManager;
use Gedmo\Fixture\Loggable\Document\Article;
use Gedmo\Fixture\Loggable\Document\RelatedArticle;
use Gedmo\Fixture\Loggable\Document\Comment;
use Gedmo\Loggable\LoggableListener;

class LoggableTest extends ObjectManagerTestCase
{
    const ARTICLE = 'Gedmo\Fixture\Loggable\Document\Article';
    const COMMENT = 'Gedmo\Fixture\Loggable\Document\Comment';
    const RELATED_ARTICLE = 'Gedmo\Fixture\Loggable\Document\RelatedArticle';
    const COMMENT_LOG = 'Gedmo\Fixture\Loggable\Document\Log\Comment';

    private $dm;

    protected function setUp()
    {
        $evm = new EventManager;
        $evm->addEventSubscriber($listener = new LoggableListener);
        $listener->setUsername('jules');

        $this->dm = $this->createDocumentManager($evm);
    }

    protected function tearDown()
    {
        $this->releaseDocumentManager($this->dm);
    }

    /**
     * test
     */
    function shouldGenerateLogEntries()
    {
        $logRepo = $this->dm->getRepository('Gedmo\Loggable\Document\LogEntry');
        $articleRepo = $this->dm->getRepository(self::ARTICLE);
        $this->assertCount(0, $logRepo->findAll());

        $art0 = new Article();
        $art0->setTitle('Title');

        $this->dm->persist($art0);
        $this->dm->flush();

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
        $this->dm->persist($article);
        $this->dm->flush();
        $this->dm->clear();

        $log = $logRepo->findOneBy(array('version' => 2, 'objectId' => $article->getId()));
        $this->assertEquals('update', $log->getAction());

        // test delete
        $article = $articleRepo->findOneByTitle('New');
        $this->dm->remove($article);
        $this->dm->flush();
        $this->dm->clear();

        $log = $logRepo->findOneBy(array('version' => 3, 'objectId' => $article->getId()));
        $this->assertEquals('remove', $log->getAction());
        $this->assertNull($log->getData());
    }

    /**
     * @test
     */
    function shouldHandleVersionControl()
    {
        $this->populate();
        $commentLogRepo = $this->dm->getRepository(self::COMMENT_LOG);
        $commentRepo = $this->dm->getRepository(self::COMMENT);

        $comment = $commentRepo->findOneByMessage('m-v5');
        $this->assertEquals('m-v5', $comment->getMessage());
        $this->assertEquals('s-v3', $comment->getSubject());
        $this->assertEquals('a2-t-v1', $comment->getArticle()->getTitle());

        // test revert
        $commentLogRepo->revert($comment, 3);
        $this->assertEquals('s-v3', $comment->getSubject());
        $this->assertEquals('m-v2', $comment->getMessage());
        $this->assertEquals('a1-t-v1', $comment->getArticle()->getTitle());
        $this->dm->persist($comment);
        $this->dm->flush();

        // test get log entries
        $logEntries = $commentLogRepo->getLogEntries($comment);
        $this->assertCount(6, $logEntries);
        $latest = array_shift($logEntries);
        $this->assertEquals('update', $latest->getAction());
    }

    private function populate()
    {
        $article = new RelatedArticle;
        $article->setTitle('a1-t-v1');
        $article->setContent('a1-c-v1');

        $comment = new Comment;
        $comment->setArticle($article);
        $comment->setMessage('m-v1');
        $comment->setSubject('s-v1');

        $this->dm->persist($article);
        $this->dm->persist($comment);
        $this->dm->flush();

        $comment->setMessage('m-v2');
        $this->dm->persist($comment);
        $this->dm->flush();

        $comment->setSubject('s-v3');
        $this->dm->persist($comment);
        $this->dm->flush();

        $article2 = new RelatedArticle;
        $article2->setTitle('a2-t-v1');
        $article2->setContent('a2-c-v1');

        $comment->setArticle($article2);
        $this->dm->persist($article2);
        $this->dm->persist($comment);
        $this->dm->flush();

        $comment->setMessage('m-v5');
        $this->dm->persist($comment);
        $this->dm->flush();
        $this->dm->clear();
    }
}
