<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Tree;

use Doctrine\Common\EventManager;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Tests\Tree\Fixture\Role;
use Gedmo\Tests\Tree\Fixture\User;
use Gedmo\Tests\Tree\Fixture\UserGroup;
use Gedmo\Tests\Tree\Fixture\UserLDAP;
use Gedmo\Tree\TreeListener;

/**
 * These are tests for Tree behavior
 * Based on reported github issue #12
 * JOINED table inheritance mapping bug on Tree;
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class MultInheritanceWithJoinedTableTest extends BaseTestCaseORM
{
    public const USER = User::class;
    public const GROUP = UserGroup::class;
    public const ROLE = Role::class;
    public const USERLDAP = UserLDAP::class;

    /**
     * @var TreeListener
     */
    private $tree;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $this->tree = new TreeListener();
        $evm->addEventSubscriber($this->tree);

        $this->getDefaultMockSqliteEntityManager($evm);
        $this->populate();
    }

    public function testShouldHandleMultilevelInheritance(): void
    {
        $admins = $this->em->getRepository(self::GROUP)->findOneBy(['name' => 'Admins']);
        $adminRight = $admins->getRight();
        $userLdap = new \Gedmo\Tests\Tree\Fixture\UserLDAP('testname');
        $userLdap->init();
        $userLdap->setParent($admins);
        $this->em->persist($userLdap);
        $this->em->flush();
        $this->em->clear();

        $admins = $this->em->getRepository(self::GROUP)->findOneBy(['name' => 'Admins']);
        static::assertNotSame($adminRight, $admins->getRight());
    }

    public function testShouldBeAbleToPopulateTree(): void
    {
        $admins = $this->em->getRepository(self::GROUP)->findOneBy(['name' => 'Admins']);
        $user3 = new \Gedmo\Tests\Tree\Fixture\User('user3@test.com', 'secret');
        $user3->init();
        $user3->setParent($admins);

        $this->em->persist($user3);
        $this->em->flush();
        $this->em->clear();

        // run tree consistence checks

        $everyBody = $this->em->getRepository(self::GROUP)->findOneBy(['name' => 'Everybody']);
        static::assertSame(1, $everyBody->getLeft());
        static::assertSame(14, $everyBody->getRight());
        static::assertSame(0, $everyBody->getLevel());

        $admins = $this->em->getRepository(self::GROUP)->findOneBy(['name' => 'Admins']);
        static::assertSame(2, $admins->getLeft());
        static::assertSame(7, $admins->getRight());
        static::assertSame(1, $admins->getLevel());

        $visitors = $this->em->getRepository(self::GROUP)->findOneBy(['name' => 'Visitors']);
        static::assertSame(8, $visitors->getLeft());
        static::assertSame(13, $visitors->getRight());
        static::assertSame(1, $visitors->getLevel());

        $user0 = $this->em->getRepository(self::USER)->findOneBy(['email' => 'user0@test.com']);
        static::assertSame(3, $user0->getLeft());
        static::assertSame(4, $user0->getRight());
        static::assertSame(2, $user0->getLevel());

        $user1 = $this->em->getRepository(self::USER)->findOneBy(['email' => 'user1@test.com']);
        static::assertSame(9, $user1->getLeft());
        static::assertSame(10, $user1->getRight());
        static::assertSame(2, $user1->getLevel());

        $user2 = $this->em->getRepository(self::USER)->findOneBy(['email' => 'user2@test.com']);
        static::assertSame(11, $user2->getLeft());
        static::assertSame(12, $user2->getRight());
        static::assertSame(2, $user2->getLevel());

        $user3 = $this->em->getRepository(self::USER)->findOneBy(['email' => 'user3@test.com']);
        static::assertSame(5, $user3->getLeft());
        static::assertSame(6, $user3->getRight());
        static::assertSame(2, $user3->getLevel());
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::USER,
            self::GROUP,
            self::ROLE,
            self::USERLDAP,
        ];
    }

    private function populate(): void
    {
        $everyBody = new \Gedmo\Tests\Tree\Fixture\UserGroup('Everybody');
        $admins = new \Gedmo\Tests\Tree\Fixture\UserGroup('Admins');
        $admins->setParent($everyBody);
        $visitors = new \Gedmo\Tests\Tree\Fixture\UserGroup('Visitors');
        $visitors->setParent($everyBody);

        $user0 = new \Gedmo\Tests\Tree\Fixture\User('user0@test.com', 'secret');
        $user0->init();
        $user0->setParent($admins);
        $user1 = new \Gedmo\Tests\Tree\Fixture\User('user1@test.com', 'secret');
        $user1->init();
        $user1->setParent($visitors);
        $user2 = new \Gedmo\Tests\Tree\Fixture\User('user2@test.com', 'secret');
        $user2->init();
        $user2->setParent($visitors);

        $this->em->persist($everyBody);
        $this->em->persist($admins);
        $this->em->persist($visitors);
        $this->em->persist($user0);
        $this->em->persist($user1);
        $this->em->persist($user2);
        $this->em->flush();
        $this->em->clear();
    }
}
