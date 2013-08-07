<?php

namespace Gedmo\Tree\MaterializedPath\Document;

use Doctrine\Common\EventManager;
use Gedmo\TestTool\ObjectManagerTestCase;
use Gedmo\Tree\TreeListener;

class RepositoryTest extends ObjectManagerTestCase
{
    const CATEGORY = "Gedmo\Fixture\Tree\MaterializedPath\Document\Category";

    private $repo, $dm, $listener;

    protected function setUp()
    {
        $evm = new EventManager;
        $evm->addEventSubscriber($this->listener = new TreeListener);

        $this->dm = $this->createDocumentManager($evm);

        $this->populate();
        $this->repo = $this->dm->getRepository(self::CATEGORY);
    }

    protected function tearDown()
    {
        $this->releaseDocumentManager($this->dm);
    }

    /**
     * @test
     */
    function getRootNodes()
    {
        $result = $this->repo->getRootNodes('title');

        $this->assertSame(3, $result->count());
        $this->assertSame('Drinks', $result->getNext()->getTitle());
        $this->assertSame('Food', $result->getNext()->getTitle());
        $this->assertSame('Sports', $result->getNext()->getTitle());
    }

    /**
     * @test
     */
    function getChildren()
    {
        $root = $this->repo->findOneByTitle('Food');

        // Get all children from the root, including it
        $result = $this->repo->getChildren($root, false, 'title', 'asc', true);

        $this->assertSame(5, count($result));
        $this->assertSame('Carrots', $result->getNext()->getTitle());
        $this->assertSame('Food', $result->getNext()->getTitle());
        $this->assertSame('Fruits', $result->getNext()->getTitle());
        $this->assertSame('Potatoes', $result->getNext()->getTitle());
        $this->assertSame('Vegitables', $result->getNext()->getTitle());

        // Get all children from the root, NOT including it
        $result = $this->repo->getChildren($root, false, 'title', 'asc', false);

        $this->assertSame(4, count($result));
        $this->assertSame('Carrots', $result->getNext()->getTitle());
        $this->assertSame('Fruits', $result->getNext()->getTitle());
        $this->assertSame('Potatoes', $result->getNext()->getTitle());
        $this->assertSame('Vegitables', $result->getNext()->getTitle());

        // Get direct children from the root, including it
        $result = $this->repo->getChildren($root, true, 'title', 'asc', true);

        $this->assertSame(3, $result->count());
        $this->assertSame('Food', $result->getNext()->getTitle());
        $this->assertSame('Fruits', $result->getNext()->getTitle());
        $this->assertSame('Vegitables', $result->getNext()->getTitle());

        // Get direct children from the root, NOT including it
        $result = $this->repo->getChildren($root, true, 'title', 'asc', false);

        $this->assertSame(2, $result->count());
        $this->assertSame('Fruits', $result->getNext()->getTitle());
        $this->assertSame('Vegitables', $result->getNext()->getTitle());

        // Get ALL nodes
        $result = $this->repo->getChildren(null, false, 'title');

        $this->assertSame(9, $result->count());
        $this->assertSame('Best Whisky', $result->getNext()->getTitle());
        $this->assertSame('Carrots', $result->getNext()->getTitle());
        $this->assertSame('Drinks', $result->getNext()->getTitle());
        $this->assertSame('Food', $result->getNext()->getTitle());
        $this->assertSame('Fruits', $result->getNext()->getTitle());
        $this->assertSame('Potatoes', $result->getNext()->getTitle());
        $this->assertSame('Sports', $result->getNext()->getTitle());
        $this->assertSame('Vegitables', $result->getNext()->getTitle());
        $this->assertSame('Whisky', $result->getNext()->getTitle());

        // Get ALL root nodes
        $result = $this->repo->getChildren(null, true, 'title');

        $this->assertSame(3, $result->count());
        $this->assertSame('Drinks', $result->getNext()->getTitle());
        $this->assertSame('Food', $result->getNext()->getTitle());
        $this->assertSame('Sports', $result->getNext()->getTitle());
    }

    /**
     * @test
     */
    function getTree()
    {
        $tree = $this->repo->getTree();

        $this->assertSame(9, $tree->count());
        $this->assertSame('Drinks', $tree->getNext()->getTitle());
        $this->assertSame('Whisky', $tree->getNext()->getTitle());
        $this->assertSame('Best Whisky', $tree->getNext()->getTitle());
        $this->assertSame('Food', $tree->getNext()->getTitle());
        $this->assertSame('Fruits', $tree->getNext()->getTitle());
        $this->assertSame('Vegitables', $tree->getNext()->getTitle());
        $this->assertSame('Carrots', $tree->getNext()->getTitle());
        $this->assertSame('Potatoes', $tree->getNext()->getTitle());
        $this->assertSame('Sports', $tree->getNext()->getTitle());

        // Get a specific tree
        $roots = $this->repo->getRootNodes();
        $tree = $this->repo->getTree($roots->getNext());

        $this->assertSame(3, $tree->count());
        $this->assertSame('Drinks', $tree->getNext()->getTitle());
        $this->assertSame('Whisky', $tree->getNext()->getTitle());
        $this->assertSame('Best Whisky', $tree->getNext()->getTitle());
    }

    /**
     * @test
     */
    function childrenHierarchy()
    {
        $tree = $this->repo->childrenHierarchy();

        $this->assertSame('Drinks', $tree[0]['title']);
        $this->assertSame('Whisky', $tree[0]['__children'][0]['title']);
        $this->assertSame('Best Whisky', $tree[0]['__children'][0]['__children'][0]['title']);
        $vegitablesChildren = $tree[1]['__children'][1]['__children'];
        $this->assertSame('Food', $tree[1]['title']);
        $this->assertSame('Fruits', $tree[1]['__children'][0]['title']);
        $this->assertSame('Vegitables', $tree[1]['__children'][1]['title']);
        $this->assertSame('Carrots', $vegitablesChildren[0]['title']);
        $this->assertSame('Potatoes', $vegitablesChildren[1]['title']);
        $this->assertSame('Sports', $tree[2]['title']);

        // Tree of one specific root
        $roots = $this->repo->getRootNodes();
        $drinks = $roots->getNext();
        $food = $roots->getNext();
        $tree = $this->repo->childrenHierarchy();

        $this->assertSame('Drinks', $tree[0]['title']);
        $this->assertSame('Whisky', $tree[0]['__children'][0]['title']);
        $this->assertSame('Best Whisky', $tree[0]['__children'][0]['__children'][0]['title']);

        // Tree of one specific root, with the root node
        $tree = $this->repo->childrenHierarchy($drinks, false, array(), true);

        $this->assertSame('Drinks', $tree[0]['title']);
        $this->assertSame('Whisky', $tree[0]['__children'][0]['title']);
        $this->assertSame('Best Whisky', $tree[0]['__children'][0]['__children'][0]['title']);

        // Tree of one specific root only with direct children, without the root node
        $roots = $this->repo->getRootNodes();
        $tree = $this->repo->childrenHierarchy($food, true);

        $this->assertSame(2, count($tree));
        $this->assertSame('Fruits', $tree[0]['title']);
        $this->assertSame('Vegitables', $tree[1]['title']);

        // Tree of one specific root only with direct children, with the root node
        $tree = $this->repo->childrenHierarchy($food, true, array(), true);

        $this->assertSame(1, count($tree));
        $this->assertSame(2, count($tree[0]['__children']));
        $this->assertSame('Food', $tree[0]['title']);
        $this->assertSame('Fruits', $tree[0]['__children'][0]['title']);
        $this->assertSame('Vegitables', $tree[0]['__children'][1]['title']);

        // HTML Tree of one specific root, without the root node
        $roots = $this->repo->getRootNodes();
        $tree = $this->repo->childrenHierarchy($drinks, false, array('decorate' => true), false);

        $this->assertSame('<ul><li>Whisky<ul><li>Best Whisky</li></ul></li></ul>', $tree);


        // HTML Tree of one specific root, with the root node
        $roots = $this->repo->getRootNodes();
        $tree = $this->repo->childrenHierarchy($drinks, false, array('decorate' => true), true);

        $this->assertSame('<ul><li>Drinks<ul><li>Whisky<ul><li>Best Whisky</li></ul></li></ul></li></ul>', $tree);
    }

    /**
     * @test
     */
    function shouldCountChildren()
    {
        // Count all
        $count = $this->repo->childCount();

        $this->assertSame(9, $count);

        // Count all, but only direct ones
        $count = $this->repo->childCount(null, true);

        $this->assertSame(3, $count);

        // Count food children
        $food = $this->repo->findOneByTitle('Food');
        $count = $this->repo->childCount($food);

        $this->assertSame(4, $count);

        // Count food children, but only direct ones
        $count = $this->repo->childCount($food, true);

        $this->assertSame(2, $count);
    }

    /**
     * @test
     * @expectedException Gedmo\Exception\InvalidArgumentException
     */
    function ifAnObjectIsPassedWhichIsNotAnInstanceOfTheEntityClassThrowException()
    {
        $this->repo->childCount(new \DateTime());
    }

    /**
     * @test
     * @expectedException Gedmo\Exception\InvalidArgumentException
     */
    function ifAnObjectIsPassedIsAnInstanceOfTheEntityClassButIsNotHandledByUnitOfWorkThrowException()
    {
        $this->repo->childCount($this->createCategory());
    }

    /**
     * @test
     */
    function changeChildrenIndex()
    {
        $childrenIndex = 'myChildren';
        $this->repo->setChildrenIndex($childrenIndex);

        $tree = $this->repo->childrenHierarchy();

        $this->assertInternalType('array', $tree[0][$childrenIndex]);
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

        $this->dm->persist($root);
        $this->dm->persist($root2);
        $this->dm->persist($child);
        $this->dm->persist($child2);
        $this->dm->persist($childsChild);
        $this->dm->persist($potatoes);
        $this->dm->persist($drinks);
        $this->dm->persist($whisky);
        $this->dm->persist($bestWhisky);

        $this->dm->flush();
    }
}
