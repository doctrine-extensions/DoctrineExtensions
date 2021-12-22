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
use Gedmo\Tests\Sluggable\Fixture\Issue131\Article;
use Gedmo\Tests\Tool\BaseTestCaseORM;

/**
 * These are tests for Sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class Issue131Test extends BaseTestCaseORM
{
    public const TARGET = Article::class;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());

        $this->getDefaultMockSqliteEntityManager($evm);
    }

    public function testSlugGeneration(): void
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

    public function testShouldHandleOnlyZeroInSlug(): void
    {
        $article = new Article();
        $article->setTitle('0');

        $this->em->persist($article);
        $this->em->flush();

        static::assertSame('0', $article->getSlug());
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::TARGET,
        ];
    }
}
