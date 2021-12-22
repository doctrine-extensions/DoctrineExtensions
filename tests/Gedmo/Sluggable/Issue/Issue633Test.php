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
use Gedmo\Tests\Sluggable\Fixture\Issue633\Article;
use Gedmo\Tests\Tool\BaseTestCaseORM;

/**
 * These are tests for Sluggable behavior
 *
 * @author Derek Clapham <derek.clapham@gmail.com>
 */
final class Issue633Test extends BaseTestCaseORM
{
    public const TARGET = Article::class;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());

        $this->getDefaultMockSqliteEntityManager($evm);
    }

    public function testShouldHandleUniqueBasedSlug(): void
    {
        $test = new Article();
        $test->setTitle('Unique to code');
        $test->setCode('CODE001');

        $this->em->persist($test);
        $this->em->flush();

        static::assertSame('unique-to-code', $test->getSlug());

        $test2 = new Article();
        $test2->setTitle('Unique to code');
        $test2->setCode('CODE002');

        $this->em->persist($test2);
        $this->em->flush();

        static::assertSame('unique-to-code', $test2->getSlug());

        $test3 = new Article();
        $test3->setTitle('Unique to code');
        $test3->setCode('CODE001');

        $this->em->persist($test3);
        $this->em->flush();

        static::assertSame('unique-to-code-1', $test3->getSlug());
    }

    public function testHandlePersistedSlugsForUniqueBased(): void
    {
        $test = new Article();
        $test->setTitle('Unique to code');
        $test->setCode('CODE001');

        $this->em->persist($test);

        $test2 = new Article();
        $test2->setTitle('Unique to code');
        $test2->setCode('CODE002');

        $this->em->persist($test2);

        $test3 = new Article();
        $test3->setTitle('Unique to code');
        $test3->setCode('CODE001');

        $this->em->persist($test3);
        $this->em->flush();

        static::assertSame('unique-to-code', $test->getSlug());
        static::assertSame('unique-to-code', $test2->getSlug());
        static::assertSame('unique-to-code-1', $test3->getSlug());
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::TARGET,
        ];
    }
}
