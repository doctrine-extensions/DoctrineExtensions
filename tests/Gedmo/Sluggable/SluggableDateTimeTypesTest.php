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

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());

        $this->getDefaultMockSqliteEntityManager($evm);
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
        static::assertSame('the-title-2022-04-01', $articleDateTimeImmutable->getSlug(), 'with datetime_immutable');

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
}
