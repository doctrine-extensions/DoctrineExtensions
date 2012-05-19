<?php

namespace Gedmo\Tree;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Tree\Fixture\Closure\Category;
use Tree\Fixture\Closure\CategoryWithoutLevel;
use Tree\Fixture\Closure\CategoryWithoutLevelClosure;

/**
 * These are tests for Tree behavior
 *
 * @author Gustavo Adrian <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Tree
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class ClosureTreeRepositoryTest extends BaseTestCaseORM
{
    const CATEGORY = "Tree\\Fixture\\Closure\\Category";
    const CLOSURE = "Tree\\Fixture\\Closure\\CategoryClosure";
    const CATEGORY_WITHOUT_LEVEL = "Tree\\Fixture\\Closure\\CategoryWithoutLevel";
    const CATEGORY_WITHOUT_LEVEL_CLOSURE = "Tree\\Fixture\\Closure\\CategoryWithoutLevelClosure";

    protected $listener;

    protected function setUp()
    {
        parent::setUp();

        $this->listener = new TreeListener;

        $evm = new EventManager;
        $evm->addEventSubscriber($this->listener);

        $this->getMockSqliteEntityManager($evm);
    }

    public function testChildCount()
    {
        $this->populate();

        $repo = $this->em->getRepository(self::CATEGORY);
        $food = $repo->findOneByTitle('Food');

        $directCount = $repo->childCount($food, true);
        $this->assertEquals(3, $directCount);

        $fruits = $repo->findOneByTitle('Fruits');
        $count = $repo->childCount($fruits);
        $this->assertEquals(4, $count);

        $rootCount = $repo->childCount(null, true);
        $this->assertEquals(1, $rootCount);
    }

    public function testPath()
    {
        $this->populate();

        $repo = $this->em->getRepository(self::CATEGORY);
        $fruits = $repo->findOneByTitle('Fruits');

        $path = $repo->getPath($fruits);
        $this->assertCount(2, $path);
        $this->assertEquals('Food', $path[0]->getTitle());
        $this->assertEquals('Fruits', $path[1]->getTitle());

        $strawberries = $repo->findOneByTitle('Strawberries');
        $path = $repo->getPath($strawberries);
        $this->assertCount(4, $path);
        $this->assertEquals('Food', $path[0]->getTitle());
        $this->assertEquals('Fruits', $path[1]->getTitle());
        $this->assertEquals('Berries', $path[2]->getTitle());
        $this->assertEquals('Strawberries', $path[3]->getTitle());
    }

    public function testChildren()
    {
        $this->populate();

        $repo = $this->em->getRepository(self::CATEGORY);
        $fruits = $repo->findOneByTitle('Fruits');

        // direct children of node, sorted by title ascending order
        $children = $repo->children($fruits, true, 'title');
        $this->assertCount(3, $children);
        $this->assertEquals('Berries', $children[0]->getTitle());
        $this->assertEquals('Lemons', $children[1]->getTitle());
        $this->assertEquals('Oranges', $children[2]->getTitle());

        // all children of node
        $children = $repo->children($fruits);
        $this->assertCount(4, $children);
        $this->assertEquals('Oranges', $children[0]->getTitle());
        $this->assertEquals('Lemons', $children[1]->getTitle());
        $this->assertEquals('Berries', $children[2]->getTitle());
        $this->assertEquals('Strawberries', $children[3]->getTitle());

        // direct root nodes
        $children = $repo->children(null, true, 'title');
        $this->assertCount(1, $children);
        $this->assertEquals('Food', $children[0]->getTitle());

        // all tree
        $children = $repo->children();
        $this->assertCount(12, $children);
    }

    public function testSingleNodeRemoval()
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
        $this->assertEquals(1, $repo->childCount($berries, true));

        $lemons = $repo->findOneByTitle('Lemons');
        $this->assertEquals(0, $repo->childCount($lemons, true));

        $repo->removeFromTree($food);

        $vegitables = $repo->findOneByTitle('Vegitables');
        $this->assertEquals(2, $repo->childCount($vegitables, true));
        $this->assertNull($vegitables->getParent());

        $repo->removeFromTree($lemons);
        $this->assertCount(4, $repo->children(null, true));
    }

    public function testBuildTreeWithLevelProperty()
    {
        $this->populate();

        $this->buildTreeTests(self::CATEGORY);
    }

    public function testBuildTreeWithoutLevelProperty()
    {
        $this->populate(self::CATEGORY_WITHOUT_LEVEL);

        $this->buildTreeTests(self::CATEGORY_WITHOUT_LEVEL);
    }

    public function testHavingLevelPropertyAvoidsSubqueryInSelectInGetNodesHierarchy()
    {
        $this->populate();

        $repo = $this->em->getRepository(self::CATEGORY);
        $roots = $repo->getRootNodes();
        $meta = $this->em->getClassMetadata(self::CATEGORY);
        $config = $this->listener->getConfiguration($this->em, $meta->name);
        $qb = $repo->getNodesHierarchyQueryBuilder($roots[0], false, $config);

        $this->assertFalse(strpos($qb->getQuery()->getDql(), '(SELECT MAX('));
    }

    public function testoNotHavingLevelPropertyUsesASubqueryInSelectInGetNodesHierarchy()
    {
        $this->populate(self::CATEGORY_WITHOUT_LEVEL);

        $repo = $this->em->getRepository(self::CATEGORY_WITHOUT_LEVEL);
        $roots = $repo->getRootNodes();
        $meta = $this->em->getClassMetadata(self::CATEGORY_WITHOUT_LEVEL);
        $config = $this->listener->getConfiguration($this->em, $meta->name);
        $qb = $repo->getNodesHierarchyQueryBuilder($roots[0], false, $config);

        $this->assertTrue(((bool) strpos($qb->getQuery()->getDql(), '(SELECT MAX(')));
    }

    // Utility Methods

    protected function buildTreeTests($class)
    {
        $repo = $this->em->getRepository($class);
        $roots = $repo->getRootNodes();
        $tree = $repo->childrenHierarchy(
            $roots[0],
            false,
            array('childSort' => array('field' => 'title', 'dir' => 'asc'))
        );

        $fruits = $tree[0]['__children'][0];
        $milk = $tree[0]['__children'][1];
        $vegitables = $tree[0]['__children'][2];

        $this->assertEquals('Food', $tree[0]['title']);
        $this->assertEquals('Fruits', $fruits['title']);
        $this->assertEquals('Berries', $fruits['__children'][0]['title']);
        $this->assertEquals('Strawberries', $fruits['__children'][0]['__children'][0]['title']);
        $this->assertEquals('Milk', $milk['title']);
        $this->assertEquals('Cheese', $milk['__children'][0]['title']);
        $this->assertEquals('Mould cheese', $milk['__children'][0]['__children'][0]['title']);
        $this->assertEquals('Vegitables', $vegitables['title']);
        $this->assertEquals('Cabbages', $vegitables['__children'][0]['title']);
        $this->assertEquals('Carrots', $vegitables['__children'][1]['title']);

        $food = $repo->findOneByTitle('Food');
        $vegitables = $repo->findOneByTitle('Vegitables');

        $boringFood = new $class();
        $boringFood->setTitle('Boring Food');
        $boringFood->setParent($food);
        $vegitables->setParent($boringFood);

        $this->em->persist($boringFood);

        $this->em->flush();

        $tree = $repo->childrenHierarchy(
            $roots[0],
            false,
            array('childSort' => array('field' => 'title', 'dir' => 'asc'))
        );

        $boringFood = $tree[0]['__children'][0];
        $fruits = $tree[0]['__children'][1];
        $milk = $tree[0]['__children'][2];
        $vegitables = $boringFood['__children'][0];

        $this->assertEquals('Food', $tree[0]['title']);
        $this->assertEquals('Fruits', $fruits['title']);
        $this->assertEquals('Berries', $fruits['__children'][0]['title']);
        $this->assertEquals('Strawberries', $fruits['__children'][0]['__children'][0]['title']);
        $this->assertEquals('Milk', $milk['title']);
        $this->assertEquals('Cheese', $milk['__children'][0]['title']);
        $this->assertEquals('Mould cheese', $milk['__children'][0]['__children'][0]['title']);
        $this->assertEquals('Boring Food', $boringFood['title']);
        $this->assertEquals('Vegitables', $boringFood['__children'][0]['title']);
        $this->assertEquals('Cabbages', $vegitables['__children'][0]['title']);
        $this->assertEquals('Carrots', $vegitables['__children'][1]['title']);
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::CATEGORY,
            self::CLOSURE,
            self::CATEGORY_WITHOUT_LEVEL,
            self::CATEGORY_WITHOUT_LEVEL_CLOSURE
        );
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

        $this->em->flush();
        $this->em->clear();
    }
}
