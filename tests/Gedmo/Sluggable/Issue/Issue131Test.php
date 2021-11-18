<?php

namespace Gedmo\Tests\Sluggable;

use Doctrine\Common\EventManager;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Tests\Sluggable\Fixture\Issue131\Article;
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
final class Issue131Test extends BaseTestCaseORM
{
    public const TARGET = Article::class;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());

        $this->getMockSqliteEntityManager($evm);
    }

    public function testSlugGeneration()
    {
        $test = new Article();
        $test->setTitle('');

        $this->em->persist($test);
        $this->em->flush();

        static::assertNull($test->getSlug());

        $test2 = new Article();
        $test2->setTitle('');

        $this->em->persist($test2);
        $this->em->flush();

        static::assertNull($test2->getSlug());
    }

    /**
     * @test
     */
    public function shouldHandleOnlyZeroInSlug()
    {
        $article = new Article();
        $article->setTitle('0');

        $this->em->persist($article);
        $this->em->flush();

        static::assertSame('0', $article->getSlug());
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::TARGET,
        ];
    }
}
