<?php

namespace Gedmo\Tests\Tree;

use Doctrine\Common\EventManager;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Tests\Tree\Fixture\RootCategory;
use Gedmo\Tree\TreeListener;

/**
 * These are tests for Tree behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class NestedTreeRootRepositoryTest extends BaseTestCaseORM
{
    public const CATEGORY = RootCategory::class;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new TreeListener());

        $this->getMockSqliteEntityManager($evm);
        $this->populate();
    }

    /**
     * Based on issue #342
     *
     * @test
     */
    public function shouldBeAbleToShiftRootNode()
    {
        $repo = $this->em->getRepository(self::CATEGORY);

        $food = $repo->findOneBy(['title' => 'Food']);
        $acme = new RootCategory();
        $acme->setTitle('Acme');

        $food->setParent($acme);

        $this->em->persist($acme);
        $this->em->persist($food);
        $this->em->flush();

        static::assertNull($acme->getParent());
        static::assertSame($acme, $food->getParent());
        static::assertSame($acme->getId(), $acme->getRoot());
        static::assertSame($acme->getId(), $food->getRoot());
        static::assertSame(1, $acme->getLeft());
        static::assertSame(12, $acme->getRight());
        static::assertSame(2, $food->getLeft());
        static::assertSame(11, $food->getRight());
    }

    /**
     * @test
     */
    public function shouldSupportChildrenHierarchyAsArray()
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $result = $repo->childrenHierarchy();
        static::assertCount(2, $result);
        static::assertTrue(isset($result[0]['__children'][0]['__children']));

        $vegies = $repo->findOneBy(['title' => 'Vegitables']);
        $result = $repo->childrenHierarchy($vegies);
        static::assertCount(2, $result);
        static::assertCount(0, $result[0]['__children']);

        // Complete Tree
        $roots = $repo->getRootNodes();
        $tree = $repo->childrenHierarchy();

        static::assertCount(2, $tree);     // Count roots
        static::assertEquals('Food', $tree[0]['title']);
        static::assertEquals('Sports', $tree[1]['title']);
        static::assertEquals('Fruits', $tree[0]['__children'][0]['title']);
        static::assertEquals('Vegitables', $tree[0]['__children'][1]['title']);
        static::assertEquals('Carrots', $tree[0]['__children'][1]['__children'][0]['title']);
        static::assertEquals('Potatoes', $tree[0]['__children'][1]['__children'][1]['title']);

        // Tree of one specific root, without the root node
        $roots = $repo->getRootNodes();
        $tree = $repo->childrenHierarchy($roots[0]);

        static::assertCount(2, $tree);     // Count roots
        static::assertEquals('Fruits', $tree[0]['title']);
        static::assertEquals('Vegitables', $tree[1]['title']);
        static::assertEquals('Carrots', $tree[1]['__children'][0]['title']);
        static::assertEquals('Potatoes', $tree[1]['__children'][1]['title']);

        // Tree of one specific root, with the root node
        $tree = $repo->childrenHierarchy($roots[0], false, [], true);

        static::assertCount(1, $tree);     // Count roots
        static::assertEquals('Food', $tree[0]['title']);
        static::assertEquals('Fruits', $tree[0]['__children'][0]['title']);
        static::assertEquals('Vegitables', $tree[0]['__children'][1]['title']);
        static::assertEquals('Carrots', $tree[0]['__children'][1]['__children'][0]['title']);
        static::assertEquals('Potatoes', $tree[0]['__children'][1]['__children'][1]['title']);

        // Tree of one specific root only with direct children, without the root node
        $roots = $repo->getRootNodes();
        $tree = $repo->childrenHierarchy($roots[0], true);

        static::assertCount(2, $tree);
        static::assertEquals('Fruits', $tree[0]['title']);
        static::assertEquals('Vegitables', $tree[1]['title']);

        // Tree of one specific root only with direct children, with the root node
        $tree = $repo->childrenHierarchy($roots[0], true, [], true);

        static::assertCount(1, $tree);
        static::assertEquals('Food', $tree[0]['title']);
        static::assertEquals('Fruits', $tree[0]['__children'][0]['title']);
        static::assertEquals('Vegitables', $tree[0]['__children'][1]['title']);
    }

    /**
     * @test
     */
    public function shouldSupportChildrenHierarchyAsHtml()
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $food = $repo->findOneBy(['title' => 'Food']);
        $decorate = true;
        $defaultHtmlTree = $repo->childrenHierarchy($food, false, compact('decorate'));

        static::assertEquals(
            '<ul><li>Fruits</li><li>Vegitables<ul><li>Carrots</li><li>Potatoes</li></ul></li></ul>',
            $defaultHtmlTree
        );

        // custom title
        $nodeDecorator = static function ($node) {
            return '<span>'.$node['title'].'</span>';
        };

        $decoratedHtmlTree = $repo->childrenHierarchy(
            $food,
            false,
            compact('decorate', 'nodeDecorator')
        );

        static::assertEquals(
            '<ul><li><span>Fruits</span></li><li><span>Vegitables</span><ul><li><span>Carrots</span></li><li><span>Potatoes</span></li></ul></li></ul>',
            $decoratedHtmlTree
        );
        // cli friendly output
        $rootOpen = '';
        $rootClose = '';
        $childOpen = '';
        $childClose = '';
        $nodeDecorator = static function ($node) {
            return str_repeat('-', $node['level']).$node['title']."\n";
        };

        $decoratedCliTree = $repo->childrenHierarchy(
            $food,
            false,
            compact('decorate', 'nodeDecorator', 'rootOpen', 'rootClose', 'childOpen', 'childClose')
        );
        static::assertEquals(
            "-Fruits\n-Vegitables\n--Carrots\n--Potatoes\n",
            $decoratedCliTree
        );

        $rootOpen = static function () {return '<ul class="group">'; };
        // check support of the closures in rootClose
        $rootClose = static function () {return '</ul><!--rootCloseClosure-->'; };
        $childOpen = static function (&$node) {
            return '<li class="depth'.$node['level'].'">';
        };
        // check support of the closures in childClose
        $childClose = static function (&$node) {
            return '</li><!--childCloseClosure-->';
        };
        $decoratedHtmlTree = $repo->childrenHierarchy(
            $food,
            false,
            compact('decorate', 'rootOpen', 'rootClose', 'childOpen', 'childClose')
        );

        static::assertEquals(
            '<ul class="group"><li class="depth1">Fruits</li><!--childCloseClosure--><li class="depth1">Vegitables<ul class="group"><li class="depth2">Carrots</li><!--childCloseClosure--><li class="depth2">Potatoes</li><!--childCloseClosure--></ul><!--rootCloseClosure--></li><!--childCloseClosure--></ul><!--rootCloseClosure-->',
            $decoratedHtmlTree
        );
    }

    /**
     * @test
     */
    public function shouldSupportChildrenHierarchyByBuildTreeFunction()
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $q = $this->em
            ->createQueryBuilder()
            ->select('node')
            ->from(self::CATEGORY, 'node')
            ->orderBy('node.root, node.lft', 'ASC')
            ->where('node.root = 1')
            ->getQuery()
        ;
        $tree = $repo->buildTree($q->getArrayResult());
        static::assertCount(1, $tree);
        static::assertCount(2, $tree[0]['__children']);
        $nodes = [];
        $options = ['decorate' => true];
        static::assertEquals('', $repo->buildTree($nodes, $options), 'should give empty string when there are no nodes given');
    }

    /**
     * @test
     */
    public function shouldRemoveRootNodeFromTree()
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $this->populateMore();

        $food = $repo->findOneBy(['title' => 'Food']);
        $repo->removeFromTree($food);
        $this->em->clear();

        $food = $repo->findOneBy(['title' => 'Food']);
        static::assertNull($food);

        $node = $repo->findOneBy(['title' => 'Fruits']);

        static::assertEquals(1, $node->getLeft());
        static::assertEquals(2, $node->getRight());
        static::assertEquals(3, $node->getRoot());
        static::assertNull($node->getParent());

        $node = $repo->findOneBy(['title' => 'Vegitables']);

        static::assertEquals(1, $node->getLeft());
        static::assertEquals(10, $node->getRight());
        static::assertEquals(4, $node->getRoot());
        static::assertNull($node->getParent());
    }

    /**
     * @test
     */
    public function shouldHandleBasicRepositoryMethods()
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $carrots = $repo->findOneBy(['title' => 'Carrots']);

        $path = $repo->getPath($carrots);
        static::assertCount(3, $path);
        static::assertEquals('Food', $path[0]->getTitle());
        static::assertEquals('Vegitables', $path[1]->getTitle());
        static::assertEquals('Carrots', $path[2]->getTitle());

        $vegies = $repo->findOneBy(['title' => 'Vegitables']);
        $childCount = $repo->childCount($vegies);
        static::assertEquals(2, $childCount);

        $food = $repo->findOneBy(['title' => 'Food']);
        $childCount = $repo->childCount($food, true);
        static::assertEquals(2, $childCount);

        $childCount = $repo->childCount($food);
        static::assertEquals(4, $childCount);

        $childCount = $repo->childCount();
        static::assertEquals(6, $childCount);

        $childCount = $repo->childCount(null, true);
        static::assertEquals(2, $childCount);
    }

    /**
     * @test
     */
    public function shouldHandleAdvancedRepositoryFunctions()
    {
        $this->populateMore();
        $repo = $this->em->getRepository(self::CATEGORY);

        // verification

        static::assertTrue($repo->verify());

        $dql = 'UPDATE '.self::CATEGORY.' node';
        $dql .= ' SET node.lft = 5';
        $dql .= ' WHERE node.id = 4';
        $this->em->createQuery($dql)->getSingleScalarResult();

        $this->em->clear(); // must clear cached entities
        $errors = $repo->verify();
        static::assertCount(2, $errors);
        static::assertEquals('index [4], missing on tree root: 1', $errors[0]);
        static::assertEquals('index [5], duplicate on tree root: 1', $errors[1]);

        // test recover functionality
        $repo->recover();
        $this->em->flush();

        static::assertTrue($repo->verify());

        $this->em->clear();
        $onions = $repo->findOneBy(['title' => 'Onions']);

        static::assertEquals(11, $onions->getLeft());
        static::assertEquals(12, $onions->getRight());

        // move up

        $repo->moveUp($onions);

        static::assertEquals(9, $onions->getLeft());
        static::assertEquals(10, $onions->getRight());

        $repo->moveUp($onions, true);

        static::assertEquals(5, $onions->getLeft());
        static::assertEquals(6, $onions->getRight());

        // move down

        $repo->moveDown($onions, 2);

        static::assertEquals(9, $onions->getLeft());
        static::assertEquals(10, $onions->getRight());

        // reorder

        $node = $repo->findOneBy(['title' => 'Food']);
        $repo->reorder($node, 'title', 'ASC', false);

        $node = $repo->findOneBy(['title' => 'Cabbages']);

        static::assertEquals(5, $node->getLeft());
        static::assertEquals(6, $node->getRight());

        $node = $repo->findOneBy(['title' => 'Carrots']);

        static::assertEquals(7, $node->getLeft());
        static::assertEquals(8, $node->getRight());

        $node = $repo->findOneBy(['title' => 'Onions']);

        static::assertEquals(9, $node->getLeft());
        static::assertEquals(10, $node->getRight());

        $node = $repo->findOneBy(['title' => 'Potatoes']);

        static::assertEquals(11, $node->getLeft());
        static::assertEquals(12, $node->getRight());

        // leafs

        $leafs = $repo->getLeafs($node);
        static::assertCount(5, $leafs);
        static::assertEquals('Fruits', $leafs[0]->getTitle());
        static::assertEquals('Cabbages', $leafs[1]->getTitle());
        static::assertEquals('Carrots', $leafs[2]->getTitle());
        static::assertEquals('Onions', $leafs[3]->getTitle());
        static::assertEquals('Potatoes', $leafs[4]->getTitle());

        // remove

        $node = $repo->findOneBy(['title' => 'Fruits']);
        $id = $node->getId();
        $repo->removeFromTree($node);

        static::assertNull($repo->find($id));

        $node = $repo->findOneBy(['title' => 'Vegitables']);
        $id = $node->getId();
        $repo->removeFromTree($node);

        static::assertNull($repo->find($id));
        $this->em->clear();

        $node = $repo->findOneBy(['title' => 'Cabbages']);

        static::assertEquals(1, $node->getRoot());
        static::assertEquals(1, $node->getParent()->getId());
    }

    /**
     * @test
     */
    public function shouldRemoveTreeLeafFromTree()
    {
        $this->populateMore();
        $repo = $this->em->getRepository(self::CATEGORY);
        $onions = $repo->findOneBy(['title' => 'Onions']);
        $id = $onions->getId();
        $repo->removeFromTree($onions);

        static::assertNull($repo->find($id));
        $this->em->clear();

        $vegies = $repo->findOneBy(['title' => 'Vegitables']);
        static::assertTrue($repo->verify());
    }

    /**
     * @test
     */
    public function getRootNodesTest()
    {
        $repo = $this->em->getRepository(self::CATEGORY);

        // Test getRootNodes without custom ordering
        $roots = $repo->getRootNodes();

        static::assertCount(2, $roots);
        static::assertEquals('Food', $roots[0]->getTitle());
        static::assertEquals('Sports', $roots[1]->getTitle());

        // Test getRootNodes with custom ordering
        $roots = $repo->getRootNodes('title', 'desc');

        static::assertCount(2, $roots);
        static::assertEquals('Sports', $roots[0]->getTitle());
        static::assertEquals('Food', $roots[1]->getTitle());
    }

    /**
     * @test
     */
    public function changeChildrenIndexTest()
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $childrenIndex = 'myChildren';
        $repo->setChildrenIndex($childrenIndex);

        $tree = $repo->childrenHierarchy();

        static::assertIsArray($tree[0][$childrenIndex]);
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::CATEGORY,
        ];
    }

    private function populateMore()
    {
        $vegies = $this->em->getRepository(self::CATEGORY)
            ->findOneBy(['title' => 'Vegitables']);

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
        $root->setTitle('Food');

        $root2 = new RootCategory();
        $root2->setTitle('Sports');

        $child = new RootCategory();
        $child->setTitle('Fruits');
        $child->setParent($root);

        $child2 = new RootCategory();
        $child2->setTitle('Vegitables');
        $child2->setParent($root);

        $childsChild = new RootCategory();
        $childsChild->setTitle('Carrots');
        $childsChild->setParent($child2);

        $potatoes = new RootCategory();
        $potatoes->setTitle('Potatoes');
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
