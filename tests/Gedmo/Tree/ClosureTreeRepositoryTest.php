<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Tree;

use Doctrine\Common\EventManager;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Tests\Tree\Fixture\Closure\Category;
use Gedmo\Tests\Tree\Fixture\Closure\CategoryClosure;
use Gedmo\Tests\Tree\Fixture\Closure\CategoryWithoutLevel;
use Gedmo\Tests\Tree\Fixture\Closure\CategoryWithoutLevelClosure;
use Gedmo\Tree\Entity\Repository\AbstractTreeRepository;
use Gedmo\Tree\TreeListener;

/**
 * These are tests for Tree behavior
 *
 * @author Gustavo Adrian <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class ClosureTreeRepositoryTest extends BaseTestCaseORM
{
    public const CATEGORY = Category::class;
    public const CLOSURE = CategoryClosure::class;
    public const CATEGORY_WITHOUT_LEVEL = CategoryWithoutLevel::class;
    public const CATEGORY_WITHOUT_LEVEL_CLOSURE = CategoryWithoutLevelClosure::class;

    /**
     * @var TreeListener
     */
    protected $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new TreeListener();

        $evm = new EventManager();
        $evm->addEventSubscriber($this->listener);

        $this->getDefaultMockSqliteEntityManager($evm);
    }

    public function testChildCount(): void
    {
        $this->populate();

        $repo = $this->em->getRepository(self::CATEGORY);
        $food = $repo->findOneBy(['title' => 'Food']);

        // Count all
        $count = $repo->childCount();
        static::assertSame(15, $count);

        // Count all, but only direct ones
        $count = $repo->childCount(null, true);
        static::assertSame(2, $count);

        // Count food children
        $food = $repo->findOneBy(['title' => 'Food']);
        $count = $repo->childCount($food);
        static::assertSame(11, $count);

        // Count food children, but only direct ones
        $food = $repo->findOneBy(['title' => 'Food']);
        $count = $repo->childCount($food, true);
        static::assertSame(3, $count);
    }

    public function testPath(): void
    {
        $this->populate();

        $repo = $this->em->getRepository(self::CATEGORY);
        $fruits = $repo->findOneBy(['title' => 'Fruits']);

        $path = $repo->getPath($fruits);
        static::assertCount(2, $path);
        static::assertSame('Food', $path[0]->getTitle());
        static::assertSame('Fruits', $path[1]->getTitle());

        $strawberries = $repo->findOneBy(['title' => 'Strawberries']);
        $path = $repo->getPath($strawberries);
        static::assertCount(4, $path);
        static::assertSame('Food', $path[0]->getTitle());
        static::assertSame('Fruits', $path[1]->getTitle());
        static::assertSame('Berries', $path[2]->getTitle());
        static::assertSame('Strawberries', $path[3]->getTitle());
    }

    public function testChildren(): void
    {
        $this->populate();

        $repo = $this->em->getRepository(self::CATEGORY);
        $fruits = $repo->findOneBy(['title' => 'Fruits']);

        // direct children of node, sorted by title ascending order. NOT including the root node
        $children = $repo->children($fruits, true, 'title');
        static::assertCount(3, $children);
        static::assertSame('Berries', $children[0]->getTitle());
        static::assertSame('Lemons', $children[1]->getTitle());
        static::assertSame('Oranges', $children[2]->getTitle());

        // direct children of node, sorted by title ascending order. including the root node
        $children = $repo->children($fruits, true, 'title', 'asc', true);
        static::assertCount(4, $children);
        static::assertSame('Berries', $children[0]->getTitle());
        static::assertSame('Fruits', $children[1]->getTitle());
        static::assertSame('Lemons', $children[2]->getTitle());
        static::assertSame('Oranges', $children[3]->getTitle());

        // all children of node, NOT including the root
        $children = $repo->children($fruits);
        static::assertCount(4, $children);
        static::assertSame('Oranges', $children[0]->getTitle());
        static::assertSame('Lemons', $children[1]->getTitle());
        static::assertSame('Berries', $children[2]->getTitle());
        static::assertSame('Strawberries', $children[3]->getTitle());

        // all children of node, including the root
        $children = $repo->children($fruits, false, 'title', 'asc', true);
        static::assertCount(5, $children);
        static::assertSame('Berries', $children[0]->getTitle());
        static::assertSame('Fruits', $children[1]->getTitle());
        static::assertSame('Lemons', $children[2]->getTitle());
        static::assertSame('Oranges', $children[3]->getTitle());
        static::assertSame('Strawberries', $children[4]->getTitle());

        // test children sorting by array of fields
        $children = $repo->children($fruits, false, ['title'], 'ASC', true);
        static::assertCount(5, $children);
        static::assertSame('Berries', $children[0]->getTitle());
        static::assertSame('Fruits', $children[1]->getTitle());
        static::assertSame('Lemons', $children[2]->getTitle());
        static::assertSame('Oranges', $children[3]->getTitle());
        static::assertSame('Strawberries', $children[4]->getTitle());

        $children = $repo->children($fruits, false, ['level', 'title'], ['ASC', 'DESC'], true);
        static::assertCount(5, $children);
        static::assertSame('Fruits', $children[0]->getTitle());
        static::assertSame('Oranges', $children[1]->getTitle());
        static::assertSame('Lemons', $children[2]->getTitle());
        static::assertSame('Berries', $children[3]->getTitle());
        static::assertSame('Strawberries', $children[4]->getTitle());

        $children = $repo->children($fruits, false, ['level', 'title'], ['ASC'], true);
        static::assertCount(5, $children);
        static::assertSame('Fruits', $children[0]->getTitle());
        static::assertSame('Berries', $children[1]->getTitle());
        static::assertSame('Lemons', $children[2]->getTitle());
        static::assertSame('Oranges', $children[3]->getTitle());
        static::assertSame('Strawberries', $children[4]->getTitle());

        // test sorting by single-valued association field
        $children = $repo->children($fruits, false, 'parent');
        static::assertCount(4, $children);
        static::assertSame('Oranges', $children[0]->getTitle());
        static::assertSame('Lemons', $children[1]->getTitle());
        static::assertSame('Berries', $children[2]->getTitle());
        static::assertSame('Strawberries', $children[3]->getTitle());

        $children = $repo->children($fruits, false, ['parent'], ['ASC']);
        static::assertCount(4, $children);
        static::assertSame('Oranges', $children[0]->getTitle());
        static::assertSame('Lemons', $children[1]->getTitle());
        static::assertSame('Berries', $children[2]->getTitle());
        static::assertSame('Strawberries', $children[3]->getTitle());

        // direct root nodes
        $children = $repo->children(null, true, 'title');
        static::assertCount(2, $children);
        static::assertSame('Food', $children[0]->getTitle());
        static::assertSame('Sports', $children[1]->getTitle());

        // all tree
        $children = $repo->children();
        static::assertCount(15, $children);
    }

    public function testSingleNodeRemoval(): void
    {
        $this->populate();

        $repo = $this->em->getRepository(self::CATEGORY);
        $fruits = $repo->findOneBy(['title' => 'Fruits']);

        $repo->removeFromTree($fruits);
        // ensure in memory node integrity
        $this->em->flush();

        $food = $repo->findOneBy(['title' => 'Food']);
        $children = $repo->children($food, true);
        static::assertCount(5, $children);

        $berries = $repo->findOneBy(['title' => 'Berries']);
        static::assertSame(1, $repo->childCount($berries, true));

        $lemons = $repo->findOneBy(['title' => 'Lemons']);
        static::assertSame(0, $repo->childCount($lemons, true));

        $repo->removeFromTree($food);

        $vegitables = $repo->findOneBy(['title' => 'Vegitables']);
        static::assertSame(2, $repo->childCount($vegitables, true));
        static::assertNull($vegitables->getParent());

        $repo->removeFromTree($lemons);
        static::assertCount(5, $repo->children(null, true));
    }

    public function testBuildTreeWithLevelProperty(): void
    {
        $this->populate();

        $this->buildTreeTests(self::CATEGORY);
    }

    public function testBuildTreeWithoutLevelProperty(): void
    {
        $this->populate(self::CATEGORY_WITHOUT_LEVEL);

        $this->buildTreeTests(self::CATEGORY_WITHOUT_LEVEL);
    }

    public function testHavingLevelPropertyAvoidsSubqueryInSelectInGetNodesHierarchy(): void
    {
        $this->populate();

        $repo = $this->em->getRepository(self::CATEGORY);
        $roots = $repo->getRootNodes();
        $meta = $this->em->getClassMetadata(self::CATEGORY);
        $config = $this->listener->getConfiguration($this->em, $meta->getName());
        $qb = $repo->getNodesHierarchyQueryBuilder($roots[0], false, $config);

        static::assertFalse(strpos($qb->getQuery()->getDql(), '(SELECT MAX('));
    }

    public function testNotHavingLevelPropertyUsesASubqueryInSelectInGetNodesHierarchy(): void
    {
        $this->populate(self::CATEGORY_WITHOUT_LEVEL);

        $repo = $this->em->getRepository(self::CATEGORY_WITHOUT_LEVEL);
        $roots = $repo->getRootNodes();
        $meta = $this->em->getClassMetadata(self::CATEGORY_WITHOUT_LEVEL);
        $config = $this->listener->getConfiguration($this->em, $meta->getName());
        $qb = $repo->getNodesHierarchyQueryBuilder($roots[0], false, $config);

        static::assertTrue((bool) strpos($qb->getQuery()->getDql(), '(SELECT MAX('));
    }

    public function testChangeChildrenIndex(): void
    {
        $this->populate(self::CATEGORY);

        $childrenIndex = 'myChildren';
        $repo = $this->em->getRepository(self::CATEGORY);
        $repo->setChildrenIndex($childrenIndex);

        $tree = $repo->childrenHierarchy();

        static::assertIsArray($tree[0][$childrenIndex]);
    }

    // Utility Methods

    /**
     * @phpstan-param class-string $class
     */
    protected function buildTreeTests(string $class): void
    {
        $repo = $this->em->getRepository($class);
        static::assertInstanceOf(AbstractTreeRepository::class, $repo);
        $sortOption = ['childSort' => ['field' => 'title', 'dir' => 'asc']];

        $testClosure = static function (array $tree, $includeNode = false, $whichTree = 'both', $includeNewNode = false): void {
            if ('both' === $whichTree || 'first' === $whichTree) {
                $boringFood = $includeNewNode ? ($includeNode ? $tree[0]['__children'][0] : $tree[0]) : null;
                $fruitsIndex = $includeNewNode ? 1 : 0;
                $milkIndex = $includeNewNode ? 2 : 1;
                $fruits = $includeNode ? $tree[0]['__children'][$fruitsIndex] : $tree[$fruitsIndex];
                $milk = $includeNode ? $tree[0]['__children'][$milkIndex] : $tree[$milkIndex];
                $vegitables = $includeNewNode ? $boringFood['__children'][0] : ($includeNode ? $tree[0]['__children'][2] : $tree[2]);

                if ($includeNode) {
                    static::assertSame('Food', $tree[0]['title']);
                }

                static::assertSame('Fruits', $fruits['title']);
                static::assertSame('Berries', $fruits['__children'][0]['title']);
                static::assertSame('Strawberries', $fruits['__children'][0]['__children'][0]['title']);
                static::assertSame('Milk', $milk['title']);
                static::assertSame('Cheese', $milk['__children'][0]['title']);
                static::assertSame('Mould cheese', $milk['__children'][0]['__children'][0]['title']);

                if ($boringFood) {
                    static::assertSame('Boring Food', $boringFood['title']);
                }

                static::assertSame('Vegitables', $vegitables['title']);
                static::assertSame('Cabbages', $vegitables['__children'][0]['title']);
                static::assertSame('Carrots', $vegitables['__children'][1]['title']);
            }

            if ('both' === $whichTree || 'second' === $whichTree) {
                $root = 'both' === $whichTree ? $tree[1] : $tree[0];
                $soccer = $includeNode ? $root['__children'][0] : $root;

                if ($includeNode) {
                    static::assertSame('Sports', $root['title']);
                }

                static::assertSame('Soccer', $soccer['title']);
                static::assertSame('Indoor Soccer', $soccer['__children'][0]['title']);
            }
        };

        // All trees
        $tree = $repo->childrenHierarchy(null, false, $sortOption);

        $testClosure($tree, true, 'both');

        $roots = $repo->getRootNodes();

        // First root tree, including root node
        $tree = $repo->childrenHierarchy(
            $roots[0],
            false,
            $sortOption,
            true
        );

        $testClosure($tree, true, 'first');

        // First root tree, not including root node
        $tree = $repo->childrenHierarchy(
            $roots[0],
            false,
            $sortOption
        );

        $testClosure($tree, false, 'first');

        // Second root tree, including root node
        $tree = $repo->childrenHierarchy(
            $roots[1],
            false,
            $sortOption,
            true
        );

        $testClosure($tree, true, 'second');

        // Second root tree, not including root node
        $tree = $repo->childrenHierarchy(
            $roots[1],
            false,
            $sortOption
        );

        $testClosure($tree, false, 'second');

        $food = $repo->findOneBy(['title' => 'Food']);
        $vegitables = $repo->findOneBy(['title' => 'Vegitables']);

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

        $testClosure($tree, true, 'first', true);

        // First root tree, after inserting a new node in the middle. This not includes the root node
        $tree = $repo->childrenHierarchy(
            $roots[0],
            false,
            $sortOption
        );

        $testClosure($tree, false, 'first', true);

        // Second root tree, after inserting a new node in the middle. This includes the root node
        $tree = $repo->childrenHierarchy(
            $roots[1],
            false,
            $sortOption,
            true
        );

        $testClosure($tree, true, 'second', true);

        // Second root tree, after inserting a new node in the middle. This not includes the root node
        $tree = $repo->childrenHierarchy(
            $roots[1],
            false,
            $sortOption
        );

        $testClosure($tree, false, 'second', false);

        // Test a subtree, including node
        $node = $repo->findOneBy(['title' => 'Fruits']);
        $tree = $repo->childrenHierarchy(
            $node,
            false,
            $sortOption,
            true
        );

        static::assertSame('Fruits', $tree[0]['title']);
        static::assertSame('Berries', $tree[0]['__children'][0]['title']);
        static::assertSame('Strawberries', $tree[0]['__children'][0]['__children'][0]['title']);

        $node = $repo->findOneBy(['title' => 'Fruits']);
        $tree = $repo->childrenHierarchy(
            $node,
            false,
            $sortOption
        );

        static::assertSame('Berries', $tree[0]['title']);
        static::assertSame('Strawberries', $tree[0]['__children'][0]['title']);

        // First Tree Direct Nodes, including root node
        $tree = $repo->childrenHierarchy(
            $roots[0],
            true,
            $sortOption,
            true
        );

        $food = $tree[0];
        static::assertSame('Food', $food['title']);
        static::assertCount(3, $food['__children']);
        static::assertSame('Boring Food', $food['__children'][0]['title']);
        static::assertSame('Fruits', $food['__children'][1]['title']);
        static::assertSame('Milk', $food['__children'][2]['title']);

        // First Tree Direct Nodes, not including root node
        $tree = $repo->childrenHierarchy(
            $roots[0],
            true,
            $sortOption
        );

        static::assertCount(3, $tree);
        static::assertSame('Boring Food', $tree[0]['title']);
        static::assertSame('Fruits', $tree[1]['title']);
        static::assertSame('Milk', $tree[2]['title']);

        // Helper Closures
        $getTree = static function ($includeNode) use ($repo, $roots, $sortOption) {
            return $repo->childrenHierarchy(
                $roots[0],
                true,
                array_merge($sortOption, ['decorate' => true]),
                $includeNode
            );
        };
        $getTreeHtml = static function ($includeNode) {
            $baseHtml = '<li>Boring Food<ul><li>Vegitables<ul><li>Cabbages</li><li>Carrots</li></ul></li></ul></li><li>Fruits<ul><li>Berries<ul><li>Strawberries</li></ul></li><li>Lemons</li><li>Oranges</li></ul></li><li>Milk<ul><li>Cheese<ul><li>Mould cheese</li></ul></li></ul></li></ul>';

            return $includeNode ? '<ul><li>Food<ul>'.$baseHtml.'</li></ul>' : '<ul>'.$baseHtml;
        };

        // First Tree - Including Root Node - Html test
        static::assertSame($getTreeHtml(true), $getTree(true));

        // First Tree - Not including Root Node - Html test
        static::assertSame($getTreeHtml(false), $getTree(false));
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::CATEGORY,
            self::CLOSURE,
            self::CATEGORY_WITHOUT_LEVEL,
            self::CATEGORY_WITHOUT_LEVEL_CLOSURE,
        ];
    }

    private function populate(string $class = self::CATEGORY): void
    {
        $food = new $class();
        $food->setTitle('Food');
        $this->em->persist($food);

        $vegitables = new $class();
        $vegitables->setTitle('Vegitables');
        $vegitables->setParent($food);
        $this->em->persist($vegitables);

        $fruits = new $class();
        $fruits->setTitle('Fruits');
        $fruits->setParent($food);
        $this->em->persist($fruits);

        $oranges = new $class();
        $oranges->setTitle('Oranges');
        $oranges->setParent($fruits);
        $this->em->persist($oranges);

        $lemons = new $class();
        $lemons->setTitle('Lemons');
        $lemons->setParent($fruits);
        $this->em->persist($lemons);

        $berries = new $class();
        $berries->setTitle('Berries');
        $berries->setParent($fruits);
        $this->em->persist($berries);

        $strawberries = new $class();
        $strawberries->setTitle('Strawberries');
        $strawberries->setParent($berries);
        $this->em->persist($strawberries);

        $cabbages = new $class();
        $cabbages->setTitle('Cabbages');
        $cabbages->setParent($vegitables);
        $this->em->persist($cabbages);

        $carrots = new $class();
        $carrots->setTitle('Carrots');
        $carrots->setParent($vegitables);
        $this->em->persist($carrots);

        $milk = new $class();
        $milk->setTitle('Milk');
        $milk->setParent($food);
        $this->em->persist($milk);

        $cheese = new $class();
        $cheese->setTitle('Cheese');
        $cheese->setParent($milk);
        $this->em->persist($cheese);

        $mouldCheese = new $class();
        $mouldCheese->setTitle('Mould cheese');
        $mouldCheese->setParent($cheese);
        $this->em->persist($mouldCheese);

        $sports = new $class();
        $sports->setTitle('Sports');
        $this->em->persist($sports);

        $soccer = new $class();
        $soccer->setTitle('Soccer');
        $soccer->setParent($sports);
        $this->em->persist($soccer);

        $indoorSoccer = new $class();
        $indoorSoccer->setTitle('Indoor Soccer');
        $indoorSoccer->setParent($soccer);
        $this->em->persist($indoorSoccer);

        $this->em->flush();
        $this->em->clear();
    }
}
