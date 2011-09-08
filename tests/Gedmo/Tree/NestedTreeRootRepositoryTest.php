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
class NestedTreeRootRepositoryTest extends BaseTestCaseORM
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

    public function testChildrenHierarchyAsArray()
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $result = $repo->childrenHierarchy();
        $this->assertEquals(2, count($result));
        $this->assertTrue(isset($result[0]['children'][0]['children']));

        $vegies = $repo->findOneByTitle('Vegitables');
        $result = $repo->childrenHierarchy($vegies);
        $this->assertEquals(2, count($result));
        $this->assertEquals(0, count($result[0]['children']));
    }

    public function testChildrenHierarchyAsHtml()
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $food = $repo->findOneByTitle('Food');
        $result = $repo->childrenHierarchy($food, false, true);

        $this->assertTrue(is_string($result));
    }

    public function testRootRemoval()
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $this->populateMore();

        $food = $repo->findOneByTitle('Food');
        $repo->removeFromTree($food);
        $this->em->clear();

        $food = $repo->findOneByTitle('Food');
        $this->assertTrue(is_null($food));

        $node = $repo->findOneByTitle('Fruits');

        $this->assertEquals(1, $node->getLeft());
        $this->assertEquals(2, $node->getRight());
        $this->assertEquals(3, $node->getRoot());
        $this->assertTrue(is_null($node->getParent()));

        $node = $repo->findOneByTitle('Vegitables');

        $this->assertEquals(1, $node->getLeft());
        $this->assertEquals(10, $node->getRight());
        $this->assertEquals(4, $node->getRoot());
        $this->assertTrue(is_null($node->getParent()));
    }

    public function testRepository()
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $carrots = $repo->findOneByTitle('Carrots');

        $path = $repo->getPath($carrots);
        $this->assertEquals(3, count($path));
        $this->assertEquals('Food', $path[0]->getTitle());
        $this->assertEquals('Vegitables', $path[1]->getTitle());
        $this->assertEquals('Carrots', $path[2]->getTitle());

        $vegies = $repo->findOneByTitle('Vegitables');
        $childCount = $repo->childCount($vegies);
        $this->assertEquals(2, $childCount);

        $food = $repo->findOneByTitle('Food');
        $childCount = $repo->childCount($food, true);
        $this->assertEquals(2, $childCount);

        $childCount = $repo->childCount($food);
        $this->assertEquals(4, $childCount);

        $childCount = $repo->childCount();
        $this->assertEquals(6, $childCount);
    }

    public function testAdvancedRepositoryFunctions()
    {
        $this->populateMore();
        $repo = $this->em->getRepository(self::CATEGORY);

        // verification

        $this->assertTrue($repo->verify());

        $dql = 'UPDATE ' . self::CATEGORY . ' node';
        $dql .= ' SET node.lft = 1';
        $dql .= ' WHERE node.id = 4';
        $this->em->createQuery($dql)->getSingleScalarResult();

        $this->em->clear(); // must clear cached entities
        $errors = $repo->verify();
        $this->assertEquals(2, count($errors));
        $this->assertEquals('index [1], duplicate on tree root: 1', $errors[0]);
        $this->assertEquals('index [4], missing on tree root: 1', $errors[1]);

        $dql = 'UPDATE ' . self::CATEGORY . ' node';
        $dql .= ' SET node.lft = 4';
        $dql .= ' WHERE node.id = 4';
        $this->em->createQuery($dql)->getSingleScalarResult();

        //@todo implement
        //$this->em->clear();
        //$repo->recover();
        //$this->em->clear();
        //$this->assertTrue($repo->verify());

        $this->em->clear();
        $onions = $repo->findOneByTitle('Onions');

        $this->assertEquals(11, $onions->getLeft());
        $this->assertEquals(12, $onions->getRight());

        // move up

        $repo->moveUp($onions);

        $this->assertEquals(9, $onions->getLeft());
        $this->assertEquals(10, $onions->getRight());

        $repo->moveUp($onions, true);

        $this->assertEquals(5, $onions->getLeft());
        $this->assertEquals(6, $onions->getRight());

        // move down

        $repo->moveDown($onions, 2);

        $this->assertEquals(9, $onions->getLeft());
        $this->assertEquals(10, $onions->getRight());

        // reorder

        $node = $repo->findOneByTitle('Food');
        $repo->reorder($node, 'title', 'ASC', false);

        $node = $repo->findOneByTitle('Cabbages');

        $this->assertEquals(5, $node->getLeft());
        $this->assertEquals(6, $node->getRight());

        $node = $repo->findOneByTitle('Carrots');

        $this->assertEquals(7, $node->getLeft());
        $this->assertEquals(8, $node->getRight());

        $node = $repo->findOneByTitle('Onions');

        $this->assertEquals(9, $node->getLeft());
        $this->assertEquals(10, $node->getRight());

        $node = $repo->findOneByTitle('Potatoes');

        $this->assertEquals(11, $node->getLeft());
        $this->assertEquals(12, $node->getRight());

        // leafs

        $leafs = $repo->getLeafs($node);
        $this->assertEquals(5, count($leafs));
        $this->assertEquals('Fruits', $leafs[0]->getTitle());
        $this->assertEquals('Cabbages', $leafs[1]->getTitle());
        $this->assertEquals('Carrots', $leafs[2]->getTitle());
        $this->assertEquals('Onions', $leafs[3]->getTitle());
        $this->assertEquals('Potatoes', $leafs[4]->getTitle());

        // remove

        $node = $repo->findOneByTitle('Fruits');
        $id = $node->getId();
        $repo->removeFromTree($node);

        $this->assertTrue(is_null($repo->find($id)));

        $node = $repo->findOneByTitle('Vegitables');
        $id = $node->getId();
        $repo->removeFromTree($node);

        $this->assertTrue(is_null($repo->find($id)));
        $this->em->clear();

        $node = $repo->findOneByTitle('Cabbages');

        $this->assertEquals(1, $node->getRoot());
        $this->assertEquals(1, $node->getParent()->getId());
    }

    public function testRemoveFromTreeLeaf()
    {
        $this->populateMore();
        $repo = $this->em->getRepository(self::CATEGORY);
        $onions = $repo->findOneByTitle('Onions');
        $id = $onions->getId();
        $repo->removeFromTree($onions);

        $this->assertTrue(is_null($repo->find($id)));
        $this->em->clear();

        $vegies = $repo->findOneByTitle('Vegitables');
        $this->assertTrue($repo->verify());
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::CATEGORY
        );
    }

    private function populateMore()
    {
        $vegies = $this->em->getRepository(self::CATEGORY)
            ->findOneByTitle('Vegitables');

        $cabbages = new RootCategory();
        $cabbages->setParent($vegies);
        $cabbages->setTitle('Cabbages');

        $onions = new RootCategory();
        $onions->setParent($vegies);
        $onions->setTitle('Onions');

        $this->em->persist($cabbages);
        $this->em->persist($onions);
        $this->em->flush();
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
    }
}
