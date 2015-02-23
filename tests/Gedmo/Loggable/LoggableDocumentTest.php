<?php

namespace Gedmo\Loggable;

use Loggable\Fixture\Document\EmbeddedComment;
use Loggable\Fixture\Document\User;
use Tool\BaseTestCaseMongoODM;
use Doctrine\Common\EventManager;
use Loggable\Fixture\Document\Article;
use Loggable\Fixture\Document\RelatedArticle;
use Loggable\Fixture\Document\Comment;

/**
 * These are tests for loggable behavior
 *
 * @author Boussekeyt Jules <jules.boussekeyt@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
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
        $this->assertCount(0, $logRepo->findAll());
        $this->populateEmbbedDocument();

        /** @var $article Article */
        $article = $articleRepo->findOneByTitle('New');
        $log = $logRepo->findOneBy(array('version' => 1, 'objectId' => $article->getId()));

        $this->assertNotNull($log);
        $this->assertEquals('create', $log->getAction());
        $this->assertEquals(self::ARTICLE, $log->getObjectClass());
        $this->assertEquals('jules', $log->getUsername());
        $this->assertEquals(1, $log->getVersion());
        $data = $log->getData();
        $this->assertCount(3, $data);
        $this->assertArrayHasKey('title', $data);
        $this->assertEquals($data['title'], 'Title');
        $this->assertArrayHasKey('author', $data);
        $this->assertCount(2, $data['author']);
        $this->assertArrayHasKey('firstName', $data['author']);
        $this->assertArrayHasKey('lastName', $data['author']);
        $this->assertEquals($data['author']['firstName'], 'firstName');
        $this->assertEquals($data['author']['lastName'], 'lastName');
        $this->assertArrayHasKey('comments', $data);
        $this->assertCount(3, $data['comments']);
        $this->assertCount(2, $data['comments'][0]);
        $this->assertCount(2, $data['comments'][1]);
        $this->assertCount(2, $data['comments'][2]);
        $this->assertArrayHasKey('subject', $data['comments'][0]);
        $this->assertArrayHasKey('message', $data['comments'][0]);
        $this->assertEquals($data['comments'][0]['subject'], 's0-v1');
        $this->assertEquals($data['comments'][0]['message'], 'm0-v1');
        $this->assertArrayHasKey('subject', $data['comments'][1]);
        $this->assertArrayHasKey('message', $data['comments'][1]);
        $this->assertEquals($data['comments'][1]['subject'], 's1-v1');
        $this->assertEquals($data['comments'][1]['message'], 'm1-v1');

        // test update
        $log = $logRepo->findOneBy(array('version' => 2, 'objectId' => $article->getId()));
        $data = $log->getData();
        $this->assertEquals('update', $log->getAction());
        $this->assertCount(2, $data['comments']);
        $this->assertCount(0, $data['comments'][0]);
        $this->assertCount(1, $data['comments'][1]);

        $log = $logRepo->findOneBy(array('version' => 3, 'objectId' => $article->getId()));
        $data = $log->getData();
        $this->assertArrayHasKey('author', $data);
        $this->assertNull($data['author']);

        // test delete
        $article = $articleRepo->findOneByTitle('New');
        $this->dm->remove($article);
        $this->dm->flush();

        $log = $logRepo->findOneBy(array('version' => 5, 'objectId' => $article->getId()));
        $this->assertEquals('remove', $log->getAction());
        $this->assertNull($log->getData());
    }

    public function testGetWrongVersion()
    {
        $logRepo = $this->dm->getRepository('Gedmo\\Loggable\\Document\\LogEntry');

        $this->setExpectedException('\Gedmo\Exception\UnexpectedValueException', 'Could not find any log entries under version: 1');

        $article = new Article();

        $logRepo->revert($article, 1);
    }


    public function testVersionControl()
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

    public function testVersionControlWithEmbbed()
    {
        $this->populateEmbbedDocument();
        $logRepo = $this->dm->getRepository('Gedmo\\Loggable\\Document\\LogEntry');
        $articleRepo = $this->dm->getRepository(self::ARTICLE);

        $article = $articleRepo->findOneByTitle('New');
        $this->assertNull($article->getAuthor());
        $article->getComments();
        $this->assertNull($article->getComments());

        // test revert
        $logRepo->revert($article, 2);
        $this->assertEquals('OtherName', $article->getAuthor()->getFirstName());
        $comments = $article->getComments();
        $this->assertCount(2, $comments);
        $this->assertEquals('m1-v2', $comments[1]->getMessage());
        $this->dm->persist($article);
        $this->dm->flush();

        $logRepo->revert($article, 1);
        $this->assertEquals('firstName', $article->getAuthor()->getFirstName());
        $comments = $article->getComments();
        $this->assertCount(3, $comments);
        $this->assertEquals('m1-v1', $comments[1]->getMessage());
        $this->assertEquals('m2-v1', $comments[2]->getMessage());
        $this->dm->persist($article);
        $this->dm->flush();

        $logRepo->revert($article, 3);
        $this->assertNull($article->getAuthor());
        $comments = $article->getComments();
        $this->assertEquals('m1-v2', $comments[1]->getMessage());
        $this->dm->persist($article);
        $this->dm->flush();

        // test get log entries
        $logEntries = $logRepo->getLogEntries($article);
        $this->assertCount(7, $logEntries);
        $latest = array_shift($logEntries);
        $this->assertEquals('update', $latest->getAction());
    }

    private function populateEmbbedDocument()
    {
        $com0 = new EmbeddedComment();
        $com0->setMessage('m0-v1');
        $com0->setSubject('s0-v1');
        $com1 = new EmbeddedComment();
        $com1->setMessage('m1-v1');
        $com1->setSubject('s1-v1');
        $com2 = new EmbeddedComment();
        $com2->setMessage('m2-v1');
        $com2->setSubject('s2-v1');
        $author0 = new User();
        $author0->setFirstName('firstName');
        $author0->setLastName('lastName');
        $article = new Article();
        $article->setTitle('Title');
        $article->setAuthor($author0);
        $article->addComment($com0);
        $article->addComment($com1);
        $article->addComment($com2);

        $this->dm->persist($article);
        $this->dm->flush();

        $article->setTitle('New');
        $article->getAuthor()->setFirstName('OtherName');
        $comments = $article->getComments();
        $comments[1]->setMessage('m1-v2');
        unset($comments[2]);
        $this->dm->persist($article);
        $this->dm->flush();

        $article->setAuthor(null);
        $this->dm->persist($article);
        $this->dm->flush();

        $article->setComments(null);
        $this->dm->persist($article);
        $this->dm->flush();


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

        $this->dm->persist($article);
        $this->dm->persist($comment);
        $this->dm->flush();

        $comment->setMessage('m-v2');
        $this->dm->persist($comment);
        $this->dm->flush();

        $comment->setSubject('s-v3');
        $this->dm->persist($comment);
        $this->dm->flush();

        $article2 = new RelatedArticle();
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
