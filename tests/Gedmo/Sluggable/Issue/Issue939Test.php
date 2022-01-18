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
use Gedmo\Tests\Sluggable\Fixture\Issue939\Article;
use Gedmo\Tests\Sluggable\Fixture\Issue939\Category;
use Gedmo\Tests\Sluggable\Fixture\Issue939\SluggableListener as SluggableListenerIssue939;
use Gedmo\Tests\Tool\BaseTestCaseORM;

/**
 * These are tests for Sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class Issue939Test extends BaseTestCaseORM
{
    public const ARTICLE = Article::class;
    public const CATEGORY = Category::class;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListenerIssue939());

        $this->getDefaultMockSqliteEntityManager($evm);
    }

    public function testSlugGeneration(): void
    {
        $category = new Category();
        $category->setTitle('Misc articles');
        $this->em->persist($category);

        $article = new Article();
        $article->setTitle('Is there water on the moon?');
        $article->setCategory($category);

        $this->em->persist($article);
        $this->em->flush();

        static::assertSame('Is there water on the moon?', $article->getSlug());
        static::assertSame('misc-articles', $category->getSlug());
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::ARTICLE,
            self::CATEGORY,
        ];
    }
}
