<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sluggable;

use Doctrine\Common\EventManager;
use Gedmo\Sluggable\Sluggable;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Tests\Sluggable\Fixture\DateTimeTypes\ArticleDate;
use Gedmo\Tests\Sluggable\Fixture\DateTimeTypes\ArticleDateImmutable;
use Gedmo\Tests\Sluggable\Fixture\DateTimeTypes\ArticleDateTime;
use Gedmo\Tests\Sluggable\Fixture\DateTimeTypes\ArticleDateTimeImmutable;
use Gedmo\Tests\Sluggable\Fixture\DateTimeTypes\ArticleDateTimeTz;
use Gedmo\Tests\Sluggable\Fixture\DateTimeTypes\ArticleDateTimeTzImmutable;
use Gedmo\Tests\Tool\BaseTestCaseORM;

/**
 * These are tests for sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class SluggableDateTimeTypesTest extends BaseTestCaseORM
{
    public const ARTICLE_DATE = ArticleDate::class;
    public const ARTICLE_DATE_IMMUTABLE = ArticleDateImmutable::class;
    public const ARTICLE_DATETIME = ArticleDateTime::class;
    public const ARTICLE_DATETIME_IMMUTABLE = ArticleDateTimeImmutable::class;
    public const ARTICLE_DATETIME_TZ = ArticleDateTimeTz::class;
    public const ARTICLE_DATETIME_TZ_IMMUTABLE = ArticleDateTimeTzImmutable::class;

    /**
     * @var int|null
     */
    private $articleId;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());

        $this->getDefaultMockSqliteEntityManager($evm);
        $this->populate();
    }

    public function testShouldInsertNewSlug(): void
    {
        $article = $this->em->find(self::ARTICLE_DATETIME_IMMUTABLE, $this->articleId);

        static::assertInstanceOf(Sluggable::class, $article);
        static::assertSame('the-title-2022-04-01', $article->getSlug());
    }

    public function testShouldBuildUniqueSlug(): void
    {
        for ($i = 0; $i < 12; ++$i) {
            $article = new ArticleDateTimeImmutable();
            $article->setTitle('the title');
            $article->setCreatedAt(new \DateTimeImmutable('2022-04-01'));

            $this->em->persist($article);
            $this->em->flush();
            $this->em->clear();
            static::assertSame($article->getSlug(), 'the-title-2022-04-01-'.($i + 1));
        }
    }

    public function testShouldBuildSlugWithAllDateTimeTypes(): void
    {
        $articleDate = new ArticleDate();
        $articleDate->setTitle('the title');
        $articleDate->setCreatedAt(new \DateTime('2022-04-01'));

        $this->em->persist($articleDate);
        $this->em->flush();
        $this->em->clear();
        static::assertSame('the-title-2022-04-01', $articleDate->getSlug(), 'with date');

        $articleDateImmutable = new ArticleDateImmutable();
        $articleDateImmutable->setTitle('the title');
        $articleDateImmutable->setCreatedAt(new \DateTimeImmutable('2022-04-01'));

        $this->em->persist($articleDateImmutable);
        $this->em->flush();
        $this->em->clear();
        static::assertSame('the-title-2022-04-01', $articleDateImmutable->getSlug(), 'with date_immutable');

        $articleDateTime = new ArticleDateTime();
        $articleDateTime->setTitle('the title');
        $articleDateTime->setCreatedAt(new \DateTime('2022-04-01'));

        $this->em->persist($articleDateTime);
        $this->em->flush();
        $this->em->clear();
        static::assertSame('the-title-2022-04-01', $articleDateTime->getSlug(), 'with datetime');

        $articleDateTimeImmutable = new ArticleDateTimeImmutable();
        $articleDateTimeImmutable->setTitle('the title');
        $articleDateTimeImmutable->setCreatedAt(new \DateTimeImmutable('2022-04-01'));

        $this->em->persist($articleDateTimeImmutable);
        $this->em->flush();
        $this->em->clear();
        static::assertSame('the-title-2022-04-01-1', $articleDateTimeImmutable->getSlug(), 'with datetime_immutable');

        $articleDateTimeTz = new ArticleDateTimeTz();
        $articleDateTimeTz->setTitle('the title');
        $articleDateTimeTz->setCreatedAt(new \DateTime('2022-04-01'));

        $this->em->persist($articleDateTimeTz);
        $this->em->flush();
        $this->em->clear();
        static::assertSame('the-title-2022-04-01', $articleDateTimeTz->getSlug(), 'with datetimetz');

        $articleDateTimeTzImmutable = new ArticleDateTimeTzImmutable();
        $articleDateTimeTzImmutable->setTitle('the title');
        $articleDateTimeTzImmutable->setCreatedAt(new \DateTimeImmutable('2022-04-01'));

        $this->em->persist($articleDateTimeTzImmutable);
        $this->em->flush();
        $this->em->clear();
        static::assertSame('the-title-2022-04-01', $articleDateTimeTzImmutable->getSlug(), 'with datetimetz_immutable');
    }

    public function testShouldHandleUniqueSlugLimitedLength(): void
    {
        $long = 'the title the title the title the title the title the title the title';
        $article = new ArticleDateTimeImmutable();
        $article->setTitle($long);
        $article->setCreatedAt(new \DateTimeImmutable('2022-04-01'));

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();
        for ($i = 1; $i <= 12; ++$i) {
            $uniqueSuffix = (string) $i;

            $article = new ArticleDateTimeImmutable();
            $article->setTitle($long);
            $article->setCreatedAt(new \DateTimeImmutable('2022-04-01'));

            $this->em->persist($article);
            $this->em->flush();
            $this->em->clear();

            $shorten = $article->getSlug();
            static::assertSame(64, strlen($shorten));
            $expected = 'the-title-the-title-the-title-the-title-the-title-the-title-the-';
            $expected = substr($expected, 0, 64 - (strlen($uniqueSuffix) + 1)).'-'.$uniqueSuffix;
            static::assertSame($shorten, $expected);
        }
    }

    public function testDoubleDelimiterShouldBeRemoved(): void
    {
        $long = 'Sample long title which should be correctly slugged blablabla';
        $article = new ArticleDateTimeImmutable();
        $article->setTitle($long);
        $article->setCreatedAt(new \DateTimeImmutable('2022-04-01'));
        $article2 = new ArticleDateTimeImmutable();
        $article2->setTitle($long);
        $article2->setCreatedAt(new \DateTimeImmutable('2022-04-01'));

        $this->em->persist($article);
        $this->em->persist($article2);
        $this->em->flush();
        $this->em->clear();
        static::assertSame('sample-long-title-which-should-be-correctly-slugged-blablabla-20', $article->getSlug());
        // OLD IMPLEMENTATION PRODUCE SLUG sample-long-title-which-should-be-correctly-slugged-blablabla--1
        static::assertSame('sample-long-title-which-should-be-correctly-slugged-blablabla-1', $article2->getSlug());
    }

    public function testShouldUpdateSlug(): void
    {
        $article = $this->em->find(self::ARTICLE_DATETIME_IMMUTABLE, $this->articleId);
        $article->setTitle('the title updated');
        $this->em->persist($article);
        $this->em->flush();

        static::assertSame('the-title-updated-2022-04-01', $article->getSlug());
    }

    public function testShouldBeAbleToForceRegenerationOfSlug(): void
    {
        $article = $this->em->find(self::ARTICLE_DATETIME_IMMUTABLE, $this->articleId);
        $article->setSlug(null);
        $this->em->persist($article);
        $this->em->flush();

        static::assertSame('the-title-2022-04-01', $article->getSlug());
    }

    public function testShouldBeAbleToForceTheSlug(): void
    {
        $article = $this->em->find(self::ARTICLE_DATETIME_IMMUTABLE, $this->articleId);
        $article->setSlug('my-forced-slug');
        $this->em->persist($article);

        $new = new ArticleDateTimeImmutable();
        $new->setTitle('hey');
        $new->setCreatedAt(new \DateTimeImmutable('2022-04-01'));
        $new->setSlug('forced');
        $this->em->persist($new);

        $this->em->flush();
        static::assertSame('my-forced-slug', $article->getSlug());
        static::assertSame('forced', $new->getSlug());
    }

    public function testShouldSolveGithubIssue45(): void
    {
        // persist new records with same slug
        $article = new ArticleDateTimeImmutable();
        $article->setTitle('test');
        $article->setCreatedAt(new \DateTimeImmutable('2022-04-01'));
        $this->em->persist($article);

        $article2 = new ArticleDateTimeImmutable();
        $article2->setTitle('test');
        $article2->setCreatedAt(new \DateTimeImmutable('2022-04-01'));
        $this->em->persist($article2);

        $this->em->flush();
        static::assertSame('test-2022-04-01', $article->getSlug());
        static::assertSame('test-2022-04-01-1', $article2->getSlug());
    }

    public function testShouldAllowForcingEmptySlugAndRegenerateIfNullIssue807(): void
    {
        $article = $this->em->find(self::ARTICLE_DATETIME_IMMUTABLE, $this->articleId);
        $article->setSlug('');

        $this->em->persist($article);
        $this->em->flush();

        static::assertSame('', $article->getSlug());
        $article->setSlug(null);

        $this->em->persist($article);
        $this->em->flush();

        static::assertSame('the-title-2022-04-01', $article->getSlug());

        $same = new ArticleDateTimeImmutable();
        $same->setTitle('any');
        $same->setCreatedAt(new \DateTimeImmutable('2022-04-01'));
        $same->setSlug('the-title-2022-04-01');
        $this->em->persist($same);
        $this->em->flush();

        static::assertSame('the-title-2022-04-01-1', $same->getSlug());
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::ARTICLE_DATE,
            self::ARTICLE_DATE_IMMUTABLE,
            self::ARTICLE_DATETIME,
            self::ARTICLE_DATETIME_IMMUTABLE,
            self::ARTICLE_DATETIME_TZ,
            self::ARTICLE_DATETIME_TZ_IMMUTABLE,
        ];
    }

    private function populate(): void
    {
        $article = new ArticleDateTimeImmutable();
        $article->setTitle('the title');
        $article->setCreatedAt(new \DateTimeImmutable('2022-04-01'));

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();
        $this->articleId = $article->getId();
    }
}
