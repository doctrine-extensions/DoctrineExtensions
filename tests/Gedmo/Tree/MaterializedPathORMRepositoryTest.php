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
use Gedmo\Exception\InvalidArgumentException;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Tests\Tree\Fixture\MPCategory;
use Gedmo\Tests\Tree\Fixture\MPCategoryWithTrimmedSeparator;
use Gedmo\Tree\Entity\Repository\MaterializedPathRepository;
use Gedmo\Tree\TreeListener;

/**
 * These are tests for Tree behavior
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class MaterializedPathORMRepositoryTest extends BaseTestCaseORM
{
    public const CATEGORY = MPCategory::class;
    public const CATEGORY_WITH_TRIMMED_SEPARATOR = MPCategoryWithTrimmedSeparator::class;

    /** @var MaterializedPathRepository */
    protected $repo;

    /**
     * @var TreeListener
     */
    private $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new TreeListener();

        $evm = new EventManager();
        $evm->addEventSubscriber($this->listener);

        $this->getDefaultMockSqliteEntityManager($evm);

        $meta = $this->em->getClassMetadata(self::CATEGORY);
        $this->listener->getConfiguration($this->em, $meta->getName());
        $this->populate();

        $this->repo = $this->em->getRepository(self::CATEGORY);
    }

    public function testGetRootNodes(): void
    {
        $result = $this->repo->getRootNodes('title');

        static::assertCount(3, $result);
        static::assertSame('Drinks', $result[0]->getTitle());
        static::assertSame('Food', $result[1]->getTitle());
        static::assertSame('Sports', $result[2]->getTitle());
    }

    public function testGetPath(): void
    {
        $childNode = $this->repo->findOneBy(['title' => 'Carrots']);

        $result = $this->repo->getPath($childNode);

        static::assertCount(3, $result);
        static::assertSame('Food', $result[0]->getTitle());
        static::assertSame('Vegitables', $result[1]->getTitle());
        static::assertSame('Carrots', $result[2]->getTitle());

        $rootNode = $this->repo->findOneBy(['title' => 'Sports']);

        $result = $this->repo->getPath($rootNode);

        static::assertCount(1, $result);
        static::assertSame('Sports', $result[0]->getTitle());
    }

    public function testGetChildren(): void
    {
        $root = $this->repo->findOneBy(['title' => 'Food']);

        // Get all children from the root, NOT including it
        $result = $this->repo->getChildren($root, false, 'title');

        static::assertCount(4, $result);
        static::assertSame('Carrots', $result[0]->getTitle());
        static::assertSame('Fruits', $result[1]->getTitle());
        static::assertSame('Potatoes', $result[2]->getTitle());
        static::assertSame('Vegitables', $result[3]->getTitle());

        // Get all children from the root, including it
        $result = $this->repo->getChildren($root, false, 'title', 'asc', true);

        static::assertCount(5, $result);
        static::assertSame('Carrots', $result[0]->getTitle());
        static::assertSame('Food', $result[1]->getTitle());
        static::assertSame('Fruits', $result[2]->getTitle());
        static::assertSame('Potatoes', $result[3]->getTitle());
        static::assertSame('Vegitables', $result[4]->getTitle());

        // Get direct children from the root, NOT including it
        $result = $this->repo->getChildren($root, true, 'title', 'asc');

        static::assertCount(2, $result);
        static::assertSame('Fruits', $result[0]->getTitle());
        static::assertSame('Vegitables', $result[1]->getTitle());

        // Get direct children from the root, including it
        $result = $this->repo->getChildren($root, true, 'title', 'asc', true);

        static::assertCount(3, $result);
        static::assertSame('Food', $result[0]->getTitle());
        static::assertSame('Fruits', $result[1]->getTitle());
        static::assertSame('Vegitables', $result[2]->getTitle());

        // Get ALL nodes
        $result = $this->repo->getChildren(null, false, 'title');

        static::assertCount(9, $result);
        static::assertSame('Best Whisky', $result[0]->getTitle());
        static::assertSame('Carrots', $result[1]->getTitle());
        static::assertSame('Drinks', $result[2]->getTitle());
        static::assertSame('Food', $result[3]->getTitle());
        static::assertSame('Fruits', $result[4]->getTitle());
        static::assertSame('Potatoes', $result[5]->getTitle());
        static::assertSame('Sports', $result[6]->getTitle());
        static::assertSame('Vegitables', $result[7]->getTitle());
        static::assertSame('Whisky', $result[8]->getTitle());

        // Get ALL root nodes
        $result = $this->repo->getChildren(null, true, 'title');

        static::assertCount(3, $result);
        static::assertSame('Drinks', $result[0]->getTitle());
        static::assertSame('Food', $result[1]->getTitle());
        static::assertSame('Sports', $result[2]->getTitle());
    }

    public function testGetChildrenForEntityWithTrimmedSeparators(): void
    {
        $meta = $this->em->getClassMetadata(self::CATEGORY_WITH_TRIMMED_SEPARATOR);
        $this->populate(self::CATEGORY_WITH_TRIMMED_SEPARATOR);

        $this->repo = $this->em->getRepository(self::CATEGORY_WITH_TRIMMED_SEPARATOR);
        $root = $this->repo->findOneBy(['title' => 'Food']);

        // Get all children from the root, NOT including it
        $result = $this->repo->getChildren($root, false, 'title');

        static::assertCount(4, $result);
        static::assertSame('Carrots', $result[0]->getTitle());
        static::assertSame('Fruits', $result[1]->getTitle());
        static::assertSame('Potatoes', $result[2]->getTitle());
        static::assertSame('Vegitables', $result[3]->getTitle());

        // Get all children from the root, including it
        $result = $this->repo->getChildren($root, false, 'title', 'asc', true);

        static::assertCount(5, $result);
        static::assertSame('Carrots', $result[0]->getTitle());
        static::assertSame('Food', $result[1]->getTitle());
        static::assertSame('Fruits', $result[2]->getTitle());
        static::assertSame('Potatoes', $result[3]->getTitle());
        static::assertSame('Vegitables', $result[4]->getTitle());

        // Get direct children from the root, NOT including it
        $result = $this->repo->getChildren($root, true, 'title', 'asc');
        static::assertCount(2, $result);
        static::assertSame('Fruits', $result[0]->getTitle());
        static::assertSame('Vegitables', $result[1]->getTitle());

        // Get direct children from the root, including it
        $result = $this->repo->getChildren($root, true, 'title', 'asc', true);

        static::assertCount(3, $result);
        static::assertSame('Food', $result[0]->getTitle());
        static::assertSame('Fruits', $result[1]->getTitle());
        static::assertSame('Vegitables', $result[2]->getTitle());

        // Get ALL nodes
        $result = $this->repo->getChildren(null, false, 'title');

        static::assertCount(9, $result);
        static::assertSame('Best Whisky', $result[0]->getTitle());
        static::assertSame('Carrots', $result[1]->getTitle());
        static::assertSame('Drinks', $result[2]->getTitle());
        static::assertSame('Food', $result[3]->getTitle());
        static::assertSame('Fruits', $result[4]->getTitle());
        static::assertSame('Potatoes', $result[5]->getTitle());
        static::assertSame('Sports', $result[6]->getTitle());
        static::assertSame('Vegitables', $result[7]->getTitle());
        static::assertSame('Whisky', $result[8]->getTitle());

        // Get ALL root nodes
        $result = $this->repo->getChildren(null, true, 'title');

        static::assertCount(3, $result);
        static::assertSame('Drinks', $result[0]->getTitle());
        static::assertSame('Food', $result[1]->getTitle());
        static::assertSame('Sports', $result[2]->getTitle());
    }

    public function testGetTree(): void
    {
        $tree = $this->repo->getTree();

        static::assertCount(9, $tree);
        static::assertSame('Drinks', $tree[0]->getTitle());
        static::assertSame('Whisky', $tree[1]->getTitle());
        static::assertSame('Best Whisky', $tree[2]->getTitle());
        static::assertSame('Food', $tree[3]->getTitle());
        static::assertSame('Fruits', $tree[4]->getTitle());
        static::assertSame('Vegitables', $tree[5]->getTitle());
        static::assertSame('Carrots', $tree[6]->getTitle());
        static::assertSame('Potatoes', $tree[7]->getTitle());
        static::assertSame('Sports', $tree[8]->getTitle());

        // Get tree from a specific root node
        $roots = $this->repo->getRootNodes();
        $tree = $this->repo->getTree($roots[0]);

        static::assertCount(3, $tree);
        static::assertSame('Drinks', $tree[0]->getTitle());
        static::assertSame('Whisky', $tree[1]->getTitle());
        static::assertSame('Best Whisky', $tree[2]->getTitle());
    }

    public function testChildrenHierarchyMethod(): void
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

        // Tree of one specific root, without the root node
        $roots = $this->repo->getRootNodes();
        $tree = $this->repo->childrenHierarchy($roots[0]);

        static::assertSame('Whisky', $tree[0]['title']);
        static::assertSame('Best Whisky', $tree[0]['__children'][0]['title']);

        // Tree of one specific root, with the root node
        $tree = $this->repo->childrenHierarchy($roots[0], false, [], true);

        static::assertSame('Drinks', $tree[0]['title']);
        static::assertSame('Whisky', $tree[0]['__children'][0]['title']);
        static::assertSame('Best Whisky', $tree[0]['__children'][0]['__children'][0]['title']);

        // Tree of one specific root only with direct children, without the root node
        $roots = $this->repo->getRootNodes();
        $tree = $this->repo->childrenHierarchy($roots[1], true);

        static::assertCount(2, $tree);
        static::assertSame('Fruits', $tree[0]['title']);
        static::assertSame('Vegitables', $tree[1]['title']);

        // Tree of one specific root only with direct children, with the root node
        $tree = $this->repo->childrenHierarchy($roots[1], true, [], true);

        static::assertCount(1, $tree);
        static::assertCount(2, $tree[0]['__children']);
        static::assertSame('Food', $tree[0]['title']);
        static::assertSame('Fruits', $tree[0]['__children'][0]['title']);
        static::assertSame('Vegitables', $tree[0]['__children'][1]['title']);

        // HTML Tree of one specific root, without the root node
        $roots = $this->repo->getRootNodes();
        $tree = $this->repo->childrenHierarchy($roots[0], false, ['decorate' => true], false);

        static::assertSame('<ul><li>Whisky<ul><li>Best Whisky</li></ul></li></ul>', $tree);

        // HTML Tree of one specific root, with the root node
        $roots = $this->repo->getRootNodes();
        $tree = $this->repo->childrenHierarchy($roots[0], false, ['decorate' => true], true);

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

    public function testIssue458(): void
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
        static::assertSame(2, $newNode->getLevel());
    }

    public function testChangeChildrenIndex(): void
    {
        $childrenIndex = 'myChildren';
        $this->repo->setChildrenIndex($childrenIndex);

        $tree = $this->repo->childrenHierarchy();

        static::assertIsArray($tree[0][$childrenIndex]);
    }

    public function createCategory($class = null)
    {
        if (!$class) {
            $class = self::CATEGORY;
        }

        return new $class();
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::CATEGORY,
            self::CATEGORY_WITH_TRIMMED_SEPARATOR,
        ];
    }

    private function populate(string $class = null): void
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
