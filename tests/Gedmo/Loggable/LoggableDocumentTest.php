<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Loggable;

use Doctrine\Common\EventManager;
use Gedmo\Loggable\Document\LogEntry;
use Gedmo\Loggable\Document\Repository\LogEntryRepository;
use Gedmo\Loggable\LoggableListener;
use Gedmo\Tests\Loggable\Fixture\Document\Article;
use Gedmo\Tests\Loggable\Fixture\Document\Author;
use Gedmo\Tests\Loggable\Fixture\Document\Comment;
use Gedmo\Tests\Loggable\Fixture\Document\RelatedArticle;
use Gedmo\Tests\Tool\BaseTestCaseMongoODM;

/**
 * These are tests for loggable behavior
 *
 * @author Boussekeyt Jules <jules.boussekeyt@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class LoggableDocumentTest extends BaseTestCaseMongoODM
{
    public const ARTICLE = Article::class;
    public const COMMENT = Comment::class;
    public const RELATED_ARTICLE = RelatedArticle::class;
    public const COMMENT_LOG = \Gedmo\Tests\Loggable\Fixture\Document\Log\Comment::class;

    protected function setUp(): void
    {
        parent::setUp();
        $evm = new EventManager();
        $loggableListener = new LoggableListener();
        $loggableListener->setUsername('jules');
        $evm->addEventSubscriber($loggableListener);

        $this->getDefaultDocumentManager($evm);
    }

    public function testLogGeneration(): void
    {
        $logRepo = $this->dm->getRepository(LogEntry::class);
        $articleRepo = $this->dm->getRepository(self::ARTICLE);
        static::assertCount(0, $logRepo->findAll());

        $art0 = new Article();
        $art0->setTitle('Title');

        $author = new Author();
        $author->setName('John Doe');
        $author->setEmail('john@doe.com');

        $art0->setAuthor($author);

        $this->dm->persist($art0);
        $this->dm->flush();

        $log = $logRepo->findOneBy(['objectId' => $art0->getId()]);

        static::assertNotNull($log);
        static::assertSame('create', $log->getAction());
        static::assertSame(get_class($art0), $log->getObjectClass());
        static::assertSame('jules', $log->getUsername());
        static::assertSame(1, $log->getVersion());
        $data = $log->getData();
        static::assertCount(2, $data);
        static::assertArrayHasKey('title', $data);
        static::assertSame('Title', $data['title']);
        static::assertArrayHasKey('author', $data);
        static::assertSame(['name' => 'John Doe', 'email' => 'john@doe.com'], $data['author']);

        // test update
        $article = $articleRepo->findOneBy(['title' => 'Title']);
        $article->setTitle('New');
        $this->dm->persist($article);
        $this->dm->flush();
        $this->dm->clear();

        $log = $logRepo->findOneBy(['version' => 2, 'objectId' => $article->getId()]);
        static::assertSame('update', $log->getAction());

        // test delete
        $article = $articleRepo->findOneBy(['title' => 'New']);
        $this->dm->remove($article);
        $this->dm->flush();
        $this->dm->clear();

        $log = $logRepo->findOneBy(['version' => 3, 'objectId' => $article->getId()]);
        static::assertSame('remove', $log->getAction());
        static::assertNull($log->getData());
    }

    public function testVersionControl(): void
    {
        $this->populate();
        $commentLogRepo = $this->dm->getRepository(self::COMMENT_LOG);
        $commentRepo = $this->dm->getRepository(self::COMMENT);
        static::assertInstanceOf(LogEntryRepository::class, $commentLogRepo);

        $comment = $commentRepo->findOneBy(['message' => 'm-v5']);
        $commentId = $comment->getId();
        static::assertSame('m-v5', $comment->getMessage());
        static::assertSame('s-v3', $comment->getSubject());
        static::assertSame('a2-t-v1', $comment->getArticle()->getTitle());
        static::assertSame('Jane Doe', $comment->getAuthor()->getName());
        static::assertSame('jane@doe.com', $comment->getAuthor()->getEmail());

        // test revert
        $commentLogRepo->revert($comment, 3);
        static::assertSame('s-v3', $comment->getSubject());
        static::assertSame('m-v2', $comment->getMessage());
        static::assertSame('a1-t-v1', $comment->getArticle()->getTitle());
        static::assertSame('John Doe', $comment->getAuthor()->getName());
        static::assertSame('john@doe.com', $comment->getAuthor()->getEmail());
        $this->dm->persist($comment);
        $this->dm->flush();

        // test get log entries
        $logEntries = $commentLogRepo->getLogEntries($comment);
        static::assertCount(6, $logEntries);
        $latest = array_shift($logEntries);
        static::assertSame('update', $latest->getAction());
    }

    private function populate(): void
    {
        $article = new RelatedArticle();
        $article->setTitle('a1-t-v1');
        $article->setContent('a1-c-v1');

        $author = new Author();
        $author->setName('John Doe');
        $author->setEmail('john@doe.com');

        $comment = new Comment();
        $comment->setArticle($article);
        $comment->setAuthor($author);
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
