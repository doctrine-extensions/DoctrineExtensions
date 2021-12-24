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
use Gedmo\Tests\Sluggable\Fixture\Issue1058\Page;
use Gedmo\Tests\Sluggable\Fixture\Issue1058\User;
use Gedmo\Tests\Tool\BaseTestCaseORM;

/**
 * These are tests for sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class Issue1058Test extends BaseTestCaseORM
{
    public const ARTICLE = Page::class;
    public const USER = User::class;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());

        $this->getDefaultMockSqliteEntityManager($evm);
    }

    /**
     * @group issue1058
     */
    public function testShouldHandleUniqueConstraintsBasedOnRelation(): void
    {
        $userFoo = new User();
        $this->em->persist($userFoo);

        $userBar = new User();
        $this->em->persist($userBar);

        $this->em->flush();

        $page = new Page();
        $page->setTitle('the title');
        $page->setUser($userFoo);

        $this->em->persist($page);
        $this->em->flush();
        static::assertSame('the-title', $page->getSlug());

        $page = new Page();
        $page->setTitle('the title');
        $page->setUser($userBar);

        $this->em->persist($page);
        $this->em->flush();
        static::assertSame('the-title', $page->getSlug());

        $page = new Page();
        $page->setTitle('the title');
        $page->setUser($userBar);

        $this->em->persist($page);
        $this->em->flush();
        static::assertSame('the-title-1', $page->getSlug());

        $page = new Page();
        $page->setTitle('the title');
        $page->setUser($userFoo);

        $this->em->persist($page);
        $this->em->flush();
        $this->em->clear();

        static::assertSame('the-title-1', $page->getSlug());
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::ARTICLE,
            self::USER,
        ];
    }
}
