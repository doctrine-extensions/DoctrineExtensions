<?php

namespace Tree\NestedSet;

use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManager;
use Fixture\Tree\NestedSet\User\User;
use Fixture\Tree\NestedSet\User\UserGroup;
use Fixture\Tree\NestedSet\User\UserLDAP;
use Gedmo\Tree\TreeListener;
use TestTool\ObjectManagerTestCase;

class MultInheritanceWithJoinedTableTest extends ObjectManagerTestCase
{
    const USER = 'Fixture\Tree\NestedSet\User\User';
    const GROUP = 'Fixture\Tree\NestedSet\User\UserGroup';
    const ROLE = 'Fixture\Tree\NestedSet\User\Role';
    const USERLDAP = 'Fixture\Tree\NestedSet\User\UserLDAP';

    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var TreeListener
     */
    private $listener;

    protected function setUp()
    {
        $evm = new EventManager();
        $evm->addEventSubscriber($this->listener = new TreeListener());

        $this->em = $this->createEntityManager($evm);
        $this->createSchema($this->em, array(
            self::USER,
            self::GROUP,
            self::ROLE,
            self::USERLDAP,
        ));
        $this->populate();
    }

    protected function tearDown()
    {
        $this->releaseEntityManager($this->em);
    }

    /**
     * @test
     */
    public function shouldHandleMultilevelInheritance()
    {
        $admins = $this->em->getRepository(self::GROUP)->findOneByName('Admins');
        $adminRight = $admins->getRight();
        $userLdap = new UserLDAP('testname');
        $userLdap->init();
        $userLdap->setParent($admins);
        $this->em->persist($userLdap);
        $this->em->flush();
        $this->em->clear();

        $admins = $this->em->getRepository(self::GROUP)->findOneByName('Admins');
        self::assertNotEquals($adminRight, $admins->getRight());
    }

    /**
     * @test
     */
    public function shouldBeAbleToPopulateTree()
    {
        $admins = $this->em->getRepository(self::GROUP)->findOneByName('Admins');
        $user3 = new User('user3@test.com', 'secret');
        $user3->init();
        $user3->setParent($admins);

        $this->em->persist($user3);
        $this->em->flush();
        $this->em->clear();

        // run tree consistence checks

        $everyBody = $this->em->getRepository(self::GROUP)->findOneByName('Everybody');
        $this->assertEquals(1, $everyBody->getLeft());
        $this->assertEquals(14, $everyBody->getRight());
        $this->assertEquals(0, $everyBody->getLevel());

        $admins = $this->em->getRepository(self::GROUP)->findOneByName('Admins');
        $this->assertEquals(2, $admins->getLeft());
        $this->assertEquals(7, $admins->getRight());
        $this->assertEquals(1, $admins->getLevel());

        $visitors = $this->em->getRepository(self::GROUP)->findOneByName('Visitors');
        $this->assertEquals(8, $visitors->getLeft());
        $this->assertEquals(13, $visitors->getRight());
        $this->assertEquals(1, $visitors->getLevel());

        $user0 = $this->em->getRepository(self::USER)->findOneByEmail('user0@test.com');
        $this->assertEquals(3, $user0->getLeft());
        $this->assertEquals(4, $user0->getRight());
        $this->assertEquals(2, $user0->getLevel());

        $user1 = $this->em->getRepository(self::USER)->findOneByEmail('user1@test.com');
        $this->assertEquals(9, $user1->getLeft());
        $this->assertEquals(10, $user1->getRight());
        $this->assertEquals(2, $user1->getLevel());

        $user2 = $this->em->getRepository(self::USER)->findOneByEmail('user2@test.com');
        $this->assertEquals(11, $user2->getLeft());
        $this->assertEquals(12, $user2->getRight());
        $this->assertEquals(2, $user2->getLevel());

        $user3 = $this->em->getRepository(self::USER)->findOneByEmail('user3@test.com');
        $this->assertEquals(5, $user3->getLeft());
        $this->assertEquals(6, $user3->getRight());
        $this->assertEquals(2, $user3->getLevel());
    }

    private function populate()
    {
        $everyBody = new UserGroup('Everybody');
        $admins = new UserGroup('Admins');
        $admins->setParent($everyBody);
        $visitors = new UserGroup('Visitors');
        $visitors->setParent($everyBody);

        $user0 = new User('user0@test.com', 'secret');
        $user0->init();
        $user0->setParent($admins);
        $user1 = new User('user1@test.com', 'secret');
        $user1->init();
        $user1->setParent($visitors);
        $user2 = new User('user2@test.com', 'secret');
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
