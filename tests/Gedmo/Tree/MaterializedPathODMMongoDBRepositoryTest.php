<?php

namespace Gedmo\Tests\Tree;

use Doctrine\Common\EventManager;
use Doctrine\ODM\MongoDB\Iterator\CachingIterator;
use Gedmo\Exception\InvalidArgumentException;
use Gedmo\Tests\Tool\BaseTestCaseMongoODM;
use Gedmo\Tests\Tree\Fixture\Document\Category;
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
final class MaterializedPathODMMongoDBRepositoryTest extends BaseTestCaseMongoODM
{
    private const CATEGORY = Category::class;

    /**
     * @var \Gedmo\Tree\Document\MongoDB\Repository\MaterializedPathRepository
     */
    protected $repo;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new TreeListener());

        $this->getMockDocumentManager($evm);
        $this->populate();

        $this->repo = $this->dm->getRepository(self::CATEGORY);
    }

    /**
     * @test
     */
    public function getRootNodes()
    {
        /** @var CachingIterator $result */
        $result = $this->repo->getRootNodes('title');

        static::assertEquals(3, \iterator_count($result));
        $result->rewind();

        $result->rewind();

        static::assertEquals('Drinks', $result->current()->getTitle());
        $result->next();
        static::assertEquals('Food', $result->current()->getTitle());
        $result->next();
        static::assertEquals('Sports', $result->current()->getTitle());
    }

    /**
     * @test
     */
    public function getChildren()
    {
        $root = $this->repo->findOneBy(['title' => 'Food']);

        // Get all children from the root, including it
        /** @var CachingIterator $result */
        $result = $this->repo->getChildren($root, false, 'title', 'asc', true);

        static::assertEquals(5, \iterator_count($result));
        $result->rewind();

        $result->rewind();
        static::assertEquals('Carrots', $result->current()->getTitle());
        $result->next();
        static::assertEquals('Food', $result->current()->getTitle());
        $result->next();
        static::assertEquals('Fruits', $result->current()->getTitle());
        $result->next();
        static::assertEquals('Potatoes', $result->current()->getTitle());
        $result->next();
        static::assertEquals('Vegitables', $result->current()->getTitle());

        // Get all children from the root, NOT including it
        /** @var CachingIterator $result */
        $result = $this->repo->getChildren($root, false, 'title', 'asc', false);

        static::assertEquals(4, \iterator_count($result));
        $result->rewind();
        $result->rewind();
        static::assertEquals('Carrots', $result->current()->getTitle());
        $result->next();
        static::assertEquals('Fruits', $result->current()->getTitle());
        $result->next();
        static::assertEquals('Potatoes', $result->current()->getTitle());
        $result->next();
        static::assertEquals('Vegitables', $result->current()->getTitle());

        // Get direct children from the root, including it
        /** @var CachingIterator $result */
        $result = $this->repo->getChildren($root, true, 'title', 'asc', true);

        static::assertEquals(3, \iterator_count($result));
        $result->rewind();
        $result->rewind();
        static::assertEquals('Food', $result->current()->getTitle());
        $result->next();
        static::assertEquals('Fruits', $result->current()->getTitle());
        $result->next();
        static::assertEquals('Vegitables', $result->current()->getTitle());

        // Get direct children from the root, NOT including it
        /** @var CachingIterator $result */
        $result = $this->repo->getChildren($root, true, 'title', 'asc', false);

        static::assertEquals(2, \iterator_count($result));
        $result->rewind();
        static::assertEquals('Fruits', $result->current()->getTitle());
        $result->next();
        static::assertEquals('Vegitables', $result->current()->getTitle());

        // Get ALL nodes
        $result = $this->repo->getChildren(null, false, 'title');

        static::assertEquals(9, \iterator_count($result));
        $result->rewind();
        static::assertEquals('Best Whisky', $result->current()->getTitle());
        $result->next();
        static::assertEquals('Carrots', $result->current()->getTitle());
        $result->next();
        static::assertEquals('Drinks', $result->current()->getTitle());
        $result->next();
        static::assertEquals('Food', $result->current()->getTitle());
        $result->next();
        static::assertEquals('Fruits', $result->current()->getTitle());
        $result->next();
        static::assertEquals('Potatoes', $result->current()->getTitle());
        $result->next();
        static::assertEquals('Sports', $result->current()->getTitle());
        $result->next();
        static::assertEquals('Vegitables', $result->current()->getTitle());
        $result->next();
        static::assertEquals('Whisky', $result->current()->getTitle());

        // Get ALL root nodes
        $result = $this->repo->getChildren(null, true, 'title');

        static::assertEquals(3, \iterator_count($result));
        $result->rewind();
        static::assertEquals('Drinks', $result->current()->getTitle());
        $result->next();
        static::assertEquals('Food', $result->current()->getTitle());
        $result->next();
        static::assertEquals('Sports', $result->current()->getTitle());
    }

    /**
     * @test
     */
    public function getTree()
    {
        $tree = $this->repo->getTree();

        static::assertEquals(9, \iterator_count($tree));
        $tree->rewind();
        static::assertEquals('Drinks', $tree->current()->getTitle());
        $tree->next();
        static::assertEquals('Whisky', $tree->current()->getTitle());
        $tree->next();
        static::assertEquals('Best Whisky', $tree->current()->getTitle());
        $tree->next();
        static::assertEquals('Food', $tree->current()->getTitle());
        $tree->next();
        static::assertEquals('Fruits', $tree->current()->getTitle());
        $tree->next();
        static::assertEquals('Vegitables', $tree->current()->getTitle());
        $tree->next();
        static::assertEquals('Carrots', $tree->current()->getTitle());
        $tree->next();
        static::assertEquals('Potatoes', $tree->current()->getTitle());
        $tree->next();
        static::assertEquals('Sports', $tree->current()->getTitle());

        // Get a specific tree
        $roots = $this->repo->getRootNodes();
        $tree = $this->repo->getTree($roots->current());

        static::assertEquals(3, \iterator_count($tree));
        $tree->rewind();
        static::assertEquals('Drinks', $tree->current()->getTitle());
        $tree->next();
        static::assertEquals('Whisky', $tree->current()->getTitle());
        $tree->next();
        static::assertEquals('Best Whisky', $tree->current()->getTitle());
    }

    /**
     * @test
     */
    public function childrenHierarchy()
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

        // Tree of one specific root
        $roots = $this->repo->getRootNodes();
        $drinks = $roots->current();
        $roots->next();
        $food = $roots->current();
        $roots->next();
        $tree = $this->repo->childrenHierarchy();

        static::assertEquals('Drinks', $tree[0]['title']);
        static::assertEquals('Whisky', $tree[0]['__children'][0]['title']);
        static::assertEquals('Best Whisky', $tree[0]['__children'][0]['__children'][0]['title']);

        // Tree of one specific root, with the root node
        $tree = $this->repo->childrenHierarchy($drinks, false, [], true);

        static::assertEquals('Drinks', $tree[0]['title']);
        static::assertEquals('Whisky', $tree[0]['__children'][0]['title']);
        static::assertEquals('Best Whisky', $tree[0]['__children'][0]['__children'][0]['title']);

        // Tree of one specific root only with direct children, without the root node
        $roots = $this->repo->getRootNodes();
        $tree = $this->repo->childrenHierarchy($food, true);

        static::assertCount(2, $tree);
        static::assertEquals('Fruits', $tree[0]['title']);
        static::assertEquals('Vegitables', $tree[1]['title']);

        // Tree of one specific root only with direct children, with the root node
        $tree = $this->repo->childrenHierarchy($food, true, [], true);

        static::assertCount(1, $tree);
        static::assertCount(2, $tree[0]['__children']);
        static::assertEquals('Food', $tree[0]['title']);
        static::assertEquals('Fruits', $tree[0]['__children'][0]['title']);
        static::assertEquals('Vegitables', $tree[0]['__children'][1]['title']);

        // HTML Tree of one specific root, without the root node
        $roots = $this->repo->getRootNodes();
        $tree = $this->repo->childrenHierarchy($drinks, false, ['decorate' => true], false);

        static::assertEquals('<ul><li>Whisky<ul><li>Best Whisky</li></ul></li></ul>', $tree);

        // HTML Tree of one specific root, with the root node
        $roots = $this->repo->getRootNodes();
        $tree = $this->repo->childrenHierarchy($drinks, false, ['decorate' => true], true);

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
        ];
    }

    public function createCategory()
    {
        $class = self::CATEGORY;

        return new $class();
    }

    private function populate()
    {
        $root = $this->createCategory();
        $root->setTitle('Food');

        $root2 = $this->createCategory();
        $root2->setTitle('Sports');

        $child = $this->createCategory();
        $child->setTitle('Fruits');
        $child->setParent($root);

        $child2 = $this->createCategory();
        $child2->setTitle('Vegitables');
        $child2->setParent($root);

        $childsChild = $this->createCategory();
        $childsChild->setTitle('Carrots');
        $childsChild->setParent($child2);

        $potatoes = $this->createCategory();
        $potatoes->setTitle('Potatoes');
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
