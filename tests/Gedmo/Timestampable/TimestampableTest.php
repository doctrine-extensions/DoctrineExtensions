<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Timestampable;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Proxy\Proxy;
use Gedmo\Tests\Mapping\Fixture\Xml\Timestampable;
use Gedmo\Tests\Timestampable\Fixture\Article;
use Gedmo\Tests\Timestampable\Fixture\Author;
use Gedmo\Tests\Timestampable\Fixture\Comment;
use Gedmo\Tests\Timestampable\Fixture\Type;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Timestampable\TimestampableListener;

/**
 * These are tests for Timestampable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class TimestampableTest extends BaseTestCaseORM
{
    public const ARTICLE = Article::class;
    public const COMMENT = Comment::class;
    public const TYPE = Type::class;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new TimestampableListener());

        $this->getDefaultMockSqliteEntityManager($evm);
    }

    /**
     * issue #1255
     */
    public function testShouldHandleDetatchedAndMergedBackEntities(): void
    {
        $sport = new Article();
        $sport->setTitle('Sport');
        $sport->setBody('Sport article body.');

        $this->em->detach($sport);
        $newSport = $this->em->merge($sport);

        $this->em->persist($newSport);
        $this->em->flush();

        static::assertNotNull($newSport->getUpdated());
    }

    /**
     * issue #1255
     */
    public function testShouldHandleDetatchedAndMergedBackEntitiesAfterPersist(): void
    {
        $sport = new Article();
        $sport->setTitle('Sport');
        $sport->setBody('Sport article body.');

        $this->em->persist($sport);
        $this->em->flush();
        $updated = $sport->getUpdated();

        $this->em->detach($sport);
        $newSport = $this->em->merge($sport);

        $this->em->persist($newSport);
        $this->em->flush();

        static::assertSame($newSport->getUpdated(), $updated, 'There was no change, should remain the same');

        $newSport->setTitle('updated');
        $this->em->persist($newSport);
        $this->em->flush();

        static::assertNotSame($newSport->getUpdated(), $updated, 'There was a change, should not remain the same');
    }

    public function testShouldHandleStandardBehavior(): void
    {
        $sport = new Article();
        $sport->setTitle('Sport');
        $sport->setBody('Sport article body.');

        $sportComment = new Comment();
        $sportComment->setMessage('hello');
        $sportComment->setArticle($sport);
        $sportComment->setStatus(0);

        $author = new Author();
        $author->setName('Original author');
        $author->setEmail('original@author.dev');

        $sport->setAuthor($author);

        $this->em->persist($sport);
        $this->em->persist($sportComment);
        $this->em->flush();

        $sport = $this->em->getRepository(self::ARTICLE)->findOneBy(['title' => 'Sport']);
        static::assertNotNull($sc = $sport->getCreated());
        static::assertNotNull($su = $sport->getUpdated());
        static::assertNull($sport->getContentChanged());
        static::assertNull($sport->getPublished());
        static::assertNull($sport->getAuthorChanged());

        $author = $sport->getAuthor();
        $author->setName('New author');
        $sport->setAuthor($author);

        $sportComment = $this->em->getRepository(self::COMMENT)->findOneBy(['message' => 'hello']);
        static::assertNotNull($scm = $sportComment->getModified());
        static::assertNull($sportComment->getClosed());

        $sportComment->setStatus(1);
        $published = new Type();
        $published->setTitle('Published');

        $sport->setType($published);
        $this->em->persist($sport);
        $this->em->persist($published);
        $this->em->persist($sportComment);
        $this->em->flush();

        $sportComment = $this->em->getRepository(self::COMMENT)->findOneBy(['message' => 'hello']);
        static::assertNotNull($scc = $sportComment->getClosed());
        static::assertNotNull($sp = $sport->getPublished());
        static::assertNotNull($sa = $sport->getAuthorChanged());

        $sport->setTitle('Updated');
        $this->em->persist($sport);
        $this->em->persist($published);
        $this->em->persist($sportComment);
        $this->em->flush();

        static::assertSame($sport->getCreated(), $sc, 'Date created should remain same after update');
        static::assertNotSame($su2 = $sport->getUpdated(), $su, 'Date updated should change after update');
        static::assertSame($sport->getPublished(), $sp, 'Date published should remain the same after update');
        static::assertNotSame($scc2 = $sport->getContentChanged(), $scc, 'Content must have changed after update');
        static::assertSame($sport->getAuthorChanged(), $sa, 'Author should remain same after update');

        $author = $sport->getAuthor();
        $author->setName('Third author');
        $sport->setAuthor($author);

        $sport->setBody('Body updated');
        $this->em->persist($sport);
        $this->em->persist($published);
        $this->em->persist($sportComment);
        $this->em->flush();

        static::assertSame($sport->getCreated(), $sc, 'Date created should remain same after update');
        static::assertNotSame($sport->getUpdated(), $su2, 'Date updated should change after update');
        static::assertSame($sport->getPublished(), $sp, 'Date published should remain the same after update');
        static::assertNotSame($sport->getContentChanged(), $scc2, 'Content must have changed after update');
        static::assertNotSame($sport->getAuthorChanged(), $sa, 'Author must have changed after update');
    }

    public function testShouldBeAbleToForceDates(): void
    {
        $sport = new Article();
        $sport->setTitle('sport forced');
        $sport->setBody('Sport article body.');
        $sport->setCreated(new \DateTime('2000-01-01'));
        $sport->setUpdated(new \DateTime('2000-01-01 12:00:00'));
        $sport->setContentChanged(new \DateTime('2000-01-01 12:00:00'));

        $this->em->persist($sport);
        $this->em->flush();

        $repo = $this->em->getRepository(self::ARTICLE);
        $sport = $repo->findOneBy(['title' => 'sport forced']);
        static::assertSame(
            '2000-01-01',
            $sport->getCreated()->format('Y-m-d')
        );
        static::assertSame(
            '2000-01-01 12:00:00',
            $sport->getUpdated()->format('Y-m-d H:i:s')
        );
        static::assertSame(
            '2000-01-01 12:00:00',
            $sport->getContentChanged()->format('Y-m-d H:i:s')
        );

        $published = new Type();
        $published->setTitle('Published');

        $sport->setType($published);
        $sport->setPublished(new \DateTime('2000-01-01 12:00:00'));
        $this->em->persist($sport);
        $this->em->persist($published);
        $this->em->flush();

        $sport = $repo->findOneBy(['title' => 'sport forced']);
        static::assertSame(
            '2000-01-01 12:00:00',
            $sport->getPublished()->format('Y-m-d H:i:s')
        );

        $this->em->clear();
    }

    public function testShouldSolveIssue767(): void
    {
        $type = new Type();
        $type->setTitle('Published');

        $this->em->persist($type);
        $this->em->flush();
        $this->em->clear();

        $type = $this->em->getReference(self::TYPE, $type->getId());
        static::assertInstanceOf(Proxy::class, $type);

        $art = new Article();
        $art->setTitle('Art');
        $art->setBody('body');

        $this->em->persist($art);
        $this->em->flush();

        $art->setType($type);
        $this->em->flush(); // in v2.4.x will work on insert too

        static::assertNotNull($art->getPublished());
    }

    /**
     * @see https://github.com/doctrine-extensions/DoctrineExtensions/issues/2367.
     */
    public function testHandledTypes(): void
    {
        $timespampable = new Article();
        $timespampable->setTitle('My article');
        $timespampable->setBody('My article body.');

        static::assertNull($timespampable->getReachedRelevantLevel());
        $timespampable->setLevel(8);

        $this->em->persist($timespampable);
        $this->em->flush();

        $repo = $this->em->getRepository(self::ARTICLE);
        $found = $repo->findOneBy(['body' => 'My article body.']);

        static::assertNull($found->getReachedRelevantLevel());

        $timespampable->setLevel(9);

        $this->em->persist($timespampable);
        $this->em->flush();

        $found = $repo->findOneBy(['body' => 'My article body.']);

        static::assertNull($found->getReachedRelevantLevel());

        $timespampable->setLevel(10);

        $this->em->persist($timespampable);
        $this->em->flush();

        $found = $repo->findOneBy(['body' => 'My article body.']);

        static::assertInstanceOf(\DateTime::class, $found->getReachedRelevantLevel());
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::ARTICLE,
            self::COMMENT,
            self::TYPE,
        ];
    }
}
