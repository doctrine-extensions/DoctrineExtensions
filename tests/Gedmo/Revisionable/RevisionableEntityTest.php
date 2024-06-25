<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Revisionable;

use Doctrine\Common\EventManager;
use Gedmo\Revisionable\Entity\Repository\RevisionRepository;
use Gedmo\Revisionable\Entity\Revision;
use Gedmo\Revisionable\RevisionableListener;
use Gedmo\Revisionable\RevisionInterface;
use Gedmo\Tests\Revisionable\Fixture\Entity\Article;
use Gedmo\Tests\Revisionable\Fixture\Entity\Author;
use Gedmo\Tests\Revisionable\Fixture\Entity\Comment;
use Gedmo\Tests\Revisionable\Fixture\Entity\CommentRevision;
use Gedmo\Tests\Revisionable\Fixture\Entity\RelatedArticle;
use Gedmo\Tests\Tool\BaseTestCaseORM;

/**
 * Functional tests for the revsionable extension with the Doctrine ORM
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class RevisionableEntityTest extends BaseTestCaseORM
{
    private const ARTICLE = Article::class;
    private const COMMENT = Comment::class;
    private const COMMENT_REVISION = CommentRevision::class;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();

        $listener = new RevisionableListener();
        $listener->setUsername('jules');

        $evm->addEventSubscriber($listener);

        $this->em = $this->getDefaultMockSqliteEntityManager($evm);
    }

    public function testRevisionableLifecycle(): void
    {
        $revisionRepository = $this->em->getRepository(Revision::class);

        static::assertCount(0, $revisionRepository->findAll());

        $articleRepository = $this->em->getRepository(self::ARTICLE);

        $art0 = new Article();
        $art0->setTitle('Title');
        $art0->setPublishAt(new \DateTimeImmutable('2024-06-24 23:00:00', new \DateTimeZone('UTC')));

        $author = new Author();
        $author->setName('John Doe');
        $author->setEmail('john@doe.com');

        $art0->setAuthor($author);

        $this->em->persist($art0);
        $this->em->flush();

        $articleId = $art0->getId();

        $revision = $revisionRepository->findOneBy(['revisionableId' => $articleId]);

        static::assertNotNull($revision);
        static::assertSame(RevisionInterface::ACTION_CREATE, $revision->getAction());
        static::assertSame(get_class($art0), $revision->getRevisionableClass());
        static::assertSame('jules', $revision->getUsername());
        static::assertSame(1, $revision->getVersion());

        $data = $revision->getData();

        static::assertCount(4, $data);
        static::assertArrayHasKey('title', $data);
        static::assertSame('Title', $data['title']);
        static::assertArrayHasKey('publishAt', $data);
        static::assertSame('2024-06-24 23:00:00', $data['publishAt']);
        static::assertArrayHasKey('author.name', $data);
        static::assertSame('John Doe', $data['author.name']);
        static::assertArrayHasKey('author.email', $data);
        static::assertSame('john@doe.com', $data['author.email']);

        // test update
        $article = $articleRepository->findOneBy(['title' => 'Title']);
        $article->setTitle('New');
        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();

        $revision = $revisionRepository->findOneBy(['version' => 2, 'revisionableId' => $articleId]);

        static::assertSame(RevisionInterface::ACTION_UPDATE, $revision->getAction());

        // test delete
        $article = $articleRepository->findOneBy(['title' => 'New']);
        $this->em->remove($article);
        $this->em->flush();
        $this->em->clear();

        $revision = $revisionRepository->findOneBy(['version' => 3, 'revisionableId' => $articleId]);

        static::assertSame(RevisionInterface::ACTION_REMOVE, $revision->getAction());
        static::assertEmpty($revision->getData());
    }

    public function testVersionLifecycle(): void
    {
        $this->populate();

        $commentRevisionRepository = $this->em->getRepository(self::COMMENT_REVISION);

        $commentRepository = $this->em->getRepository(self::COMMENT);

        static::assertInstanceOf(RevisionRepository::class, $commentRevisionRepository);

        $comment = $commentRepository->findOneBy(['message' => 'm-v5']);

        static::assertNotNull($comment);

        static::assertSame('m-v5', $comment->getMessage());
        static::assertSame('s-v3', $comment->getSubject());
        static::assertSame('2024-06-24 23:30:00', $comment->getWrittenAt()->format('Y-m-d H:i:s'));
        static::assertSame('a2-t-v1', $comment->getArticle()->getTitle());
        static::assertSame('Jane Doe', $comment->getAuthor()->getName());
        static::assertSame('jane@doe.com', $comment->getAuthor()->getEmail());

        // test revert
        $commentRevisionRepository->revert($comment, 3);

        static::assertSame('s-v3', $comment->getSubject());
        static::assertSame('m-v2', $comment->getMessage());
        static::assertSame('2024-06-24 23:30:00', $comment->getWrittenAt()->format('Y-m-d H:i:s'));
        static::assertSame('a1-t-v1', $comment->getArticle()->getTitle());
        static::assertSame('John Doe', $comment->getAuthor()->getName());
        static::assertSame('john@doe.com', $comment->getAuthor()->getEmail());

        $this->em->persist($comment);
        $this->em->flush();

        // test fetch revisions
        $revisions = $commentRevisionRepository->getRevisions($comment);

        static::assertCount(6, $revisions);

        $latest = array_shift($revisions);

        static::assertSame(RevisionInterface::ACTION_UPDATE, $latest->getAction());
    }

    public function testSupportsClonedEntities(): void
    {
        $art0 = new Article();
        $art0->setTitle('Title');
        $art0->setPublishAt(new \DateTimeImmutable('2024-06-24 23:00:00', new \DateTimeZone('UTC')));

        $author = new Author();
        $author->setName('John Doe');
        $author->setEmail('john@doe.com');

        $art0->setAuthor($author);

        $this->em->persist($art0);
        $this->em->flush();

        $art1 = clone $art0;
        $art1->setTitle('Cloned');

        $this->em->persist($art1);
        $this->em->flush();

        $revisionRepository = $this->em->getRepository(Revision::class);

        $revisions = $revisionRepository->findAll();

        static::assertCount(2, $revisions);
        static::assertSame(RevisionInterface::ACTION_CREATE, $revisions[0]->getAction());
        static::assertSame(RevisionInterface::ACTION_CREATE, $revisions[1]->getAction());
        static::assertNotSame($revisions[0]->getRevisionableId(), $revisions[1]->getRevisionableId());
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::ARTICLE,
            Author::class,
            self::COMMENT,
            self::COMMENT_REVISION,
            RelatedArticle::class,
            Revision::class,
        ];
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
        $comment->setWrittenAt(new \DateTimeImmutable('2024-06-24 23:30:00', new \DateTimeZone('UTC')));

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

        $author2 = new Author();
        $author2->setName('Jane Doe');
        $author2->setEmail('jane@doe.com');

        $comment->setAuthor($author2);
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
