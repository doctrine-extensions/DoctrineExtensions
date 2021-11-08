<?php

namespace Gedmo\Tests\Tree;

use Doctrine\Common\EventManager;
use Gedmo\Exception\InvalidArgumentException;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Tests\Tree\Fixture\MPCategory;
use Gedmo\Tests\Tree\Fixture\MPCategoryWithTrimmedSeparator;
use Gedmo\Tree\TreeListener;

/**
 * These are tests for Tree behavior
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class MaterializedPathORMRepositoryTest extends BaseTestCaseORM
{
    public const CATEGORY = MPCategory::class;
    public const CATEGORY_WITH_TRIMMED_SEPARATOR = MPCategoryWithTrimmedSeparator::class;

    /** @var \Gedmo\Tree\Entity\Repository\MaterializedPathRepository */
    protected $repo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new TreeListener();

        $evm = new EventManager();
        $evm->addEventSubscriber($this->listener);

        $this->getMockSqliteEntityManager($evm);

        $meta = $this->em->getClassMetadata(self::CATEGORY);
        $this->config = $this->listener->getConfiguration($this->em, $meta->name);
        $this->populate();

        $this->repo = $this->em->getRepository(self::CATEGORY);
    }

    /**
     * @test
     */
    public function getRootNodes()
    {
        $result = $this->repo->getRootNodes('title');

        static::assertCount(3, $result);
        static::assertEquals('Drinks', $result[0]->getTitle());
        static::assertEquals('Food', $result[1]->getTitle());
        static::assertEquals('Sports', $result[2]->getTitle());
    }

    /**
     * @test
     */
    public function getPath()
    {
        $childNode = $this->repo->findOneBy(['title' => 'Carrots']);

        $result = $this->repo->getPath($childNode);

        static::assertCount(3, $result);
        static::assertEquals('Food', $result[0]->getTitle());
        static::assertEquals('Vegitables', $result[1]->getTitle());
        static::assertEquals('Carrots', $result[2]->getTitle());

        $rootNode = $this->repo->findOneBy(['title' => 'Sports']);

        $result = $this->repo->getPath($rootNode);

        static::assertCount(1, $result);
        static::assertEquals('Sports', $result[0]->getTitle());
    }

    /**
     * @test
     */
    public function getChildren()
    {
        $root = $this->repo->findOneBy(['title' => 'Food']);

        // Get all children from the root, NOT including it
        $result = $this->repo->getChildren($root, false, 'title');

        static::assertCount(4, $result);
        static::assertEquals('Carrots', $result[0]->getTitle());
        static::assertEquals('Fruits', $result[1]->getTitle());
        static::assertEquals('Potatoes', $result[2]->getTitle());
        static::assertEquals('Vegitables', $result[3]->getTitle());

        // Get all children from the root, including it
        $result = $this->repo->getChildren($root, false, 'title', 'asc', true);

        static::assertCount(5, $result);
        static::assertEquals('Carrots', $result[0]->getTitle());
        static::assertEquals('Food', $result[1]->getTitle());
        static::assertEquals('Fruits', $result[2]->getTitle());
        static::assertEquals('Potatoes', $result[3]->getTitle());
        static::assertEquals('Vegitables', $result[4]->getTitle());

        // Get direct children from the root, NOT including it
        $result = $this->repo->getChildren($root, true, 'title', 'asc');

        static::assertCount(2, $result);
        static::assertEquals('Fruits', $result[0]->getTitle());
        static::assertEquals('Vegitables', $result[1]->getTitle());

        // Get direct children from the root, including it
        $result = $this->repo->getChildren($root, true, 'title', 'asc', true);

        static::assertCount(3, $result);
        static::assertEquals('Food', $result[0]->getTitle());
        static::assertEquals('Fruits', $result[1]->getTitle());
        static::assertEquals('Vegitables', $result[2]->getTitle());

        // Get ALL nodes
        $result = $this->repo->getChildren(null, false, 'title');

        static::assertCount(9, $result);
        static::assertEquals('Best Whisky', $result[0]->getTitle());
        static::assertEquals('Carrots', $result[1]->getTitle());
        static::assertEquals('Drinks', $result[2]->getTitle());
        static::assertEquals('Food', $result[3]->getTitle());
        static::assertEquals('Fruits', $result[4]->getTitle());
        static::assertEquals('Potatoes', $result[5]->getTitle());
        static::assertEquals('Sports', $result[6]->getTitle());
        static::assertEquals('Vegitables', $result[7]->getTitle());
        static::assertEquals('Whisky', $result[8]->getTitle());

        // Get ALL root nodes
        $result = $this->repo->getChildren(null, true, 'title');

        static::assertCount(3, $result);
        static::assertEquals('Drinks', $result[0]->getTitle());
        static::assertEquals('Food', $result[1]->getTitle());
        static::assertEquals('Sports', $result[2]->getTitle());
    }

    /**
     * @test
     */
    public function getChildrenForEntityWithTrimmedSeparators()
    {
        $meta = $this->em->getClassMetadata(self::CATEGORY_WITH_TRIMMED_SEPARATOR);
        $this->populate(self::CATEGORY_WITH_TRIMMED_SEPARATOR);

        $this->repo = $this->em->getRepository(self::CATEGORY_WITH_TRIMMED_SEPARATOR);
        $root = $this->repo->findOneBy(['title' => 'Food']);

        // Get all children from the root, NOT including it
        $result = $this->repo->getChildren($root, false, 'title');

        static::assertCount(4, $result);
        static::assertEquals('Carrots', $result[0]->getTitle());
        static::assertEquals('Fruits', $result[1]->getTitle());
        static::assertEquals('Potatoes', $result[2]->getTitle());
        static::assertEquals('Vegitables', $result[3]->getTitle());

        // Get all children from the root, including it
        $result = $this->repo->getChildren($root, false, 'title', 'asc', true);

        static::assertCount(5, $result);
        static::assertEquals('Carrots', $result[0]->getTitle());
        static::assertEquals('Food', $result[1]->getTitle());
        static::assertEquals('Fruits', $result[2]->getTitle());
        static::assertEquals('Potatoes', $result[3]->getTitle());
        static::assertEquals('Vegitables', $result[4]->getTitle());

        // Get direct children from the root, NOT including it
        $result = $this->repo->getChildren($root, true, 'title', 'asc');
        static::assertCount(2, $result);
        static::assertEquals('Fruits', $result[0]->getTitle());
        static::assertEquals('Vegitables', $result[1]->getTitle());

        // Get direct children from the root, including it
        $result = $this->repo->getChildren($root, true, 'title', 'asc', true);

        static::assertCount(3, $result);
        static::assertEquals('Food', $result[0]->getTitle());
        static::assertEquals('Fruits', $result[1]->getTitle());
        static::assertEquals('Vegitables', $result[2]->getTitle());

        // Get ALL nodes
        $result = $this->repo->getChildren(null, false, 'title');

        static::assertCount(9, $result);
        static::assertEquals('Best Whisky', $result[0]->getTitle());
        static::assertEquals('Carrots', $result[1]->getTitle());
        static::assertEquals('Drinks', $result[2]->getTitle());
        static::assertEquals('Food', $result[3]->getTitle());
        static::assertEquals('Fruits', $result[4]->getTitle());
        static::assertEquals('Potatoes', $result[5]->getTitle());
        static::assertEquals('Sports', $result[6]->getTitle());
        static::assertEquals('Vegitables', $result[7]->getTitle());
        static::assertEquals('Whisky', $result[8]->getTitle());

        // Get ALL root nodes
        $result = $this->repo->getChildren(null, true, 'title');

        static::assertCount(3, $result);
        static::assertEquals('Drinks', $result[0]->getTitle());
        static::assertEquals('Food', $result[1]->getTitle());
        static::assertEquals('Sports', $result[2]->getTitle());
    }

    /**
     * @test
     */
    public function getTree()
    {
        $tree = $this->repo->getTree();

        static::assertCount(9, $tree);
        static::assertEquals('Drinks', $tree[0]->getTitle());
        static::assertEquals('Whisky', $tree[1]->getTitle());
        static::assertEquals('Best Whisky', $tree[2]->getTitle());
        static::assertEquals('Food', $tree[3]->getTitle());
        static::assertEquals('Fruits', $tree[4]->getTitle());
        static::assertEquals('Vegitables', $tree[5]->getTitle());
        static::assertEquals('Carrots', $tree[6]->getTitle());
        static::assertEquals('Potatoes', $tree[7]->getTitle());
        static::assertEquals('Sports', $tree[8]->getTitle());

        // Get tree from a specific root node
        $roots = $this->repo->getRootNodes();
        $tree = $this->repo->getTree($roots[0]);

        static::assertCount(3, $tree);
        static::assertEquals('Drinks', $tree[0]->getTitle());
        static::assertEquals('Whisky', $tree[1]->getTitle());
        static::assertEquals('Best Whisky', $tree[2]->getTitle());
    }

    public function testChildrenHierarchyMethod()
    {
        $tree = $this->repo->childrenHierarchy();

        static::assertEquals('Drinks', $tree[0]['title']);
        static::assertEquals('Whisky', $tree[0]['__children'][0]['title']);
        static::assertEquals('Best Whisky', $tree[0]['__children'][0]['__children'][0]['title']);
        $vegitablesChildren = $tree[1]['__children'][1]['__children'];
        static::assertEquals('Food', $tree[1]['title']);
        static::assertEquals('Fruits', $tree[1]['__children'][0]['title']);
        static::assertEquals('Vegitables', $tree[1]['__children'][1]['title']);
        static::assertEquals('Carrots', $vegitablesChildren[0]['title']);
        static::assertEquals('Potatoes', $vegitablesChildren[1]['title']);
        static::assertEquals('Sports', $tree[2]['title']);

        // Tree of one specific root, without the root node
        $roots = $this->repo->getRootNodes();
        $tree = $this->repo->childrenHierarchy($roots[0]);

        static::assertEquals('Whisky', $tree[0]['title']);
        static::assertEquals('Best Whisky', $tree[0]['__children'][0]['title']);

        // Tree of one specific root, with the root node
        $tree = $this->repo->childrenHierarchy($roots[0], false, [], true);

        static::assertEquals('Drinks', $tree[0]['title']);
        static::assertEquals('Whisky', $tree[0]['__children'][0]['title']);
        static::assertEquals('Best Whisky', $tree[0]['__children'][0]['__children'][0]['title']);

        // Tree of one specific root only with direct children, without the root node
        $roots = $this->repo->getRootNodes();
        $tree = $this->repo->childrenHierarchy($roots[1], true);

        static::assertCount(2, $tree);
        static::assertEquals('Fruits', $tree[0]['title']);
        static::assertEquals('Vegitables', $tree[1]['title']);

        // Tree of one specific root only with direct children, with the root node
        $tree = $this->repo->childrenHierarchy($roots[1], true, [], true);

        static::assertCount(1, $tree);
        static::assertCount(2, $tree[0]['__children']);
        static::assertEquals('Food', $tree[0]['title']);
        static::assertEquals('Fruits', $tree[0]['__children'][0]['title']);
        static::assertEquals('Vegitables', $tree[0]['__children'][1]['title']);

        // HTML Tree of one specific root, without the root node
        $roots = $this->repo->getRootNodes();
        $tree = $this->repo->childrenHierarchy($roots[0], false, ['decorate' => true], false);

        static::assertEquals('<ul><li>Whisky<ul><li>Best Whisky</li></ul></li></ul>', $tree);

        // HTML Tree of one specific root, with the root node
        $roots = $this->repo->getRootNodes();
        $tree = $this->repo->childrenHierarchy($roots[0], false, ['decorate' => true], true);

        static::assertEquals('<ul><li>Drinks<ul><li>Whisky<ul><li>Best Whisky</li></ul></li></ul></li></ul>', $tree);
    }

    public function testChildCount()
    {
        // Count all
        $count = $this->repo->childCount();

        static::assertEquals(9, $count);

        // Count all, but only direct ones
        $count = $this->repo->childCount(null, true);

        static::assertEquals(3, $count);

        // Count food children
        $food = $this->repo->findOneBy(['title' => 'Food']);
        $count = $this->repo->childCount($food);

        static::assertEquals(4, $count);

        // Count food children, but only direct ones
        $count = $this->repo->childCount($food, true);

        static::assertEquals(2, $count);
    }

    public function testChildCountIfAnObjectIsPassedWhichIsNotAnInstanceOfTheEntityClassThrowException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->repo->childCount(new \DateTime());
    }

    public function testChildCountIfAnObjectIsPassedIsAnInstanceOfTheEntityClassButIsNotHandledByUnitOfWorkThrowException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->repo->childCount($this->createCategory());
    }

    public function testIssue458()
    {
        $this->em->clear();

        $node = $this->repo->findOneBy(['title' => 'Fruits']);
        $newNode = $this->createCategory();
        $parent = $node->getParent();

        static::assertFalse($parent->__isInitialized());

        $newNode->setTitle('New Node');
        $newNode->setParent($parent);

        $this->em->persist($newNode);
        $this->em->flush();

        // @todo: Remove the condition and the `else` block when dropping support for "phpunit/phpunit" < 9.1.
        if (method_exists($this, 'assertMatchesRegularExpression')) {
            static::assertMatchesRegularExpression('/Food\-\d+,New\sNode\-\d+/', $newNode->getPath());
        } else {
            static::assertRegExp('/Food\-\d+,New\sNode\-\d+/', $newNode->getPath());
        }
        static::assertEquals(2, $newNode->getLevel());
    }

    public function testChangeChildrenIndex()
    {
        $childrenIndex = 'myChildren';
        $this->repo->setChildrenIndex($childrenIndex);

        $tree = $this->repo->childrenHierarchy();

        static::assertIsArray($tree[0][$childrenIndex]);
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::CATEGORY,
            self::CATEGORY_WITH_TRIMMED_SEPARATOR,
        ];
    }

    public function createCategory($class = null)
    {
        if (!$class) {
            $class = self::CATEGORY;
        }

        return new $class();
    }

    private function populate($class = null)
    {
        $root = $this->createCategory($class);
        $root->setTitle('Food');

        $root2 = $this->createCategory($class);
        $root2->setTitle('Sports');

        $child = $this->createCategory($class);
        $child->setTitle('Fruits');
        $child->setParent($root);

        $child2 = $this->createCategory($class);
        $child2->setTitle('Vegitables');
        $child2->setParent($root);

        $childsChild = $this->createCategory($class);
        $childsChild->setTitle('Carrots');
        $childsChild->setParent($child2);

        $potatoes = $this->createCategory($class);
        $potatoes->setTitle('Potatoes');
        $potatoes->setParent($child2);

        $drinks = $this->createCategory($class);
        $drinks->setTitle('Drinks');

        $whisky = $this->createCategory($class);
        $whisky->setTitle('Whisky');
        $whisky->setParent($drinks);

        $bestWhisky = $this->createCategory($class);
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
