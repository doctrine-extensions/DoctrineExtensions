<?php

namespace Gedmo\Tree;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Doctrine\Common\Util\Debug;
use Tree\Fixture\Category;

/**
 * These are tests for Tree behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Tree
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TreeTest extends BaseTestCaseORM
{
    const CATEGORY = "Tree\\Fixture\\Category";

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $evm->addEventSubscriber(new TreeListener);

        $this->getMockSqliteEntityManager($evm);
    }

    public function testTheTree()
    {
        $meta = $this->em->getClassMetadata(self::CATEGORY);

        $root = new Category();
        $root->setTitle("Root");
        $this->assertTrue($root instanceof Node);

        $this->em->persist($root);
        $this->em->flush();
        $this->em->clear();

        $root = $this->em->getRepository(self::CATEGORY)->find(1);
        $left = $meta->getReflectionProperty('lft')->getValue($root);
        $right = $meta->getReflectionProperty('rgt')->getValue($root);

        $this->assertEquals($left, 1);
        $this->assertEquals($right, 2);

        $child = new Category();
        $child->setTitle("child");
        $child->setParent($root);

        $this->em->persist($child);
        $this->em->flush();
        $this->em->clear();

        $root = $this->em->getRepository(self::CATEGORY)->find(1);
        $left = $meta->getReflectionProperty('lft')->getValue($root);
        $right = $meta->getReflectionProperty('rgt')->getValue($root);
        $level = $meta->getReflectionProperty('level')->getValue($root);

        $this->assertEquals($left, 1);
        $this->assertEquals($right, 4);
        $this->assertEquals($level, 0);

        $child = $this->em->getRepository(self::CATEGORY)->find(2);
        $left = $meta->getReflectionProperty('lft')->getValue($child);
        $right = $meta->getReflectionProperty('rgt')->getValue($child);
        $level = $meta->getReflectionProperty('level')->getValue($child);

        $this->assertEquals($left, 2);
        $this->assertEquals($right, 3);
        $this->assertEquals($level, 1);

        $child2 = new Category();
        $child2->setTitle("child2");
        $child2->setParent($root);

        $this->em->persist($child2);
        $this->em->flush();
        $this->em->clear();

        $root = $this->em->getRepository(self::CATEGORY)->find(1);
        $left = $meta->getReflectionProperty('lft')->getValue($root);
        $right = $meta->getReflectionProperty('rgt')->getValue($root);
        $level = $meta->getReflectionProperty('level')->getValue($root);

        $this->assertEquals($left, 1);
        $this->assertEquals($right, 6);
        $this->assertEquals($level, 0);

        $child2 = $this->em->getRepository(self::CATEGORY)->find(3);
        $left = $meta->getReflectionProperty('lft')->getValue($child2);
        $right = $meta->getReflectionProperty('rgt')->getValue($child2);
        $level = $meta->getReflectionProperty('level')->getValue($child2);

        $this->assertEquals($left, 4);
        $this->assertEquals($right, 5);
        $this->assertEquals($level, 1);

        $childsChild = new Category();
        $childsChild->setTitle("childs2_child");
        $childsChild->setParent($child2);

        $this->em->persist($childsChild);
        $this->em->flush();
        $this->em->clear();

        $child2 = $this->em->getRepository(self::CATEGORY)->find(3);
        $left = $meta->getReflectionProperty('lft')->getValue($child2);
        $right = $meta->getReflectionProperty('rgt')->getValue($child2);
        $level = $meta->getReflectionProperty('level')->getValue($child2);

        $this->assertEquals($left, 4);
        $this->assertEquals($right, 7);
        $this->assertEquals($level, 1);

        $level = $meta->getReflectionProperty('level')->getValue($childsChild);

        $this->assertEquals($level, 2);

        // test updates to nodes, parent changes

        $childsChild = $this->em->getRepository(self::CATEGORY)->find(4);
        $child = $this->em->getRepository(self::CATEGORY)->find(2);
        $childsChild->setTitle('childs_child');
        $childsChild->setParent($child);

        $this->em->persist($childsChild);
        $this->em->flush();
        $this->em->clear();

        $child = $this->em->getRepository(self::CATEGORY)->find(2);
        $left = $meta->getReflectionProperty('lft')->getValue($child);
        $right = $meta->getReflectionProperty('rgt')->getValue($child);
        $level = $meta->getReflectionProperty('level')->getValue($child);

        $this->assertEquals($left, 2);
        $this->assertEquals($right, 5);
        $this->assertEquals($level, 1);

        // test deletion

        $this->em->remove($child);
        $this->em->flush();
        $this->em->clear();

        $root = $this->em->getRepository(self::CATEGORY)->find(1);
        $left = $meta->getReflectionProperty('lft')->getValue($root);
        $right = $meta->getReflectionProperty('rgt')->getValue($root);

        $this->assertEquals($left, 1);
        $this->assertEquals($right, 4);
        
        // test persisting in any time
        $yetAnotherChild = new Category();
        $this->em->persist($yetAnotherChild);
        $yetAnotherChild->setTitle("yetanotherchild");
        $yetAnotherChild->setParent($root);
        $this->em->flush();
        $this->em->clear();

        $left = $meta->getReflectionProperty('lft')->getValue($yetAnotherChild);
        $right = $meta->getReflectionProperty('rgt')->getValue($yetAnotherChild);
        $level = $meta->getReflectionProperty('level')->getValue($yetAnotherChild);

        $this->assertEquals($left, 4);
        $this->assertEquals($right, 5);
        $this->assertEquals($level, 1);
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::CATEGORY
        );
    }
}
