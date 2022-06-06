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
use Gedmo\Tests\Sluggable\Fixture\Article;
use Gedmo\Tests\Tool\BaseTestCaseORM;

/**
 * These are tests for sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class SluggableTest extends BaseTestCaseORM
{
    public const ARTICLE = Article::class;

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
        $article = $this->em->find(self::ARTICLE, $this->articleId);

        static::assertInstanceOf(Sluggable::class, $article);
        static::assertSame('the-title-my-code', $article->getSlug());
    }

    public function testShouldBuildUniqueSlug(): void
    {
        for ($i = 0; $i < 12; ++$i) {
            $article = new Article();
            $article->setTitle('the title');
            $article->setCode('my code');

            $this->em->persist($article);
            $this->em->flush();
            $this->em->clear();
            static::assertSame($article->getSlug(), 'the-title-my-code-'.($i + 1));
        }
    }

    public function testShouldHandleUniqueSlugLimitedLength(): void
    {
        $long = 'the title the title the title the title the title the title the title';
        $article = new Article();
        $article->setTitle($long);
        $article->setCode('my code');

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();
        for ($i = 1; $i <= 12; ++$i) {
            $uniqueSuffix = (string) $i;

            $article = new Article();
            $article->setTitle($long);
            $article->setCode('my code');

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
        $article = new Article();
        $article->setTitle($long);
        $article->setCode('my code');
        $article2 = new Article();
        $article2->setTitle($long);
        $article2->setCode('my code');

        $this->em->persist($article);
        $this->em->persist($article2);
        $this->em->flush();
        $this->em->clear();
        static::assertSame('sample-long-title-which-should-be-correctly-slugged-blablabla-my', $article->getSlug());
        // OLD IMPLEMENTATION PRODUCE SLUG sample-long-title-which-should-be-correctly-slugged-blablabla--1
        static::assertSame('sample-long-title-which-should-be-correctly-slugged-blablabla-1', $article2->getSlug());
    }

    public function testShouldHandleNumbersInSlug(): void
    {
        $article = new Article();
        $article->setTitle('the title');
        $article->setCode('my code 123');

        $this->em->persist($article);
        $this->em->flush();
        for ($i = 0; $i < 12; ++$i) {
            $article = new Article();
            $article->setTitle('the title');
            $article->setCode('my code 123');

            $this->em->persist($article);
            $this->em->flush();
            $this->em->clear();
            static::assertSame($article->getSlug(), 'the-title-my-code-123-'.($i + 1));
        }
    }

    public function testShouldUpdateSlug(): void
    {
        $article = $this->em->find(self::ARTICLE, $this->articleId);
        $article->setTitle('the title updated');
        $this->em->persist($article);
        $this->em->flush();

        static::assertSame('the-title-updated-my-code', $article->getSlug());
    }

    public function testShouldBeAbleToForceRegenerationOfSlug(): void
    {
        $article = $this->em->find(self::ARTICLE, $this->articleId);
        $article->setSlug(null);
        $this->em->persist($article);
        $this->em->flush();

        static::assertSame('the-title-my-code', $article->getSlug());
    }

    public function testShouldBeAbleToForceTheSlug(): void
    {
        $article = $this->em->find(self::ARTICLE, $this->articleId);
        $article->setSlug('my-forced-slug');
        $this->em->persist($article);

        $new = new Article();
        $new->setTitle('hey');
        $new->setCode('cc');
        $new->setSlug('forced');
        $this->em->persist($new);

        $this->em->flush();
        static::assertSame('my-forced-slug', $article->getSlug());
        static::assertSame('forced', $new->getSlug());
    }

    public function testShouldSolveGithubIssue45(): void
    {
        // persist new records with same slug
        $article = new Article();
        $article->setTitle('test');
        $article->setCode('code');
        $this->em->persist($article);

        $article2 = new Article();
        $article2->setTitle('test');
        $article2->setCode('code');
        $this->em->persist($article2);

        $this->em->flush();
        static::assertSame('test-code', $article->getSlug());
        static::assertSame('test-code-1', $article2->getSlug());
    }

    public function testShouldSolveGithubIssue57(): void
    {
        // slug matched by prefix
        $article = new Article();
        $article->setTitle('my');
        $article->setCode('slug');
        $this->em->persist($article);

        $article2 = new Article();
        $article2->setTitle('my');
        $article2->setCode('s');
        $this->em->persist($article2);

        $this->em->flush();
        static::assertSame('my-s', $article2->getSlug());
    }

    public function testShouldAllowForcingEmptySlugAndRegenerateIfNullIssue807(): void
    {
        $article = $this->em->find(self::ARTICLE, $this->articleId);
        $article->setSlug('');

        $this->em->persist($article);
        $this->em->flush();

        static::assertSame('', $article->getSlug());
        $article->setSlug(null);

        $this->em->persist($article);
        $this->em->flush();

        static::assertSame('the-title-my-code', $article->getSlug());

        $same = new Article();
        $same->setTitle('any');
        $same->setCode('any');
        $same->setSlug('the-title-my-code');
        $this->em->persist($same);
        $this->em->flush();

        static::assertSame('the-title-my-code-1', $same->getSlug());
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::ARTICLE,
        ];
    }

    private function populate(): void
    {
        $article = new Article();
        $article->setTitle('the title');
        $article->setCode('my code');

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();
        $this->articleId = $article->getId();
    }
}
