<?php

namespace Gedmo\Loggable;

use Loggable\Fixture\Document\Reference;
use Tool\BaseTestCaseMongoODM;
use Doctrine\Common\EventManager;
use Loggable\Fixture\Document\Article;
use Loggable\Fixture\Document\RelatedArticle;
use Loggable\Fixture\Document\Comment;
use Loggable\Fixture\Document\Author;
use Composer\Autoload\ClassLoader;

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

        $ref0 = new Reference();
        $ref0->setTitle('wikipedia');
        $ref0->setReference('https://www.wikipedia.org/');

        $ref1 = new Reference();
        $ref1->setTitle('DoctrineExtensions');
        $ref1->setReference('https://github.com/Atlantic18/DoctrineExtensions');

        $art0 = new Article();
        $art0->setTitle('Title');
        $art0->setReferences(array($ref0, $ref1));

        $author = new Author();
        $author->setName('John Doe');
        $author->setEmail('john@doe.com');

        $art0->setAuthor($author);

        $this->dm->persist($art0);
        $this->dm->flush();

        $log = $logRepo->findOneByObjectId($art0->getId());

        $this->assertNotNull($log);
        $this->assertEquals('create', $log->getAction());
        $this->assertEquals(get_class($art0), $log->getObjectClass());
        $this->assertEquals('jules', $log->getUsername());
        $this->assertEquals(1, $log->getVersion());
        $data = $log->getData();
        $this->assertCount(3, $data);
        $this->assertArrayHasKey('title', $data);
        $this->assertEquals($data['title'], 'Title');
        $this->assertArrayHasKey('author', $data);
        $this->assertEquals($data['author'], array('name' => 'John Doe', 'email' => 'john@doe.com'));
        $this->assertEquals(
            $data['references'],
            array(
                array('reference' => 'https://www.wikipedia.org/', 'title' => 'wikipedia'),
                array('reference' => 'https://github.com/Atlantic18/DoctrineExtensions', 'title' => 'DoctrineExtensions')
            )
        );

        // test update
        $article = $articleRepo->findOneByTitle('Title');
        $article->setTitle('New');
        $article->getReferences()[0]->setTitle('www.wikipedia.org');
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
        $this->assertEquals('Jane Doe', $comment->getAuthor()->getName());
        $this->assertEquals('jane@doe.com', $comment->getAuthor()->getEmail());

        // test revert
        $commentLogRepo->revert($comment, 3);
        $this->assertEquals('s-v3', $comment->getSubject());
        $this->assertEquals('m-v2', $comment->getMessage());
        $this->assertEquals('a1-t-v1', $comment->getArticle()->getTitle());
        $this->assertEquals('John Doe', $comment->getAuthor()->getName());
        $this->assertEquals('john@doe.com', $comment->getAuthor()->getEmail());
        $this->dm->persist($comment);
        $this->dm->flush();

        // test get log entries
        $logEntries = $commentLogRepo->getLogEntries($comment);
        $this->assertCount(6, $logEntries);
        $latest = array_shift($logEntries);
        $this->assertEquals('update', $latest->getAction());
    }

    public function testVersionControlWithEmbedMany()
    {
        $this->populate();
        $commentLogRepo = $this->dm->getRepository(self::COMMENT_LOG);
        $commentRepo = $this->dm->getRepository(self::COMMENT);
        $comment = $commentRepo->findOneByMessage('m-v5');
        $this->assertEquals('m-v5', $comment->getMessage());
        $this->assertEquals('s-v3', $comment->getSubject());
        $this->assertEquals('a2-t-v1', $comment->getArticle()->getTitle());
        $this->assertCount(0, $comment->getArticle()->getReferences());
        $this->assertEquals('Jane Doe', $comment->getAuthor()->getName());
        $this->assertEquals('jane@doe.com', $comment->getAuthor()->getEmail());
        // test revert
        $commentLogRepo->revert($comment, 3);
        $this->assertEquals('s-v3', $comment->getSubject());
        $this->assertEquals('m-v2', $comment->getMessage());
        $this->assertEquals('a1-t-v1', $comment->getArticle()->getTitle());
        $this->assertCount(2, $comment->getArticle()->getReferences());
        $this->assertEquals('r1-t-v2', $comment->getArticle()->getReferences()[0]->getTitle());
        $this->assertEquals('John Doe', $comment->getAuthor()->getName());
        $this->assertEquals('john@doe.com', $comment->getAuthor()->getEmail());
        $this->dm->persist($comment);
        $this->dm->flush();
        // test revert
        $commentLogRepo->revert($comment, 2);
        $this->assertEquals('r1-t-v2', $comment->getArticle()->getReferences()[0]->getTitle());
        // test get log entries
        $logEntries = $commentLogRepo->getLogEntries($comment);
        $this->assertCount(6, $logEntries);
        $latest = array_shift($logEntries);
        $this->assertEquals('update', $latest->getAction());
    }

    private function populate()
    {
        $ref = new Reference();
        $ref->setTitle('r1-t-v1');
        $ref->setReference('r1-r-v1');

        $ref2 = new Reference();
        $ref2->setTitle('r2-t-v1');
        $ref2->setReference('r2-r-v1');

        $article = new RelatedArticle();
        $article->setTitle('a1-t-v1');
        $article->setContent('a1-c-v1');
        $article->setReferences(array($ref, $ref2));

        $author = new Author();
        $author->setName('John Doe');
        $author->setEmail('john@doe.com');

        $comment = new Comment();
        $comment->setArticle($article);
        $comment->setAuthor($author);
        $comment->setMessage('m-v1');
        $comment->setSubject('s-v1');
        $article->getReferences()[0]->setTitle('r1-t-v2');
        $this->dm->persist($article);

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

        $author2 = new Author();
        $author2->setName('Jane Doe');
        $author2->setEmail('jane@doe.com');

        $comment->setAuthor($author2);
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
