<?php

namespace Tree\Closure;

use Doctrine\Common\EventManager;
use TestTool\ObjectManagerTestCase;
use Fixture\Tree\Closure\Category;
use Fixture\Tree\Closure\CategoryWithoutLevel;
use Fixture\Tree\Closure\CategoryWithoutLevelClosure;
use Gedmo\Tree\TreeListener;

class ClosureTreeRepositoryTest extends ObjectManagerTestCase
{
    const CATEGORY = "Fixture\Tree\Closure\Category";
    const CLOSURE = "Fixture\Tree\Closure\CategoryClosure";
    const CATEGORY_WITHOUT_LEVEL = "Fixture\Tree\Closure\CategoryWithoutLevel";
    const CATEGORY_WITHOUT_LEVEL_CLOSURE = "Fixture\Tree\Closure\CategoryWithoutLevelClosure";

    private $em, $listener;

    protected function setUp()
    {
        $evm = new EventManager;
        $evm->addEventSubscriber($this->listener = new TreeListener);

        $this->em = $this->createEntityManager($evm);
        $this->createSchema($this->em, array(
            self::CATEGORY,
            self::CLOSURE,
            self::CATEGORY_WITHOUT_LEVEL,
            self::CATEGORY_WITHOUT_LEVEL_CLOSURE
        ));
    }

    protected function tearDown()
    {
        $this->releaseEntityManager($this->em);
    }

    /**
     * @test
     */
    function shouldBeAbleToCountChildren()
    {
        $this->populate();

        $repo = $this->em->getRepository(self::CATEGORY);
        $food = $repo->findOneByTitle('Food');

        // Count all
        $count = $repo->childCount();
        $this->assertSame(15, $count);

        // Count all, but only direct ones
        $count = $repo->childCount(null, true);
        $this->assertSame(2, $count);

        // Count food children
        $food = $repo->findOneByTitle('Food');
        $count = $repo->childCount($food);
        $this->assertSame(11, $count);

        // Count food children, but only direct ones
        $food = $repo->findOneByTitle('Food');
        $count = $repo->childCount($food, true);
        $this->assertSame(3, $count);
    }

    /**
     * @test
     */
    function shouldBeAbleToGetNodePath()
    {
        $this->populate();

        $repo = $this->em->getRepository(self::CATEGORY);
        $fruits = $repo->findOneByTitle('Fruits');

        $path = $repo->getPath($fruits);
        $this->assertCount(2, $path);
        $this->assertSame('Food', $path[0]->getTitle());
        $this->assertSame('Fruits', $path[1]->getTitle());

        $strawberries = $repo->findOneByTitle('Strawberries');
        $path = $repo->getPath($strawberries);
        $this->assertCount(4, $path);
        $this->assertSame('Food', $path[0]->getTitle());
        $this->assertSame('Fruits', $path[1]->getTitle());
        $this->assertSame('Berries', $path[2]->getTitle());
        $this->assertSame('Strawberries', $path[3]->getTitle());
    }

    /**
     * @test
     */
    function shouldBeAbleToGetChildren()
    {
        $this->populate();

        $repo = $this->em->getRepository(self::CATEGORY);
        $fruits = $repo->findOneByTitle('Fruits');

        // direct children of node, sorted by title ascending order. NOT including the root node
        $children = $repo->children($fruits, true, 'title');
        $this->assertCount(3, $children);
        $this->assertSame('Berries', $children[0]->getTitle());
        $this->assertSame('Lemons', $children[1]->getTitle());
        $this->assertSame('Oranges', $children[2]->getTitle());

        // direct children of node, sorted by title ascending order. including the root node
        $children = $repo->children($fruits, true, 'title', 'asc', true);
        $this->assertCount(4, $children);
        $this->assertSame('Berries', $children[0]->getTitle());
        $this->assertSame('Fruits', $children[1]->getTitle());
        $this->assertSame('Lemons', $children[2]->getTitle());
        $this->assertSame('Oranges', $children[3]->getTitle());

        // all children of node, NOT including the root
        $children = $repo->children($fruits);
        $this->assertCount(4, $children);
        $this->assertSame('Oranges', $children[0]->getTitle());
        $this->assertSame('Lemons', $children[1]->getTitle());
        $this->assertSame('Berries', $children[2]->getTitle());
        $this->assertSame('Strawberries', $children[3]->getTitle());

        // all children of node, including the root
        $children = $repo->children($fruits, false, 'title', 'asc', true);
        $this->assertCount(5, $children);
        $this->assertSame('Berries', $children[0]->getTitle());
        $this->assertSame('Fruits', $children[1]->getTitle());
        $this->assertSame('Lemons', $children[2]->getTitle());
        $this->assertSame('Oranges', $children[3]->getTitle());
        $this->assertSame('Strawberries', $children[4]->getTitle());

        // direct root nodes
        $children = $repo->children(null, true, 'title');
        $this->assertCount(2, $children);
        $this->assertSame('Food', $children[0]->getTitle());
        $this->assertSame('Sports', $children[1]->getTitle());

        // all tree
        $children = $repo->children();
        $this->assertCount(15, $children);
    }

    /**
     * @test
     */
    function shouldBeAbleToRemoveSingleNodeFromTree()
    {
        $this->populate();

        $repo = $this->em->getRepository(self::CATEGORY);
        $fruits = $repo->findOneByTitle('Fruits');

        $repo->removeFromTree($fruits);
        // ensure in memory node integrity
        $this->em->flush();

        $food = $repo->findOneByTitle('Food');
        $children = $repo->children($food, true);
        $this->assertCount(5, $children);

        $berries = $repo->findOneByTitle('Berries');
        $this->assertSame(1, $repo->childCount($berries, true));

        $lemons = $repo->findOneByTitle('Lemons');
        $this->assertSame(0, $repo->childCount($lemons, true));

        $repo->removeFromTree($food);

        $vegitables = $repo->findOneByTitle('Vegitables');
        $this->assertSame(2, $repo->childCount($vegitables, true));
        $this->assertNull($vegitables->getParent());

        $repo->removeFromTree($lemons);
        $this->assertCount(5, $repo->children(null, true));
    }

    /**
     * @test
     */
    function shouldBuildTreeWithLevelProperty()
    {
        $this->populate();
        $this->buildTreeTests(self::CATEGORY);
    }

    /**
     * @test
     */
    function shouldBuildTreeWithoutLevelProperty()
    {
        $this->populate(self::CATEGORY_WITHOUT_LEVEL);
        $this->buildTreeTests(self::CATEGORY_WITHOUT_LEVEL);
    }

    /**
     * @test
     */
    function shouldAvoidSubqueryInSelectHavingLevelPropertyInGetNodesHierarchy()
    {
        $this->populate();

        $repo = $this->em->getRepository(self::CATEGORY);
        $roots = $repo->getRootNodes();
        $meta = $this->em->getClassMetadata(self::CATEGORY);
        $config = $this->listener->getConfiguration($this->em, $meta->name);
        $qb = $repo->getNodesHierarchyQueryBuilder($roots[0], false, $config->getMapping());

        $this->assertFalse(strpos($qb->getQuery()->getDql(), '(SELECT MAX('));
    }

    /**
     * @test
     */
    function shouldUseASubqueryInSelectNotHavingLevelPropertyInGetNodesHierarchy()
    {
        $this->populate(self::CATEGORY_WITHOUT_LEVEL);

        $repo = $this->em->getRepository(self::CATEGORY_WITHOUT_LEVEL);
        $roots = $repo->getRootNodes();
        $meta = $this->em->getClassMetadata(self::CATEGORY_WITHOUT_LEVEL);
        $config = $this->listener->getConfiguration($this->em, $meta->name);
        $qb = $repo->getNodesHierarchyQueryBuilder($roots[0], false, $config->getMapping());

        $this->assertTrue(((bool) strpos($qb->getQuery()->getDql(), '(SELECT MAX(')));
    }

    /**
     * @test
     */
    function shouldChangeChildrenIndex()
    {
        $this->populate(self::CATEGORY);

        $childrenIndex = 'myChildren';
        $repo = $this->em->getRepository(self::CATEGORY);
        $repo->setChildrenIndex($childrenIndex);

        $tree = $repo->childrenHierarchy();
        $this->assertInternalType('array', $tree[0][$childrenIndex]);
    }

    // Utility Methods

    protected function buildTreeTests($class)
    {
        $repo = $this->em->getRepository($class);
        $sortOption =  array('childSort' => array('field' => 'title', 'dir' => 'asc'));

        $testClosure = function(ClosureTreeRepositoryTest $phpUnit, array $tree, $includeNode = false, $whichTree = 'both', $includeNewNode = false) {
            if ($whichTree === 'both' || $whichTree === 'first') {
                $boringFood = $includeNewNode ? ($includeNode ? $tree[0]['__children'][0] : $tree[0]) : null;
                $fruitsIndex = $includeNewNode ? 1 : 0;
                $milkIndex = $includeNewNode ? 2 : 1;
                $fruits = $includeNode ? $tree[0]['__children'][$fruitsIndex] : $tree[$fruitsIndex];
                $milk = $includeNode ? $tree[0]['__children'][$milkIndex] : $tree[$milkIndex];
                $vegitables = $includeNewNode ? $boringFood['__children'][0] : ($includeNode ? $tree[0]['__children'][2] : $tree[2]);

                if ($includeNode) {
                    $phpUnit->assertSame('Food', $tree[0]['title']);
                }

                $phpUnit->assertSame('Fruits', $fruits['title']);
                $phpUnit->assertSame('Berries', $fruits['__children'][0]['title']);
                $phpUnit->assertSame('Strawberries', $fruits['__children'][0]['__children'][0]['title']);
                $phpUnit->assertSame('Milk', $milk['title']);
                $phpUnit->assertSame('Cheese', $milk['__children'][0]['title']);
                $phpUnit->assertSame('Mould cheese', $milk['__children'][0]['__children'][0]['title']);

                if ($boringFood) {
                    $phpUnit->assertSame('Boring Food', $boringFood['title']);
                }

                $phpUnit->assertSame('Vegitables', $vegitables['title']);
                $phpUnit->assertSame('Cabbages', $vegitables['__children'][0]['title']);
                $phpUnit->assertSame('Carrots', $vegitables['__children'][1]['title']);
            }

            if ($whichTree === 'both' || $whichTree === 'second') {
                $root = $whichTree === 'both' ? $tree[1] : $tree[0];
                $soccer = $includeNode ? $root['__children'][0] : $root;

                if ($includeNode) {
                    $phpUnit->assertSame('Sports', $root['title']);
                }

                $phpUnit->assertSame('Soccer', $soccer['title']);
                $phpUnit->assertSame('Indoor Soccer', $soccer['__children'][0]['title']);
            }
        };

        // All trees
        $tree = $repo->childrenHierarchy(null, false, $sortOption);

        $testClosure($this, $tree, true, 'both');

        $roots = $repo->getRootNodes();

        // First root tree, including root node
        $tree = $repo->childrenHierarchy(
            $roots[0],
            false,
            $sortOption,
            true
        );

        $testClosure($this, $tree, true, 'first');

        // First root tree, not including root node
        $tree = $repo->childrenHierarchy(
            $roots[0],
            false,
            $sortOption
        );

        $testClosure($this, $tree, false, 'first');

        // Second root tree, including root node
        $tree = $repo->childrenHierarchy(
            $roots[1],
            false,
            $sortOption,
            true
        );

        $testClosure($this, $tree, true, 'second');

        // Second root tree, not including root node
        $tree = $repo->childrenHierarchy(
            $roots[1],
            false,
            $sortOption
        );

        $testClosure($this, $tree, false, 'second');

        $food = $repo->findOneByTitle('Food');
        $vegitables = $repo->findOneByTitle('Vegitables');

        $boringFood = new $class();
        $boringFood->setTitle('Boring Food');
        $boringFood->setParent($food);
        $vegitables->setParent($boringFood);

        $this->em->persist($boringFood);

        $this->em->flush();

        // First root tree, after inserting a new node in the middle. This includes the root node
        $tree = $repo->childrenHierarchy(
            $roots[0],
            false,
            $sortOption,
            true
        );

        $testClosure($this, $tree, true, 'first', true);

        // First root tree, after inserting a new node in the middle. This not includes the root node
        $tree = $repo->childrenHierarchy(
            $roots[0],
            false,
            $sortOption
        );

        $testClosure($this, $tree, false, 'first', true);

        // Second root tree, after inserting a new node in the middle. This includes the root node
        $tree = $repo->childrenHierarchy(
            $roots[1],
            false,
            $sortOption,
            true
        );

        $testClosure($this, $tree, true, 'second', true);

        // Second root tree, after inserting a new node in the middle. This not includes the root node
        $tree = $repo->childrenHierarchy(
            $roots[1],
            false,
            $sortOption
        );

        $testClosure($this, $tree, false, 'second', false);

        // Test a subtree, including node
        $node = $repo->findOneByTitle('Fruits');
        $tree = $repo->childrenHierarchy(
            $node,
            false,
            $sortOption,
            true
        );


        $this->assertSame('Fruits', $tree[0]['title']);
        $this->assertSame('Berries', $tree[0]['__children'][0]['title']);
        $this->assertSame('Strawberries', $tree[0]['__children'][0]['__children'][0]['title']);

        $node = $repo->findOneByTitle('Fruits');
        $tree = $repo->childrenHierarchy(
            $node,
            false,
            $sortOption
        );

        $this->assertSame('Berries', $tree[0]['title']);
        $this->assertSame('Strawberries', $tree[0]['__children'][0]['title']);

        // First Tree Direct Nodes, including root node
        $tree = $repo->childrenHierarchy(
            $roots[0],
            true,
            $sortOption,
            true
        );

        $food = $tree[0];
        $this->assertSame('Food', $food['title']);
        $this->assertSame(3, count($food['__children']));
        $this->assertSame('Boring Food', $food['__children'][0]['title']);
        $this->assertSame('Fruits', $food['__children'][1]['title']);
        $this->assertSame('Milk', $food['__children'][2]['title']);

        // First Tree Direct Nodes, not including root node
        $tree = $repo->childrenHierarchy(
            $roots[0],
            true,
            $sortOption
        );

        $this->assertSame(3, count($tree));
        $this->assertSame('Boring Food', $tree[0]['title']);
        $this->assertSame('Fruits', $tree[1]['title']);
        $this->assertSame('Milk', $tree[2]['title']);

        // Helper Closures
        $getTree = function($includeNode) use ($repo, $roots, $sortOption) {
            return $repo->childrenHierarchy(
                $roots[0],
                true,
                array_merge($sortOption, array('decorate' => true)),
                $includeNode
            );
        };
        $getTreeHtml = function($includeNode) {
            $baseHtml = '<li>Boring Food<ul><li>Vegitables<ul><li>Cabbages</li><li>Carrots</li></ul></li></ul></li><li>Fruits<ul><li>Berries<ul><li>Strawberries</li></ul></li><li>Lemons</li><li>Oranges</li></ul></li><li>Milk<ul><li>Cheese<ul><li>Mould cheese</li></ul></li></ul></li></ul>';

            return $includeNode ? '<ul><li>Food<ul>'.$baseHtml.'</li></ul>' : '<ul>'.$baseHtml;
        };

        // First Tree - Including Root Node - Html test
        $this->assertSame($getTreeHtml(true), $getTree(true));

        // First Tree - Not including Root Node - Html test
        $this->assertSame($getTreeHtml(false), $getTree(false));
    }

    private function populate($class = self::CATEGORY)
    {
        $food = new $class;
        $food->setTitle("Food");
        $this->em->persist($food);

        $vegitables = new $class;
        $vegitables->setTitle('Vegitables');
        $vegitables->setParent($food);
        $this->em->persist($vegitables);

        $fruits = new $class;
        $fruits->setTitle('Fruits');
        $fruits->setParent($food);
        $this->em->persist($fruits);

        $oranges = new $class;
        $oranges->setTitle('Oranges');
        $oranges->setParent($fruits);
        $this->em->persist($oranges);

        $lemons = new $class;
        $lemons->setTitle('Lemons');
        $lemons->setParent($fruits);
        $this->em->persist($lemons);

        $berries = new $class;
        $berries->setTitle('Berries');
        $berries->setParent($fruits);
        $this->em->persist($berries);

        $strawberries = new $class;
        $strawberries->setTitle('Strawberries');
        $strawberries->setParent($berries);
        $this->em->persist($strawberries);

        $cabbages = new $class;
        $cabbages->setTitle('Cabbages');
        $cabbages->setParent($vegitables);
        $this->em->persist($cabbages);

        $carrots = new $class;
        $carrots->setTitle('Carrots');
        $carrots->setParent($vegitables);
        $this->em->persist($carrots);

        $milk = new $class;
        $milk->setTitle('Milk');
        $milk->setParent($food);
        $this->em->persist($milk);

        $cheese = new $class;
        $cheese->setTitle('Cheese');
        $cheese->setParent($milk);
        $this->em->persist($cheese);

        $mouldCheese = new $class;
        $mouldCheese->setTitle('Mould cheese');
        $mouldCheese->setParent($cheese);
        $this->em->persist($mouldCheese);

        $sports = new $class;
        $sports->setTitle('Sports');
        $this->em->persist($sports);

        $soccer = new $class;
        $soccer->setTitle('Soccer');
        $soccer->setParent($sports);
        $this->em->persist($soccer);

        $indoorSoccer = new $class;
        $indoorSoccer->setTitle('Indoor Soccer');
        $indoorSoccer->setParent($soccer);
        $this->em->persist($indoorSoccer);

        $this->em->flush();
        $this->em->clear();
    }
}
