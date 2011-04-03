<?php

namespace Gedmo\Loggable;

use Tool\BaseTestCaseMongoODM;
use Doctrine\Common\EventManager;
use Loggable\Fixture\Document\Article,
    Loggable\Fixture\Document\RelatedArticle,
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
class LoggableDocumentTest extends BaseTestCaseMongoODM
{
    const ARTICLE = 'Loggable\\Fixture\\Document\\Article';
    const COMMENT = 'Loggable\\Fixture\\Document\\Comment';
    const RELATED_ARTICLE = 'Loggable\\Fixture\\Document\\RelatedArticle';
    const COMMENT_LOG = 'Loggable\\Fixture\\Document\\Log\\Comment';

    protected function setUp()
    {
        parent::setUp();
        $evm = new EventManager();
        $loggableListener = new LoggableListener();
        $loggableListener->setUsername('jules');
        $evm->addEventSubscriber($loggableListener);

        $this->getMockDocumentManager($evm);
    }

    public function testLogGeneration()
    {
        $logRepo = $this->dm->getRepository('Gedmo\\Loggable\\Document\\LogEntry');
        $articleRepo = $this->dm->getRepository(self::ARTICLE);
        $this->assertEquals(0, count($logRepo->findAll()));

        $art0 = new Article();
        $art0->setTitle('Title');

        $this->dm->persist($art0);
        $this->dm->flush();

        $log = $logRepo->findOneByObjectId($art0->getId());

        $this->assertNotEquals(null, $log);
        $this->assertEquals('create', $log->getAction());
        $this->assertEquals(get_class($art0), $log->getObjectClass());
        $this->assertEquals('jules', $log->getUsername());
        $this->assertEquals(1, $log->getVersion());
        $data = $log->getData();
        $this->assertEquals(1, count($data));
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
        $this->assertEquals(null, $log->getData());
    }

    public function testVersionControl()
    {
        $this->populate();
        $commentLogRepo = $this->dm->getRepository(self::COMMENT_LOG);
        $commentRepo = $this->dm->getRepository(self::COMMENT);

        $comment = $commentRepo->findOneByMessage('m-v5');
        $commentId = $comment->getId();
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
        $this->assertEquals(6, count($logEntries));
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
