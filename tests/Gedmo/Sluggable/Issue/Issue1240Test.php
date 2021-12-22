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
use Gedmo\Tests\Sluggable\Fixture\Issue1240\Article;
use Gedmo\Tests\Tool\BaseTestCaseORM;

/**
 * These are tests for sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class Issue1240Test extends BaseTestCaseORM
{
    public const ARTICLE = Article::class;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());

        $this->getDefaultMockSqliteEntityManager($evm);
    }

    public function testShouldWorkWithPlusAsSeparator(): void
    {
        $article = new Article();
        $article->setTitle('the title');
        $this->em->persist($article);

        $article2 = new Article();
        $article2->setTitle('the title');
        $this->em->persist($article2);

        $this->em->flush();
        $this->em->clear();

        static::assertSame('the+title', $article->getSlug());
        static::assertSame('The+Title', $article->getCamelSlug());

        static::assertSame('the+title+1', $article2->getSlug());
        static::assertSame('The+Title+1', $article2->getCamelSlug());

        $article = new Article();
        $article->setTitle('the title');

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();
        static::assertSame('the+title+2', $article->getSlug());
        static::assertSame('The+Title+2', $article->getCamelSlug());
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::ARTICLE,
        ];
    }
}
