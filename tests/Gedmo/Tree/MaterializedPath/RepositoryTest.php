<?php

namespace Gedmo\Tree\MaterializedPath;

use Doctrine\Common\EventManager;
use Gedmo\TestTool\ObjectManagerTestCase;
use Gedmo\Tree\TreeListener;

class RepositoryTest extends ObjectManagerTestCase
{
    const CATEGORY = "Gedmo\Fixture\Tree\MaterializedPath\MPCategory";

    private $repo, $config, $em, $listener;

    protected function setUp()
    {
        $evm = new EventManager;
        $evm->addEventSubscriber($this->listener = new TreeListener);

        $this->em = $this->createEntityManager($evm);
        $this->createSchema($this->em, array(
            self::CATEGORY,
        ));
        $meta = $this->em->getClassMetadata(self::CATEGORY);
        $this->config = $this->listener->getConfiguration($this->em, $meta->name)->getMapping();
        $this->repo = $this->em->getRepository(self::CATEGORY);

        $this->populate();
    }

    protected function tearDown()
    {
        $this->releaseEntityManager($this->em);
    }

    /**
     * @test
     */
    function getRootNodes()
    {
        $result = $this->repo->getRootNodes('title');

        $this->assertCount(3, $result);
        $this->assertSame('Drinks', $result[0]->getTitle());
        $this->assertSame('Food', $result[1]->getTitle());
        $this->assertSame('Sports', $result[2]->getTitle());
    }

    /**
     * @test
     */
    function getChildren()
    {
        $root = $this->repo->findOneByTitle('Food');

        // Get all children from the root, NOT including it
        $result = $this->repo->getChildren($root, false, 'title');

        $this->assertCount(4, $result);
        $this->assertSame('Carrots', $result[0]->getTitle());
        $this->assertSame('Fruits', $result[1]->getTitle());
        $this->assertSame('Potatoes', $result[2]->getTitle());
        $this->assertSame('Vegitables', $result[3]->getTitle());

        // Get all children from the root, including it
        $result = $this->repo->getChildren($root, false, 'title', 'asc', true);

        $this->assertCount(5, $result);
        $this->assertSame('Carrots', $result[0]->getTitle());
        $this->assertSame('Food', $result[1]->getTitle());
        $this->assertSame('Fruits', $result[2]->getTitle());
        $this->assertSame('Potatoes', $result[3]->getTitle());
        $this->assertSame('Vegitables', $result[4]->getTitle());

        // Get direct children from the root, NOT including it
        $result = $this->repo->getChildren($root, true, 'title', 'asc');

        $this->assertCount(2, $result);
        $this->assertSame('Fruits', $result[0]->getTitle());
        $this->assertSame('Vegitables', $result[1]->getTitle());

        // Get direct children from the root, including it
        $result = $this->repo->getChildren($root, true, 'title', 'asc', true);

        $this->assertCount(3, $result);
        $this->assertSame('Food', $result[0]->getTitle());
        $this->assertSame('Fruits', $result[1]->getTitle());
        $this->assertSame('Vegitables', $result[2]->getTitle());

        // Get ALL nodes
        $result = $this->repo->getChildren(null, false, 'title');

        $this->assertCount(9, $result);
        $this->assertSame('Best Whisky', $result[0]->getTitle());
        $this->assertSame('Carrots', $result[1]->getTitle());
        $this->assertSame('Drinks', $result[2]->getTitle());
        $this->assertSame('Food', $result[3]->getTitle());
        $this->assertSame('Fruits', $result[4]->getTitle());
        $this->assertSame('Potatoes', $result[5]->getTitle());
        $this->assertSame('Sports', $result[6]->getTitle());
        $this->assertSame('Vegitables', $result[7]->getTitle());
        $this->assertSame('Whisky', $result[8]->getTitle());

        // Get ALL root nodes
        $result = $this->repo->getChildren(null, true, 'title');

        $this->assertCount(3, $result);
        $this->assertSame('Drinks', $result[0]->getTitle());
        $this->assertSame('Food', $result[1]->getTitle());
        $this->assertSame('Sports', $result[2]->getTitle());
    }

    /**
     * @test
     */
    function getTree()
    {
        $tree = $this->repo->getTree();

        $this->assertCount(9, $tree);
        $this->assertSame('Drinks', $tree[0]->getTitle());
        $this->assertSame('Whisky', $tree[1]->getTitle());
        $this->assertSame('Best Whisky', $tree[2]->getTitle());
        $this->assertSame('Food', $tree[3]->getTitle());
        $this->assertSame('Fruits', $tree[4]->getTitle());
        $this->assertSame('Vegitables', $tree[5]->getTitle());
        $this->assertSame('Carrots', $tree[6]->getTitle());
        $this->assertSame('Potatoes', $tree[7]->getTitle());
        $this->assertSame('Sports', $tree[8]->getTitle());

        // Get tree from a specific root node
        $roots = $this->repo->getRootNodes();
        $tree = $this->repo->getTree($roots[0]);

        $this->assertCount(3, $tree);
        $this->assertSame('Drinks', $tree[0]->getTitle());
        $this->assertSame('Whisky', $tree[1]->getTitle());
        $this->assertSame('Best Whisky', $tree[2]->getTitle());
    }

    /**
     * @test
     */
    function shouldGenerateChildrenHierarchy()
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

        // Tree of one specific root, without the root node
        $roots = $this->repo->getRootNodes();
        $tree = $this->repo->childrenHierarchy($roots[0]);

        $this->assertSame('Whisky', $tree[0]['title']);
        $this->assertSame('Best Whisky', $tree[0]['__children'][0]['title']);

        // Tree of one specific root, with the root node
        $tree = $this->repo->childrenHierarchy($roots[0], false, array(), true);

        $this->assertSame('Drinks', $tree[0]['title']);
        $this->assertSame('Whisky', $tree[0]['__children'][0]['title']);
        $this->assertSame('Best Whisky', $tree[0]['__children'][0]['__children'][0]['title']);

        // Tree of one specific root only with direct children, without the root node
        $roots = $this->repo->getRootNodes();
        $tree = $this->repo->childrenHierarchy($roots[1], true);

        $this->assertSame(2, count($tree));
        $this->assertSame('Fruits', $tree[0]['title']);
        $this->assertSame('Vegitables', $tree[1]['title']);

        // Tree of one specific root only with direct children, with the root node
        $tree = $this->repo->childrenHierarchy($roots[1], true, array(), true);

        $this->assertSame(1, count($tree));
        $this->assertSame(2, count($tree[0]['__children']));
        $this->assertSame('Food', $tree[0]['title']);
        $this->assertSame('Fruits', $tree[0]['__children'][0]['title']);
        $this->assertSame('Vegitables', $tree[0]['__children'][1]['title']);

        // HTML Tree of one specific root, without the root node
        $roots = $this->repo->getRootNodes();
        $tree = $this->repo->childrenHierarchy($roots[0], false, array('decorate' => true), false);

        $this->assertSame('<ul><li>Whisky<ul><li>Best Whisky</li></ul></li></ul>', $tree);


        // HTML Tree of one specific root, with the root node
        $roots = $this->repo->getRootNodes();
        $tree = $this->repo->childrenHierarchy($roots[0], false, array('decorate' => true), true);

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
    function shouldFixIssue458()
    {
        $this->em->clear();

        $node = $this->repo->findOneByTitle('Fruits');
        $newNode = $this->createCategory();
        $parent = $node->getParent();

        $this->assertFalse($parent->__isInitialized());

        $newNode->setTitle('New Node');
        $newNode->setParent($parent);

        $this->em->persist($newNode);
        $this->em->flush();

        $this->assertRegexp('/Food\-\d+,New\sNode\-\d+/', $newNode->getPath());
        $this->assertSame(1, $newNode->getLevel());
    }

    /**
     * @test
     */
    function shouldChangeChildrenIndex()
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
