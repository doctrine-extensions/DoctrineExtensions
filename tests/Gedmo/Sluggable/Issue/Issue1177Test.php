<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sluggable\Issue;

use Doctrine\Common\EventManager;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Tests\Sluggable\Fixture\Issue1177\Article;
use Gedmo\Tests\Tool\BaseTestCaseORM;

/**
 * These are tests for sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class Issue1177Test extends BaseTestCaseORM
{
    public const ARTICLE = Article::class;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());

        $this->getDefaultMockSqliteEntityManager($evm);
    }

    public function testShouldTryPreferedSlugFirst(): void
    {
        $article = new Article();
        $article->setTitle('the title with number 1');

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();
        static::assertSame('the-title-with-number-1', $article->getSlug());

        $article = new Article();
        $article->setTitle('the title with number');

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();
        // the slug was 'the-title-with-number-2' before the fix here
        // despite the fact that there is no entity with slug 'the-title-with-number'
        static::assertSame('the-title-with-number', $article->getSlug());

        $article = new Article();
        $article->setTitle('the title with number');

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();
        static::assertSame('the-title-with-number-2', $article->getSlug());
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::ARTICLE,
        ];
    }
}
