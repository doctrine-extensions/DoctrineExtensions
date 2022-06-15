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
use Doctrine\ODM\MongoDB\Iterator\CachingIterator;
use Doctrine\ODM\MongoDB\Iterator\Iterator;
use Gedmo\Exception\InvalidArgumentException;
use Gedmo\Tests\Tool\BaseTestCaseMongoODM;
use Gedmo\Tests\Tree\Fixture\Document\Category;
use Gedmo\Tree\Document\MongoDB\Repository\MaterializedPathRepository;
use Gedmo\Tree\TreeListener;

/**
 * These are tests for Tree behavior
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class MaterializedPathODMMongoDBRepositoryTest extends BaseTestCaseMongoODM
{
    private const CATEGORY = Category::class;

    /**
     * @var MaterializedPathRepository
     */
    protected $repo;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new TreeListener());

        $this->getDefaultDocumentManager($evm);
        $this->populate();

        $this->repo = $this->dm->getRepository(self::CATEGORY);
    }

    public function testGetRootNodes(): void
    {
        /** @var CachingIterator $result */
        $result = $this->repo->getRootNodes('title');

        static::assertSame(3, \iterator_count($result));
        $result->rewind();

        $result->rewind();

        static::assertSame('Drinks', $result->current()->getTitle());
        $result->next();
        static::assertSame('Food', $result->current()->getTitle());
        $result->next();
        static::assertSame('Sports', $result->current()->getTitle());
    }

    public function testGetChildren(): void
    {
        $root = $this->repo->findOneBy(['title' => 'Food']);

        // Get all children from the root, including it
        /** @var CachingIterator $result */
        $result = $this->repo->getChildren($root, false, 'title', 'asc', true);

        static::assertSame(5, \iterator_count($result));
        $result->rewind();

        $result->rewind();
        static::assertSame('Carrots', $result->current()->getTitle());
        $result->next();
        static::assertSame('Food', $result->current()->getTitle());
        $result->next();
        static::assertSame('Fruits', $result->current()->getTitle());
        $result->next();
        static::assertSame('Potatoes', $result->current()->getTitle());
        $result->next();
        static::assertSame('Vegitables', $result->current()->getTitle());

        // Get all children from the root, NOT including it
        /** @var CachingIterator $result */
        $result = $this->repo->getChildren($root, false, 'title', 'asc', false);

        static::assertSame(4, \iterator_count($result));
        $result->rewind();
        $result->rewind();
        static::assertSame('Carrots', $result->current()->getTitle());
        $result->next();
        static::assertSame('Fruits', $result->current()->getTitle());
        $result->next();
        static::assertSame('Potatoes', $result->current()->getTitle());
        $result->next();
        static::assertSame('Vegitables', $result->current()->getTitle());

        // Get direct children from the root, including it
        /** @var CachingIterator $result */
        $result = $this->repo->getChildren($root, true, 'title', 'asc', true);

        static::assertSame(3, \iterator_count($result));
        $result->rewind();
        $result->rewind();
        static::assertSame('Food', $result->current()->getTitle());
        $result->next();
        static::assertSame('Fruits', $result->current()->getTitle());
        $result->next();
        static::assertSame('Vegitables', $result->current()->getTitle());

        // Get direct children from the root, NOT including it
        /** @var CachingIterator $result */
        $result = $this->repo->getChildren($root, true, 'title', 'asc', false);
        static::assertInstanceOf(Iterator::class, $result);

        static::assertSame(2, \iterator_count($result));
        $result->rewind();
        static::assertSame('Fruits', $result->current()->getTitle());
        $result->next();
        static::assertSame('Vegitables', $result->current()->getTitle());

        // Get ALL nodes
        $result = $this->repo->getChildren(null, false, 'title');
        static::assertInstanceOf(Iterator::class, $result);

        static::assertSame(9, \iterator_count($result));
        $result->rewind();
        static::assertSame('Best Whisky', $result->current()->getTitle());
        $result->next();
        static::assertSame('Carrots', $result->current()->getTitle());
        $result->next();
        static::assertSame('Drinks', $result->current()->getTitle());
        $result->next();
        static::assertSame('Food', $result->current()->getTitle());
        $result->next();
        static::assertSame('Fruits', $result->current()->getTitle());
        $result->next();
        static::assertSame('Potatoes', $result->current()->getTitle());
        $result->next();
        static::assertSame('Sports', $result->current()->getTitle());
        $result->next();
        static::assertSame('Vegitables', $result->current()->getTitle());
        $result->next();
        static::assertSame('Whisky', $result->current()->getTitle());

        // Get ALL root nodes
        $result = $this->repo->getChildren(null, true, 'title');
        static::assertInstanceOf(Iterator::class, $result);

        static::assertSame(3, \iterator_count($result));
        $result->rewind();
        static::assertSame('Drinks', $result->current()->getTitle());
        $result->next();
        static::assertSame('Food', $result->current()->getTitle());
        $result->next();
        static::assertSame('Sports', $result->current()->getTitle());
    }

    public function testGetTree(): void
    {
        $tree = $this->repo->getTree();

        static::assertSame(9, \iterator_count($tree));
        $tree->rewind();
        static::assertSame('Drinks', $tree->current()->getTitle());
        $tree->next();
        static::assertSame('Whisky', $tree->current()->getTitle());
        $tree->next();
        static::assertSame('Best Whisky', $tree->current()->getTitle());
        $tree->next();
        static::assertSame('Food', $tree->current()->getTitle());
        $tree->next();
        static::assertSame('Fruits', $tree->current()->getTitle());
        $tree->next();
        static::assertSame('Vegitables', $tree->current()->getTitle());
        $tree->next();
        static::assertSame('Carrots', $tree->current()->getTitle());
        $tree->next();
        static::assertSame('Potatoes', $tree->current()->getTitle());
        $tree->next();
        static::assertSame('Sports', $tree->current()->getTitle());

        // Get a specific tree
        $roots = $this->repo->getRootNodes();
        static::assertInstanceOf(Iterator::class, $roots);
        $tree = $this->repo->getTree($roots->current());

        static::assertSame(3, \iterator_count($tree));
        $tree->rewind();
        static::assertSame('Drinks', $tree->current()->getTitle());
        $tree->next();
        static::assertSame('Whisky', $tree->current()->getTitle());
        $tree->next();
        static::assertSame('Best Whisky', $tree->current()->getTitle());
    }

    public function testChildrenHierarchy(): void
    {
        $tree = $this->repo->childrenHierarchy();

        static::assertSame('Drinks', $tree[0]['title']);
        static::assertSame('Whisky', $tree[0]['__children'][0]['title']);
        static::assertSame('Best Whisky', $tree[0]['__children'][0]['__children'][0]['title']);
        $vegitablesChildren = $tree[1]['__children'][1]['__children'];
        static::assertSame('Food', $tree[1]['title']);
        static::assertSame('Fruits', $tree[1]['__children'][0]['title']);
        static::assertSame('Vegitables', $tree[1]['__children'][1]['title']);
        static::assertSame('Carrots', $vegitablesChildren[0]['title']);
        static::assertSame('Potatoes', $vegitablesChildren[1]['title']);
        static::assertSame('Sports', $tree[2]['title']);

        // Tree of one specific root
        $roots = $this->repo->getRootNodes();
        static::assertInstanceOf(Iterator::class, $roots);
        $drinks = $roots->current();
        $roots->next();
        $food = $roots->current();
        $roots->next();
        $tree = $this->repo->childrenHierarchy();

        static::assertSame('Drinks', $tree[0]['title']);
        static::assertSame('Whisky', $tree[0]['__children'][0]['title']);
        static::assertSame('Best Whisky', $tree[0]['__children'][0]['__children'][0]['title']);

        // Tree of one specific root, with the root node
        $tree = $this->repo->childrenHierarchy($drinks, false, [], true);

        static::assertSame('Drinks', $tree[0]['title']);
        static::assertSame('Whisky', $tree[0]['__children'][0]['title']);
        static::assertSame('Best Whisky', $tree[0]['__children'][0]['__children'][0]['title']);

        // Tree of one specific root only with direct children, without the root node
        $roots = $this->repo->getRootNodes();
        $tree = $this->repo->childrenHierarchy($food, true);

        static::assertCount(2, $tree);
        static::assertSame('Fruits', $tree[0]['title']);
        static::assertSame('Vegitables', $tree[1]['title']);

        // Tree of one specific root only with direct children, with the root node
        $tree = $this->repo->childrenHierarchy($food, true, [], true);

        static::assertCount(1, $tree);
        static::assertCount(2, $tree[0]['__children']);
        static::assertSame('Food', $tree[0]['title']);
        static::assertSame('Fruits', $tree[0]['__children'][0]['title']);
        static::assertSame('Vegitables', $tree[0]['__children'][1]['title']);

        // HTML Tree of one specific root, without the root node
        $roots = $this->repo->getRootNodes();
        $tree = $this->repo->childrenHierarchy($drinks, false, ['decorate' => true], false);

        static::assertSame('<ul><li>Whisky<ul><li>Best Whisky</li></ul></li></ul>', $tree);

        // HTML Tree of one specific root, with the root node
        $roots = $this->repo->getRootNodes();
        $tree = $this->repo->childrenHierarchy($drinks, false, ['decorate' => true], true);

        static::assertSame('<ul><li>Drinks<ul><li>Whisky<ul><li>Best Whisky</li></ul></li></ul></li></ul>', $tree);
    }

    public function testChildCount(): void
    {
        // Count all
        $count = $this->repo->childCount();

        static::assertSame(9, $count);

        // Count all, but only direct ones
        $count = $this->repo->childCount(null, true);

        static::assertSame(3, $count);

        // Count food children
        $food = $this->repo->findOneBy(['title' => 'Food']);
        $count = $this->repo->childCount($food);

        static::assertSame(4, $count);

        // Count food children, but only direct ones
        $count = $this->repo->childCount($food, true);

        static::assertSame(2, $count);
    }

    public function testChildCountIfAnObjectIsPassedWhichIsNotAnInstanceOfTheEntityClassThrowException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->repo->childCount(new \DateTime());
    }

    public function testChildCountIfAnObjectIsPassedIsAnInstanceOfTheEntityClassButIsNotHandledByUnitOfWorkThrowException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->repo->childCount($this->createCategory());
    }

    public function testChangeChildrenIndex(): void
    {
        $childrenIndex = 'myChildren';
        $this->repo->setChildrenIndex($childrenIndex);

        $tree = $this->repo->childrenHierarchy();

        static::assertIsArray($tree[0][$childrenIndex]);
    }

    public function createCategory(): Category
    {
        $class = self::CATEGORY;

        return new $class();
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::CATEGORY,
        ];
    }

    private function populate(): void
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
