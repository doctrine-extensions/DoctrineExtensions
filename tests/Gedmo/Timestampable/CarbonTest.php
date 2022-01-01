<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Timestampable;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\Doctrine\DateTimeImmutableType;
use Carbon\Doctrine\DateTimeType;
use DateTime;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Types\DateType;
use Doctrine\DBAL\Types\Type as DoctrineType;
use Doctrine\DBAL\Types\Types;
use Gedmo\Tests\Timestampable\Fixture\ArticleCarbon;
use Gedmo\Tests\Timestampable\Fixture\Author;
use Gedmo\Tests\Timestampable\Fixture\CommentCarbon;
use Gedmo\Tests\Timestampable\Fixture\Type;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Timestampable\TimestampableListener;

final class CarbonTest extends BaseTestCaseORM
{
    public const ARTICLE = ArticleCarbon::class;
    public const COMMENT = CommentCarbon::class;
    public const TYPE = Type::class;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new TimestampableListener());

        $this->getDefaultMockSqliteEntityManager($evm);

        /**
         * DATE_MUTABLE => Carbon
         * DATETIME_MUTABLE => CarbonImmutable
         * TIME_MUTABLE => DateTime
         */
        DoctrineType::overrideType(Types::DATE_MUTABLE, DateTimeType::class);
        DoctrineType::overrideType(Types::DATETIME_MUTABLE, DateTimeImmutableType::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        DoctrineType::overrideType(Types::DATE_MUTABLE, DateType::class);
        DoctrineType::overrideType(Types::DATETIME_MUTABLE, \Doctrine\DBAL\Types\DateTimeType::class);
    }

    public function testShouldHandleStandardBehavior(): void
    {
        $sport = new ArticleCarbon();
        $sport->setTitle('Sport');
        $sport->setBody('Sport article body.');

        $sportComment = new CommentCarbon();
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

        /** @var ArticleCarbon $sport */
        $sport = $this->em->getRepository(self::ARTICLE)->findOneBy(['title' => 'Sport']);
        static::assertInstanceOf(CarbonImmutable::class, $sport->getUpdated(), 'Type DATETIME_MUTABLE should become CarbonImmutable');
        static::assertInstanceOf(Carbon::class, $sport->getCreated(), 'Type DATE_MUTABLE should become Carbon');

        static::assertNotNull($sc = $sport->getCreated());
        static::assertNotNull($su = $sport->getUpdated());
        static::assertNull($sport->getContentChanged());
        static::assertNull($sport->getPublished());
        static::assertNull($sport->getAuthorChanged());

        $author = $sport->getAuthor();
        $author->setName('New author');
        $sport->setAuthor($author);

        /** @var \Gedmo\Tests\Timestampable\Fixture\CommentCarbon $sportComment */
        $sportComment = $this->em->getRepository(self::COMMENT)->findOneBy(['message' => 'hello']);
        static::assertInstanceOf(DateTime::class, $sportComment->getModified(), 'Type TIME_MUTABLE should stay DateTime');

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
        static::assertInstanceOf(CarbonImmutable::class, $sportComment->getClosed(), 'Type DATETIME_MUTABLE should become CarbonImmutable');
        static::assertInstanceOf(CarbonImmutable::class, $sport->getPublished(), 'Type DATETIME_MUTABLE should become CarbonImmutable');
        static::assertInstanceOf(CarbonImmutable::class, $sport->getAuthorChanged(), 'Type DATETIME_MUTABLE should become CarbonImmutable');

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
        static::assertInstanceOf(CarbonImmutable::class, $sport->getUpdated(), 'Type DATETIME_MUTABLE should become CarbonImmutable');
        static::assertSame($sport->getPublished(), $sp, 'Date published should remain the same after update');
        static::assertNotSame($scc2 = $sport->getContentChanged(), $scc, 'Content must have changed after update');
        static::assertInstanceOf(CarbonImmutable::class, $sport->getContentChanged(), 'Type DATETIME_MUTABLE should become CarbonImmutable');
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

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::ARTICLE,
            self::COMMENT,
            self::TYPE,
        ];
    }
}
