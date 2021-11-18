<?php

namespace Gedmo\Tests\Sluggable;

use Doctrine\Common\EventManager;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Tests\Sluggable\Fixture\Issue1058\Page;
use Gedmo\Tests\Sluggable\Fixture\Issue1058\User;
use Gedmo\Tests\Tool\BaseTestCaseORM;

/**
 * These are tests for sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
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

        $this->getMockSqliteEntityManager($evm);
    }

    /**
     * @test
     * @group issue1058
     */
    public function shouldHandleUniqueConstraintsBasedOnRelation()
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

    protected function getUsedEntityFixtures()
    {
        return [
            self::ARTICLE,
            self::USER,
        ];
    }
}
