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
use Gedmo\Revisionable\Revisionable;
use Gedmo\Revisionable\RevisionableListener;
use Gedmo\Revisionable\RevisionInterface;
use Gedmo\Tests\Revisionable\Fixture\Entity\Address;
use Gedmo\Tests\Revisionable\Fixture\Entity\Article;
use Gedmo\Tests\Revisionable\Fixture\Entity\Author;
use Gedmo\Tests\Revisionable\Fixture\Entity\Comment;
use Gedmo\Tests\Revisionable\Fixture\Entity\CommentRevision;
use Gedmo\Tests\Revisionable\Fixture\Entity\Composite;
use Gedmo\Tests\Revisionable\Fixture\Entity\CompositeRelation;
use Gedmo\Tests\Revisionable\Fixture\Entity\Geo;
use Gedmo\Tests\Revisionable\Fixture\Entity\GeoLocation;
use Gedmo\Tests\Revisionable\Fixture\Entity\RelatedArticle;
use Gedmo\Tests\TestActorProvider;
use Gedmo\Tests\Tool\BaseTestCaseORM;

/**
 * Functional tests for the revsionable extension with the Doctrine ORM
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class RevisionableEntityTest extends BaseTestCaseORM
{
    /**
     * @var RevisionableListener<Revisionable|object>
     */
    private RevisionableListener $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();

        $this->listener = new RevisionableListener();
        $this->listener->setUsername('jules');

        $evm->addEventSubscriber($this->listener);

        $this->em = $this->getDefaultMockSqliteEntityManager($evm);
    }

    public function testRevisionableLifecycle(): void
    {
        $revisionRepository = $this->em->getRepository(Revision::class);

        static::assertCount(0, $revisionRepository->findAll());

        $articleRepository = $this->em->getRepository(Article::class);

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

    public function testRevisionableLifecycleWithActorProvider(): void
    {
        $this->listener->setActorProvider(new TestActorProvider('testactor'));

        $revisionRepository = $this->em->getRepository(Revision::class);

        static::assertCount(0, $revisionRepository->findAll());

        $articleRepository = $this->em->getRepository(Article::class);

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
        static::assertSame('testactor', $revision->getUsername());
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

        $commentRevisionRepository = $this->em->getRepository(CommentRevision::class);

        $commentRepository = $this->em->getRepository(Comment::class);

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

    public function testLogsRevisionsOfEmbeddedEntities(): void
    {
        $address = new Address();
        $address->setCity('city-v1');
        $address->setStreet('street-v1');
        $address->setGeo(new Geo(1.0000, 1.0000, new GeoLocation('Online')));

        $this->em->persist($address);
        $this->em->flush();

        $address->setGeo(new Geo(2.0000, 2.0000, new GeoLocation('Offline')));

        $this->em->persist($address);
        $this->em->flush();

        $address->getGeo()->setLatitude(3.0000);
        $address->getGeo()->setLongitude(3.0000);

        $this->em->persist($address);
        $this->em->flush();

        $address->setStreet('street-v2');

        $this->em->persist($address);
        $this->em->flush();

        $revisionRepository = $this->em->getRepository(Revision::class);

        $revisions = $revisionRepository->getRevisions($address);

        static::assertCount(4, $revisions);
        static::assertCount(1, $revisions[0]->getData());
        static::assertCount(2, $revisions[1]->getData());
        static::assertCount(3, $revisions[2]->getData());
        static::assertCount(5, $revisions[3]->getData());
    }

    public function testLogsRevisionsOfEntitiesWithCompositeIds(): void
    {
        $compositeIds = [1, 2];

        $composite = new Composite(...$compositeIds);
        $composite->setTitle('Title2');

        $this->em->persist($composite);
        $this->em->flush();

        $compositeId = sprintf('%s %s', ...$compositeIds);

        $revisionRepository = $this->em->getRepository(Revision::class);

        $revision = $revisionRepository->findOneBy(['revisionableId' => $compositeId]);

        static::assertNotNull($revision);
        static::assertSame(RevisionInterface::ACTION_CREATE, $revision->getAction());
        static::assertSame(get_class($composite), $revision->getRevisionableClass());
        static::assertSame('jules', $revision->getUsername());
        static::assertSame(1, $revision->getVersion());

        $data = $revision->getData();

        static::assertCount(1, $data);
        static::assertArrayHasKey('title', $data);
        static::assertSame($data['title'], 'Title2');

        $compositeRepository = $this->em->getRepository(Composite::class);

        // test update
        $composite = $compositeRepository->findOneBy(['title' => 'Title2']);
        $composite->setTitle('New');

        $this->em->persist($composite);
        $this->em->flush();
        $this->em->clear();

        $revision = $revisionRepository->findOneBy(['revisionableId' => $compositeId, 'version' => 2]);

        static::assertNotNull($revision);
        static::assertSame(RevisionInterface::ACTION_UPDATE, $revision->getAction());

        // test delete
        $composite = $compositeRepository->findOneBy(['title' => 'New']);
        $this->em->remove($composite);
        $this->em->flush();
        $this->em->clear();

        $revision = $revisionRepository->findOneBy(['revisionableId' => $compositeId, 'version' => 3]);
        static::assertSame(RevisionInterface::ACTION_REMOVE, $revision->getAction());
        static::assertEmpty($revision->getData());
    }

    public function testLogsRevisionsOfEntitiesWithCompositeIdsBasedOnRelations(): void
    {
        $author = new Author();
        $author->setName('John Doe');
        $author->setEmail('john@doe.com');

        $art0 = new Article();
        $art0->setTitle('Title0');
        $art0->setPublishAt(new \DateTimeImmutable('2024-06-24 23:00:00', new \DateTimeZone('UTC')));
        $art0->setAuthor($author);

        $art1 = new Article();
        $art1->setTitle('Title1');
        $art1->setPublishAt(new \DateTimeImmutable('2024-06-24 23:00:00', new \DateTimeZone('UTC')));
        $art1->setAuthor($author);

        $composite = new CompositeRelation($art0, $art1);
        $composite->setTitle('Title2');

        $this->em->persist($art0);
        $this->em->persist($art1);
        $this->em->persist($composite);
        $this->em->flush();

        $compositeId = sprintf('%s %s', $art0->getId(), $art1->getId());

        $revisionRepository = $this->em->getRepository(Revision::class);

        $revision = $revisionRepository->findOneBy(['revisionableId' => $compositeId]);

        static::assertNotNull($revision);
        static::assertSame(RevisionInterface::ACTION_CREATE, $revision->getAction());
        static::assertSame(get_class($composite), $revision->getRevisionableClass());
        static::assertSame('jules', $revision->getUsername());
        static::assertSame(1, $revision->getVersion());

        $data = $revision->getData();

        static::assertCount(1, $data);
        static::assertArrayHasKey('title', $data);
        static::assertSame($data['title'], 'Title2');

        $compositeRepository = $this->em->getRepository(CompositeRelation::class);

        // test update
        $composite = $compositeRepository->findOneBy(['title' => 'Title2']);
        $composite->setTitle('New');

        $this->em->persist($composite);
        $this->em->flush();
        $this->em->clear();

        $revision = $revisionRepository->findOneBy(['revisionableId' => $compositeId, 'version' => 2]);

        static::assertNotNull($revision);
        static::assertSame(RevisionInterface::ACTION_UPDATE, $revision->getAction());

        // test delete
        $composite = $compositeRepository->findOneBy(['title' => 'New']);
        $this->em->remove($composite);
        $this->em->flush();
        $this->em->clear();

        $revision = $revisionRepository->findOneBy(['revisionableId' => $compositeId, 'version' => 3]);
        static::assertSame(RevisionInterface::ACTION_REMOVE, $revision->getAction());
        static::assertEmpty($revision->getData());
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            Address::class,
            Article::class,
            Author::class,
            Comment::class,
            CommentRevision::class,
            Composite::class,
            CompositeRelation::class,
            Geo::class,
            GeoLocation::class,
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
