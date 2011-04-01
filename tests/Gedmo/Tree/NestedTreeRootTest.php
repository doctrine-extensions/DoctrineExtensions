<?php

namespace Gedmo\Tree;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Doctrine\Common\Util\Debug;
use Tree\Fixture\RootCategory;

/**
 * These are tests for Tree behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Tree
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class NestedTreeRootTest extends BaseTestCaseORM
{
    const CATEGORY = "Tree\\Fixture\\RootCategory";

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $evm->addEventSubscriber(new TreeListener);

        $this->getMockSqliteEntityManager($evm);
        $this->populate();
    }

    public function testTheTree()
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $node = $repo->findOneByTitle('Food');

        $this->assertEquals(1, $node->getRoot());
        $this->assertEquals(1, $node->getLeft());
        $this->assertEquals(0, $node->getLevel());
        $this->assertEquals(10, $node->getRight());

        $node = $repo->findOneByTitle('Sports');

        $this->assertEquals(2, $node->getRoot());
        $this->assertEquals(1, $node->getLeft());
        $this->assertEquals(0, $node->getLevel());
        $this->assertEquals(2, $node->getRight());

        $node = $repo->findOneByTitle('Fruits');

        $this->assertEquals(1, $node->getRoot());
        $this->assertEquals(2, $node->getLeft());
        $this->assertEquals(1, $node->getLevel());
        $this->assertEquals(3, $node->getRight());

        $node = $repo->findOneByTitle('Vegitables');

        $this->assertEquals(1, $node->getRoot());
        $this->assertEquals(4, $node->getLeft());
        $this->assertEquals(1, $node->getLevel());
        $this->assertEquals(9, $node->getRight());

        $node = $repo->findOneByTitle('Carrots');

        $this->assertEquals(1, $node->getRoot());
        $this->assertEquals(5, $node->getLeft());
        $this->assertEquals(2, $node->getLevel());
        $this->assertEquals(6, $node->getRight());

        $node = $repo->findOneByTitle('Potatoes');

        $this->assertEquals(1, $node->getRoot());
        $this->assertEquals(7, $node->getLeft());
        $this->assertEquals(2, $node->getLevel());
        $this->assertEquals(8, $node->getRight());
    }

    public function testSetParentToNull()
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $node = $repo->findOneByTitle('Vegitables');
        $node->setParent(null);

        $this->em->persist($node);
        $this->em->flush();
        $this->em->clear();

        $node = $repo->findOneByTitle('Vegitables');
        $this->assertEquals(4, $node->getRoot());
        $this->assertEquals(1, $node->getLeft());
        $this->assertEquals(6, $node->getRight());
        $this->assertEquals(0, $node->getLevel());
    }

    public function testTreeUpdateShiftToNextBranch()
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $sport = $repo->findOneByTitle('Sports');
        $food = $repo->findOneByTitle('Food');

        $sport->setParent($food);
        $this->em->persist($sport);
        $this->em->flush();
        $this->em->clear();

        $node = $repo->findOneByTitle('Food');

        $this->assertEquals(1, $node->getLeft());
        $this->assertEquals(12, $node->getRight());

        $node = $repo->findOneByTitle('Sports');

        $this->assertEquals(1, $node->getRoot());
        $this->assertEquals(2, $node->getLeft());
        $this->assertEquals(1, $node->getLevel());
        $this->assertEquals(3, $node->getRight());

        $node = $repo->findOneByTitle('Vegitables');

        $this->assertEquals(6, $node->getLeft());
        $this->assertEquals(11, $node->getRight());
    }

    public function testTreeUpdateShiftToRoot()
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $vegies = $repo->findOneByTitle('Vegitables');

        $vegies->setParent(null);
        $this->em->persist($vegies);
        $this->em->flush();
        $this->em->clear();

        $node = $repo->findOneByTitle('Food');

        $this->assertEquals(1, $node->getLeft());
        $this->assertEquals(4, $node->getRight());

        $node = $repo->findOneByTitle('Vegitables');

        $this->assertEquals(4, $node->getRoot());
        $this->assertEquals(1, $node->getLeft());
        $this->assertEquals(0, $node->getLevel());
        $this->assertEquals(6, $node->getRight());

        $node = $repo->findOneByTitle('Potatoes');

        $this->assertEquals(4, $node->getRoot());
        $this->assertEquals(4, $node->getLeft());
        $this->assertEquals(1, $node->getLevel());
        $this->assertEquals(5, $node->getRight());
    }

    public function testTreeUpdateShiftToOtherParent()
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $carrots = $repo->findOneByTitle('Carrots');
        $food = $repo->findOneByTitle('Food');

        $carrots->setParent($food);
        $this->em->persist($carrots);
        $this->em->flush();
        $this->em->clear();

        $node = $repo->findOneByTitle('Food');

        $this->assertEquals(1, $node->getLeft());
        $this->assertEquals(10, $node->getRight());

        $node = $repo->findOneByTitle('Carrots');

        $this->assertEquals(1, $node->getRoot());
        $this->assertEquals(2, $node->getLeft());
        $this->assertEquals(1, $node->getLevel());
        $this->assertEquals(3, $node->getRight());

        $node = $repo->findOneByTitle('Potatoes');

        $this->assertEquals(1, $node->getRoot());
        $this->assertEquals(7, $node->getLeft());
        $this->assertEquals(2, $node->getLevel());
        $this->assertEquals(8, $node->getRight());
    }

    /**
     * @expectedException UnexpectedValueException
     */
    public function testTreeUpdateShiftToChildParent()
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $vegies = $repo->findOneByTitle('Vegitables');
        $food = $repo->findOneByTitle('Food');

        $food->setParent($vegies);
        $this->em->persist($food);
        $this->em->flush();
        $this->em->clear();
    }

    public function testTwoUpdateOperations()
    {
        $repo = $this->em->getRepository(self::CATEGORY);

        $sport = $repo->findOneByTitle('Sports');
        $food = $repo->findOneByTitle('Food');
        $sport->setParent($food);

        $vegies = $repo->findOneByTitle('Vegitables');
        $vegies->setParent(null);

        $this->em->persist($vegies);
        $this->em->persist($sport);
        $this->em->flush();
        $this->em->clear();

        $node = $repo->findOneByTitle('Carrots');

        $this->assertEquals(4, $node->getRoot());
        $this->assertEquals(2, $node->getLeft());
        $this->assertEquals(1, $node->getLevel());
        $this->assertEquals(3, $node->getRight());

        $node = $repo->findOneByTitle('Vegitables');

        $this->assertEquals(4, $node->getRoot());
        $this->assertEquals(1, $node->getLeft());
        $this->assertEquals(0, $node->getLevel());
        $this->assertEquals(6, $node->getRight());

        $node = $repo->findOneByTitle('Sports');

        $this->assertEquals(1, $node->getRoot());
        $this->assertEquals(2, $node->getLeft());
        $this->assertEquals(1, $node->getLevel());
        $this->assertEquals(3, $node->getRight());
    }

    public function testRemoval()
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $vegies = $repo->findOneByTitle('Vegitables');

        $this->em->remove($vegies);
        $this->em->flush();
        $this->em->clear();

        $node = $repo->findOneByTitle('Food');

        $this->assertEquals(1, $node->getLeft());
        $this->assertEquals(4, $node->getRight());
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::CATEGORY
        );
    }

    private function populate()
    {
        $root = new RootCategory();
        $root->setTitle("Food");

        $root2 = new RootCategory();
        $root2->setTitle("Sports");

        $child = new RootCategory();
        $child->setTitle("Fruits");
        $child->setParent($root);

        $child2 = new RootCategory();
        $child2->setTitle("Vegitables");
        $child2->setParent($root);

        $childsChild = new RootCategory();
        $childsChild->setTitle("Carrots");
        $childsChild->setParent($child2);

        $potatoes = new RootCategory();
        $potatoes->setTitle("Potatoes");
        $potatoes->setParent($child2);

        $this->em->persist($root);
        $this->em->persist($root2);
        $this->em->persist($child);
        $this->em->persist($child2);
        $this->em->persist($childsChild);
        $this->em->persist($potatoes);
        $this->em->flush();
        $this->em->clear();
    }
}
