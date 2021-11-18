<?php

namespace Gedmo\Tests\Sluggable;

use Doctrine\Common\EventManager;
use Gedmo\Tests\Sluggable\Fixture\Issue939\Article;
use Gedmo\Tests\Sluggable\Fixture\Issue939\Category;
use Gedmo\Tests\Sluggable\Fixture\Issue939\SluggableListener as SluggableListenerIssue939;
use Gedmo\Tests\Tool\BaseTestCaseORM;

/**
 * These are tests for Sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
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

        $this->getMockSqliteEntityManager($evm);
    }

    public function testSlugGeneration()
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

    protected function getUsedEntityFixtures()
    {
        return [
            self::ARTICLE,
            self::CATEGORY,
        ];
    }
}
