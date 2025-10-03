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
use Gedmo\Tests\Sluggable\Fixture\Issue2990\Article;
use Gedmo\Tests\Sluggable\Fixture\Issue2990\ArticleRelativeSlug;
use Gedmo\Tests\Tool\BaseTestCaseORM;

/**
 * These are tests for sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class Issue2990Test extends BaseTestCaseORM
{
    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());

        $this->getDefaultMockSqliteEntityManager($evm);
    }

    /**
     * @group issue2990
     */
    public function testShouldHandleUrilizeProperly(): void
    {
        $article = new Article();
        $article->setTitle('My Title');

        $this->em->persist($article);
        $this->em->flush();

        static::assertSame('my-title', $article->getSlug());

        $relative = new ArticleRelativeSlug();
        $relative->setTitle('The Title');
        $relative->setArticle($article);

        $this->em->persist($relative);
        $this->em->flush();

        static::assertSame('my-title/the-title', $relative->getSlug());
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            Article::class,
            ArticleRelativeSlug::class,
        ];
    }
}
