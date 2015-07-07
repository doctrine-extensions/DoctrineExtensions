<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Sluggable\Fixture\Issue1058\Page;
use Sluggable\Fixture\Issue1058\User;

/**
 * These are tests for sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Issue1058Test extends BaseTestCaseORM
{
    const ARTICLE = 'Sluggable\\Fixture\\Issue1058\\Page';
    const USER = 'Sluggable\\Fixture\\Issue1058\\User';

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $evm->addEventSubscriber(new SluggableListener);

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
        $this->assertEquals('the-title', $page->getSlug());

        $page = new Page();
        $page->setTitle('the title');
        $page->setUser($userBar);

        $this->em->persist($page);
        $this->em->flush();
        $this->assertEquals('the-title', $page->getSlug());

        $page = new Page();
        $page->setTitle('the title');
        $page->setUser($userBar);

        $this->em->persist($page);
        $this->em->flush();
        $this->assertEquals('the-title-1', $page->getSlug());

        $page = new Page();
        $page->setTitle('the title');
        $page->setUser($userFoo);

        $this->em->persist($page);
        $this->em->flush();
        $this->em->clear();

        $this->assertEquals('the-title-1', $page->getSlug());
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::ARTICLE,
            self::USER
        );
    }
}
