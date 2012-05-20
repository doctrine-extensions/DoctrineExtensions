<?php

namespace Gedmo\Tree;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Tree\Fixture\RootCategory;

/**
 * These are tests for Tree behavior
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Tree
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class MaterializedPathORMRepositoryTest extends BaseTestCaseORM
{
    const CATEGORY = "Tree\\Fixture\\MPCategory";

    protected function setUp()
    {
        parent::setUp();

        $this->listener = new TreeListener;

        $evm = new EventManager;
        $evm->addEventSubscriber($this->listener);

        $this->getMockSqliteEntityManager($evm);

        $meta = $this->em->getClassMetadata(self::CATEGORY);
        $this->config = $this->listener->getConfiguration($this->em, $meta->name);
        $this->populate();
    }

    /**
     * @test
     */
    function getRootNodes()
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $result = $repo->getRootNodes('title');
        
        $this->assertCount(3, $result);
        $this->assertEquals('Drinks', $result[0]->getTitle());
        $this->assertEquals('Food', $result[1]->getTitle());
        $this->assertEquals('Sports', $result[2]->getTitle());
    }

    /**
     * @test
     */
    function getChildren()
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $root = $repo->findOneByTitle('Food');

        // Get all children from the root, NOT including it
        $result = $repo->getChildren($root, false, 'title');

        $this->assertCount(4, $result);
        $this->assertEquals('Carrots', $result[0]->getTitle());
        $this->assertEquals('Fruits', $result[1]->getTitle());
        $this->assertEquals('Potatoes', $result[2]->getTitle());
        $this->assertEquals('Vegitables', $result[3]->getTitle());

        // Get all children from the root, including it
        $result = $repo->getChildren($root, false, 'title', 'asc', true);

        $this->assertCount(5, $result);
        $this->assertEquals('Carrots', $result[0]->getTitle());
        $this->assertEquals('Food', $result[1]->getTitle());
        $this->assertEquals('Fruits', $result[2]->getTitle());
        $this->assertEquals('Potatoes', $result[3]->getTitle());
        $this->assertEquals('Vegitables', $result[4]->getTitle());

        // Get direct children from the root, NOT including it
        $result = $repo->getChildren($root, true, 'title', 'asc');

        $this->assertCount(2, $result);
        $this->assertEquals('Fruits', $result[0]->getTitle());
        $this->assertEquals('Vegitables', $result[1]->getTitle());

        // Get direct children from the root, including it
        $result = $repo->getChildren($root, true, 'title', 'asc', true);

        $this->assertCount(3, $result);
        $this->assertEquals('Food', $result[0]->getTitle());
        $this->assertEquals('Fruits', $result[1]->getTitle());
        $this->assertEquals('Vegitables', $result[2]->getTitle());

        // Get ALL nodes
        $result = $repo->getChildren(null, false, 'title');

        $this->assertCount(9, $result);
        $this->assertEquals('Best Whisky', $result[0]->getTitle());
        $this->assertEquals('Carrots', $result[1]->getTitle());
        $this->assertEquals('Drinks', $result[2]->getTitle());
        $this->assertEquals('Food', $result[3]->getTitle());
        $this->assertEquals('Fruits', $result[4]->getTitle());
        $this->assertEquals('Potatoes', $result[5]->getTitle());
        $this->assertEquals('Sports', $result[6]->getTitle());
        $this->assertEquals('Vegitables', $result[7]->getTitle());
        $this->assertEquals('Whisky', $result[8]->getTitle());

        // Get ALL root nodes
        $result = $repo->getChildren(null, true, 'title');

        $this->assertCount(3, $result);
        $this->assertEquals('Drinks', $result[0]->getTitle());
        $this->assertEquals('Food', $result[1]->getTitle());
        $this->assertEquals('Sports', $result[2]->getTitle());
    }

    /**
     * @test
     */
    function getTree()
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $tree = $repo->getTree();

        $this->assertCount(9, $tree);
        $this->assertEquals('Drinks', $tree[0]->getTitle());
        $this->assertEquals('Whisky', $tree[1]->getTitle());
        $this->assertEquals('Best Whisky', $tree[2]->getTitle());
        $this->assertEquals('Food', $tree[3]->getTitle());
        $this->assertEquals('Fruits', $tree[4]->getTitle());
        $this->assertEquals('Vegitables', $tree[5]->getTitle());
        $this->assertEquals('Carrots', $tree[6]->getTitle());
        $this->assertEquals('Potatoes', $tree[7]->getTitle());
        $this->assertEquals('Sports', $tree[8]->getTitle());

        // Get tree from a specific root node
        $roots = $repo->getRootNodes();
        $tree = $repo->getTree($roots[0]);

        $this->assertCount(3, $tree);
        $this->assertEquals('Drinks', $tree[0]->getTitle());
        $this->assertEquals('Whisky', $tree[1]->getTitle());
        $this->assertEquals('Best Whisky', $tree[2]->getTitle());
    }

    public function testBuildTreeMethod()
    {
        /** @var $repo \Gedmo\Tree\Document\MongoDB\Repository\MaterializedPathRepository */
        $repo = $this->em->getRepository(self::CATEGORY);
        $tree = $repo->childrenHierarchy();

        $this->assertEquals('Drinks', $tree[0]['title']);
        $this->assertEquals('Whisky', $tree[0]['__children'][0]['title']);
        $this->assertEquals('Best Whisky', $tree[0]['__children'][0]['__children'][0]['title']);
        $vegitablesChildren = $tree[1]['__children'][1]['__children'];
        $this->assertEquals('Food', $tree[1]['title']);
        $this->assertEquals('Fruits', $tree[1]['__children'][0]['title']);
        $this->assertEquals('Vegitables', $tree[1]['__children'][1]['title']);
        $this->assertEquals('Carrots', $vegitablesChildren[0]['title']);
        $this->assertEquals('Potatoes', $vegitablesChildren[1]['title']);
        $this->assertEquals('Sports', $tree[2]['title']);

        // Tree of one specific root
        $roots = $repo->getRootNodes();
        $tree = $repo->childrenHierarchy($roots[0]);

        $this->assertEquals('Drinks', $tree[0]['title']);
        $this->assertEquals('Whisky', $tree[0]['__children'][0]['title']);
        $this->assertEquals('Best Whisky', $tree[0]['__children'][0]['__children'][0]['title']);
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::CATEGORY
        );
    }

    public function createCategory()
    {
        $class = self::CATEGORY;
        return new $class;
    }

    private function populate()
    {
        $root = $this->createCategory();
        $root->setTitle("Food");

        $root2 = $this->createCategory();
        $root2->setTitle("Sports");

        $child = $this->createCategory();
        $child->setTitle("Fruits");
        $child->setParent($root);

        $child2 = $this->createCategory();
        $child2->setTitle("Vegitables");
        $child2->setParent($root);

        $childsChild = $this->createCategory();
        $childsChild->setTitle("Carrots");
        $childsChild->setParent($child2);

        $potatoes = $this->createCategory();
        $potatoes->setTitle("Potatoes");
        $potatoes->setParent($child2);

        $drinks = $this->createCategory();
        $drinks->setTitle('Drinks');

        $whisky = $this->createCategory();
        $whisky->setTitle('Whisky');
        $whisky->setParent($drinks);

        $bestWhisky = $this->createCategory();
        $bestWhisky->setTitle('Best Whisky');
        $bestWhisky->setParent($whisky);

        $this->em->persist($root);
        $this->em->persist($root2);
        $this->em->persist($child);
        $this->em->persist($child2);
        $this->em->persist($childsChild);
        $this->em->persist($potatoes);
        $this->em->persist($drinks);
        $this->em->persist($whisky);
        $this->em->persist($bestWhisky);

        $this->em->flush();
    }
}
