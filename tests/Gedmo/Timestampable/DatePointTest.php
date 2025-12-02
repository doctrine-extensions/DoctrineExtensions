<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Timestampable;

use Doctrine\Common\EventManager;
use Gedmo\Tests\Timestampable\Fixture\ArticleDatePoint;
use Gedmo\Tests\Timestampable\Fixture\Author;
use Gedmo\Tests\Timestampable\Fixture\CommentDatePoint;
use Gedmo\Tests\Timestampable\Fixture\Type;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Timestampable\TimestampableListener;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\Clock\NativeClock;

final class DatePointTest extends BaseTestCaseORM
{
    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new TimestampableListener());

        $this->getDefaultMockSqliteEntityManager($evm);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function testShouldHandleStandardBehavior(): void
    {
        $sport = new ArticleDatePoint();
        $sport->setTitle('Sport');
        $sport->setBody('Sport article body.');

        $sportComment = new CommentDatePoint();
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

        /** @var ArticleDatePoint $sport */
        $sport = $this->em->getRepository(ArticleDatePoint::class)->findOneBy(['title' => 'Sport']);
        static::assertInstanceOf(DatePoint::class, $su = $sport->getUpdated());

        static::assertNull($sport->getContentChanged());
        static::assertNull($sport->getPublished());
        static::assertNull($sport->getAuthorChanged());

        $author = $sport->getAuthor();
        $author->setName('New author');
        $sport->setAuthor($author);

        /** @var CommentDatePoint $sportComment */
        $sportComment = $this->em->getRepository(CommentDatePoint::class)->findOneBy(['message' => 'hello']);
        static::assertInstanceOf(DatePoint::class, $sc = $sportComment->getModified());

        static::assertNotNull($sportComment->getModified());
        static::assertNull($sportComment->getClosed());

        $sportComment->setStatus(1);
        $published = new Type();
        $published->setTitle('Published');

        $sport->setType($published);
        $this->em->persist($sport);
        $this->em->persist($published);
        $this->em->persist($sportComment);
        $this->em->flush();

        $sportComment = $this->em->getRepository(CommentDatePoint::class)->findOneBy(['message' => 'hello']);
        static::assertInstanceOf(DatePoint::class, $scc = $sportComment->getClosed());
        static::assertInstanceOf(DatePoint::class, $sp = $sport->getPublished());
        static::assertInstanceOf(DatePoint::class, $sa = $sport->getAuthorChanged());

        $sport->setTitle('Updated');
        $this->em->persist($sport);
        $this->em->persist($published);
        $this->em->persist($sportComment);
        $this->em->flush();

        // prevent "Failed asserting that two variables reference the same object." error, hence compare unix epoch
        static::assertEquals($sport->getCreated()->format('U'), $sc->format('U'), 'Date created should remain same after update');
        static::assertNotSame($su2 = $sport->getUpdated(), $su, 'Date updated should change after update');
        static::assertInstanceOf(DatePoint::class, $sport->getUpdated());
        static::assertSame($sport->getPublished(), $sp, 'Date published should remain the same after update');
        static::assertNotSame($scc2 = $sport->getContentChanged(), $scc, 'Content must have changed after update');
        static::assertInstanceOf(DatePoint::class, $sport->getContentChanged());
        static::assertSame($sport->getAuthorChanged(), $sa, 'Author should remain same after update');

        $author = $sport->getAuthor();
        $author->setName('Third author');
        $sport->setAuthor($author);

        $sport->setBody('Body updated');
        $this->em->persist($sport);
        $this->em->persist($published);
        $this->em->persist($sportComment);
        $this->em->flush();

        // prevent "Failed asserting that two variables reference the same object." error, hence compare unix epoch
        static::assertEquals($sport->getCreated()->format('U'), $sc->format('U'), 'Date created should remain same after update');
        static::assertNotSame($sport->getUpdated(), $su2, 'Date updated should change after update');
        static::assertSame($sport->getPublished(), $sp, 'Date published should remain the same after update');
        static::assertNotSame($sport->getContentChanged(), $scc2, 'Content must have changed after update');
        static::assertNotSame($sport->getAuthorChanged(), $sa, 'Author must have changed after update');
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            ArticleDatePoint::class,
            CommentDatePoint::class,
            Type::class,
        ];
    }
}
