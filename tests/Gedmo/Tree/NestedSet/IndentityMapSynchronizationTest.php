<?php

namespace Gedmo\Tree\NestedSet;

use Doctrine\Common\EventManager;
use Gedmo\TestTool\ObjectManagerTestCase;
use Gedmo\Tree\TreeListener;
use Gedmo\Fixture\Tree\NestedSet\Category;
use Gedmo\Fixture\Tree\NestedSet\Genealogy\Man;
use Gedmo\Fixture\Tree\NestedSet\Genealogy\Woman;

class IndentityMapSynchronizationTest extends ObjectManagerTestCase
{
    const CATEGORY = "Gedmo\Fixture\Tree\NestedSet\Category";
    const PERSON = "Gedmo\Fixture\Tree\NestedSet\Genealogy\Person";
    const MAN = "Gedmo\Fixture\Tree\NestedSet\Genealogy\Man";
    const WOMAN = "Gedmo\Fixture\Tree\NestedSet\Genealogy\Woman";

    private $em;

    protected function setUp()
    {
        $evm = new EventManager;
        $evm->addEventSubscriber(new TreeListener);

        $this->em = $this->createEntityManager($evm);
        $this->createSchema($this->em, array(
            self::CATEGORY,
            self::PERSON,
            self::MAN,
            self::WOMAN
        ));
    }

    protected function tearDown()
    {
        $this->releaseEntityManager($this->em);
    }

    /**
     * @test
     */
    function shouldSynchronizeInsertedNodesInIdentityMap()
    {
        $meta = $this->em->getClassMetadata(self::CATEGORY);
        $repo = $this->em->getRepository(self::CATEGORY);

        $root = new Category();
        $this->em->persist($root);
        $root->setTitle("Root");

        $child = new Category();
        $this->em->persist($child);
        $child->setTitle("child");

        $child2 = new Category();
        $this->em->persist($child2);
        $child2->setTitle("child2");

        $child2->setParent($root);
        $child->setParent($root);

        $this->em->flush();

        $childsChild = new Category();
        $this->em->persist($childsChild);
        $childsChild->setTitle("childs_child");
        $childsChild->setParent($child);

        $this->em->flush();
        $this->em->clear();

        $node = $repo->find(2);
        $left = $meta->getReflectionProperty('lft')->getValue($node);
        $right = $meta->getReflectionProperty('rgt')->getValue($node);
        $this->assertSame(2, $left);
        $this->assertSame(5, $right);

        $node = $repo->find(3);
        $left = $meta->getReflectionProperty('lft')->getValue($node);
        $right = $meta->getReflectionProperty('rgt')->getValue($node);
        $this->assertSame(6, $left);
        $this->assertSame(7, $right);

        $node = $repo->find(4);
        $left = $meta->getReflectionProperty('lft')->getValue($node);
        $right = $meta->getReflectionProperty('rgt')->getValue($node);
        $this->assertSame(3, $left);
        $this->assertSame(4, $right);
    }

    /**
     * @test
     */
    function shouldSynchronizeIdentityMapWithInheritance()
    {
        $nodes = array();

        $man1 = new Man('Root - Man1');
        $this->em->persist($man1);

        $woman1 = new Woman('Level 1 - Woman1');
        $this->em->persist($woman1);
        $woman1->setParent($man1);

        $man2 = new Man('Level 2 - Man2');
        $this->em->persist($man2);
        $man2->setParent($woman1);

        $woman2 = new Woman('Level 3 - Woman2');
        $this->em->persist($woman2);
        $woman2->setParent($man2);

        $this->em->flush();

        $left = $man1->getLeft();
        $right = $man1->getRight();
        $level = $man1->getLevel();
        $this->assertSame(1, $left);
        $this->assertSame(8, $right);
        $this->assertSame(0, $level);

        $left = $woman1->getLeft();
        $right = $woman1->getRight();
        $level = $woman1->getLevel();
        $this->assertSame(2, $left);
        $this->assertSame(7, $right);
        $this->assertSame(1, $level);

        $left = $man2->getLeft();
        $right = $man2->getRight();
        $level = $man2->getLevel();
        $this->assertSame(3, $left);
        $this->assertSame(6, $right);
        $this->assertSame(2, $level);

        $left = $woman2->getLeft();
        $right = $woman2->getRight();
        $level = $woman2->getLevel();
        $this->assertSame(4, $left);
        $this->assertSame(5, $right);
        $this->assertSame(3, $level);
    }
}
