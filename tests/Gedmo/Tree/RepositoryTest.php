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
use Gedmo\Tests\Tree\Fixture\Category;
use Gedmo\Tests\Tree\Fixture\CategoryUuid;
use Gedmo\Tree\TreeListener;

/**
 * These are tests for Tree behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class RepositoryTest extends BaseTestCaseORM
{
    public const CATEGORY = Category::class;
    public const CATEGORY_UUID = CategoryUuid::class;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new TreeListener());

        $this->getDefaultMockSqliteEntityManager($evm);
        $this->populate();
    }

    public function testBasicFunctions(): void
    {
        $vegies = $this->em->getRepository(self::CATEGORY)
            ->findOneBy(['title' => 'Vegitables']);

        $food = $this->em->getRepository(self::CATEGORY)
            ->findOneBy(['title' => 'Food']);

        // test childCount

        $childCount = $this->em->getRepository(self::CATEGORY)
            ->childCount($vegies);
        static::assertSame(2, $childCount);

        $childCount = $this->em->getRepository(self::CATEGORY)
            ->childCount($food);
        static::assertSame(4, $childCount);

        $childCount = $this->em->getRepository(self::CATEGORY)
            ->childCount($food, true);
        static::assertSame(2, $childCount);

        $childCount = $this->em->getRepository(self::CATEGORY)
            ->childCount();
        static::assertSame(6, $childCount);

        // test children

        $children = $this->em->getRepository(self::CATEGORY)
            ->children($vegies);

        static::assertCount(2, $children);
        static::assertSame('Carrots', $children[0]->getTitle());
        static::assertSame('Potatoes', $children[1]->getTitle());

        $children = $this->em->getRepository(self::CATEGORY)
            ->children($food);

        static::assertCount(4, $children);
        static::assertSame('Fruits', $children[0]->getTitle());
        static::assertSame('Vegitables', $children[1]->getTitle());
        static::assertSame('Carrots', $children[2]->getTitle());
        static::assertSame('Potatoes', $children[3]->getTitle());

        $children = $this->em->getRepository(self::CATEGORY)
            ->children($food, true);

        static::assertCount(2, $children);
        static::assertSame('Fruits', $children[0]->getTitle());
        static::assertSame('Vegitables', $children[1]->getTitle());

        $children = $this->em->getRepository(self::CATEGORY)
            ->children();

        static::assertCount(6, $children);

        // test children sorting

        $children = $this->em->getRepository(self::CATEGORY)
             ->children($food, true, ['title'], 'ASC');

        static::assertCount(2, $children);
        static::assertSame('Fruits', $children[0]->getTitle());
        static::assertSame('Vegitables', $children[1]->getTitle());

        $children = $this->em->getRepository(self::CATEGORY)
             ->children($food, false, ['level', 'title'], ['ASC', 'DESC']);

        static::assertCount(4, $children);
        static::assertSame('Vegitables', $children[0]->getTitle());
        static::assertSame('Fruits', $children[1]->getTitle());
        static::assertSame('Potatoes', $children[2]->getTitle());
        static::assertSame('Carrots', $children[3]->getTitle());

        $children = $this->em->getRepository(self::CATEGORY)
             ->children($food, false, ['level', 'title'], ['ASC']);

        static::assertCount(4, $children);
        static::assertSame('Fruits', $children[0]->getTitle());
        static::assertSame('Vegitables', $children[1]->getTitle());
        static::assertSame('Carrots', $children[2]->getTitle());
        static::assertSame('Potatoes', $children[3]->getTitle());

        // test sorting by single-valued association field
        $children = $this->em->getRepository(self::CATEGORY)
            ->children($food, false, 'parentId');

        static::assertCount(4, $children);
        static::assertSame('Fruits', $children[0]->getTitle());
        static::assertSame('Vegitables', $children[1]->getTitle());
        static::assertSame('Carrots', $children[2]->getTitle());
        static::assertSame('Potatoes', $children[3]->getTitle());

        $children = $this->em->getRepository(self::CATEGORY)
            ->children($food, false, ['parentId'], ['ASC']);

        static::assertCount(4, $children);
        static::assertSame('Fruits', $children[0]->getTitle());
        static::assertSame('Vegitables', $children[1]->getTitle());
        static::assertSame('Carrots', $children[2]->getTitle());
        static::assertSame('Potatoes', $children[3]->getTitle());

        // path

        $path = $this->em->getRepository(self::CATEGORY)
            ->getPath($vegies);

        static::assertCount(2, $path);
        static::assertSame('Food', $path[0]->getTitle());
        static::assertSame('Vegitables', $path[1]->getTitle());

        $carrots = $this->em->getRepository(self::CATEGORY)
            ->findOneBy(['title' => 'Carrots']);

        $path = $this->em->getRepository(self::CATEGORY)
            ->getPath($carrots);

        static::assertCount(3, $path);
        static::assertSame('Food', $path[0]->getTitle());
        static::assertSame('Vegitables', $path[1]->getTitle());
        static::assertSame('Carrots', $path[2]->getTitle());

        // leafs

        $leafs = $this->em->getRepository(self::CATEGORY)
            ->getLeafs();

        static::assertCount(4, $leafs);
        static::assertSame('Fruits', $leafs[0]->getTitle());
        static::assertSame('Carrots', $leafs[1]->getTitle());
        static::assertSame('Potatoes', $leafs[2]->getTitle());
        static::assertSame('Sports', $leafs[3]->getTitle());
    }

    public function testAdvancedFunctions(): void
    {
        $this->populateMore();
        $onions = $this->em->getRepository(self::CATEGORY)
            ->findOneBy(['title' => 'Onions']);
        $repo = $this->em->getRepository(self::CATEGORY);
        $meta = $this->em->getClassMetadata(self::CATEGORY);

        $left = $meta->getReflectionProperty('lft')->getValue($onions);
        $right = $meta->getReflectionProperty('rgt')->getValue($onions);

        static::assertSame(11, $left);
        static::assertSame(12, $right);

        // move up onions by one position

        $repo->moveUp($onions, 1);

        $left = $meta->getReflectionProperty('lft')->getValue($onions);
        $right = $meta->getReflectionProperty('rgt')->getValue($onions);

        static::assertSame(9, $left);
        static::assertSame(10, $right);

        // move down onions by one position
        $repo->moveDown($onions, 1);

        $left = $meta->getReflectionProperty('lft')->getValue($onions);
        $right = $meta->getReflectionProperty('rgt')->getValue($onions);

        static::assertSame(11, $left);
        static::assertSame(12, $right);

        // move to the up onions on this level

        $repo->moveUp($onions, true);

        $left = $meta->getReflectionProperty('lft')->getValue($onions);
        $right = $meta->getReflectionProperty('rgt')->getValue($onions);

        static::assertSame(5, $left);
        static::assertSame(6, $right);

        // test tree reordering
        // reorder tree by title

        $food = $repo->findOneBy(['title' => 'Food']);
        $repo->reorder($food, 'title');

        $node = $this->em->getRepository(self::CATEGORY)
            ->findOneBy(['title' => 'Cabbages']);
        $left = $meta->getReflectionProperty('lft')->getValue($node);
        $right = $meta->getReflectionProperty('rgt')->getValue($node);

        static::assertSame(5, $left);
        static::assertSame(6, $right);

        $node = $this->em->getRepository(self::CATEGORY)
            ->findOneBy(['title' => 'Carrots']);
        $left = $meta->getReflectionProperty('lft')->getValue($node);
        $right = $meta->getReflectionProperty('rgt')->getValue($node);

        static::assertSame(7, $left);
        static::assertSame(8, $right);

        $node = $this->em->getRepository(self::CATEGORY)
            ->findOneBy(['title' => 'Onions']);
        $left = $meta->getReflectionProperty('lft')->getValue($node);
        $right = $meta->getReflectionProperty('rgt')->getValue($node);

        static::assertSame(9, $left);
        static::assertSame(10, $right);

        $node = $this->em->getRepository(self::CATEGORY)
            ->findOneBy(['title' => 'Potatoes']);
        $left = $meta->getReflectionProperty('lft')->getValue($node);
        $right = $meta->getReflectionProperty('rgt')->getValue($node);

        static::assertSame(11, $left);
        static::assertSame(12, $right);

        // test removal with reparenting

        $vegies = $this->em->getRepository(self::CATEGORY)
            ->findOneBy(['title' => 'Vegitables']);

        $repo->removeFromTree($vegies);

        $this->em->clear(); // clear all cached nodes

        $vegies = $this->em->getRepository(self::CATEGORY)
            ->findOneBy(['title' => 'Vegitables']);

        static::assertNull($vegies);

        $node = $this->em->getRepository(self::CATEGORY)
            ->findOneBy(['title' => 'Fruits']);
        $left = $meta->getReflectionProperty('lft')->getValue($node);
        $right = $meta->getReflectionProperty('rgt')->getValue($node);

        static::assertSame(2, $left);
        static::assertSame(3, $right);
        static::assertSame('Food', $node->getParent()->getTitle());

        $node = $this->em->getRepository(self::CATEGORY)
            ->findOneBy(['title' => 'Cabbages']);
        $left = $meta->getReflectionProperty('lft')->getValue($node);
        $right = $meta->getReflectionProperty('rgt')->getValue($node);

        static::assertSame(4, $left);
        static::assertSame(5, $right);
        static::assertSame('Food', $node->getParent()->getTitle());
    }

    public function testRootRemoval(): void
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $meta = $this->em->getClassMetadata(self::CATEGORY);
        $this->populateMore();

        $food = $repo->findOneBy(['title' => 'Food']);
        $repo->removeFromTree($food);
        $this->em->clear();

        $food = $repo->findOneBy(['title' => 'Food']);
        static::assertNull($food);

        $node = $repo->findOneBy(['title' => 'Fruits']);
        $left = $meta->getReflectionProperty('lft')->getValue($node);
        $right = $meta->getReflectionProperty('rgt')->getValue($node);

        static::assertSame(1, $left);
        static::assertSame(2, $right);
        static::assertNull($node->getParent());

        $node = $repo->findOneBy(['title' => 'Vegitables']);
        $left = $meta->getReflectionProperty('lft')->getValue($node);
        $right = $meta->getReflectionProperty('rgt')->getValue($node);

        static::assertSame(3, $left);
        static::assertSame(12, $right);
        static::assertNull($node->getParent());
    }

    public function testVerificationAndRecover(): void
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $meta = $this->em->getClassMetadata(self::CATEGORY);
        $this->populateMore();
        // test verification of tree

        static::assertTrue($repo->verify());

        // now lets brake something

        $dql = 'UPDATE '.self::CATEGORY.' node';
        $dql .= ' SET node.lft = 1';
        $dql .= ' WHERE node.id = 8';
        $q = $this->em->createQuery($dql);
        $q->getSingleScalarResult();

        $this->em->clear(); // must clear cached entities

        // verify again

        $result = $repo->verify();
        static::assertIsArray($result);

        static::assertArrayHasKey(0, $result);
        static::assertArrayHasKey(1, $result);
        static::assertArrayHasKey(2, $result);

        $duplicate = $result[0];
        $missing = $result[1];
        $invalidLeft = $result[2];

        static::assertSame('index [1], duplicate', $duplicate);
        static::assertSame('index [11], missing', $missing);
        static::assertSame('node [8] left is less than parent`s [4] left value', $invalidLeft);

        // test recover functionality
        $repo->recover();
        $this->em->flush();

        static::assertTrue($repo->verify());
    }

    public function testMoveRootNode(): void
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $food = $repo->findOneBy(['title' => 'Food']);

        $repo->moveDown($food, 1);

        $meta = $this->em->getClassMetadata(self::CATEGORY);

        $left = $meta->getReflectionProperty('lft')->getValue($food);
        $right = $meta->getReflectionProperty('rgt')->getValue($food);

        static::assertSame(3, $left);
        static::assertSame(12, $right);
        static::assertNull($food->getParent());

        static::assertTrue($repo->verify());
    }

    public function testIssue273(): void
    {
        $this->populateUuid();

        $vegies = $this->em->getRepository(self::CATEGORY_UUID)
            ->findOneBy(['title' => 'Vegitables']);

        $food = $this->em->getRepository(self::CATEGORY_UUID)
            ->findOneBy(['title' => 'Food']);

        // test childCount

        $childCount = $this->em->getRepository(self::CATEGORY_UUID)
            ->childCount($vegies);
        static::assertSame(2, $childCount);

        $childCount = $this->em->getRepository(self::CATEGORY_UUID)
            ->childCount($food);
        static::assertSame(4, $childCount);

        $childCount = $this->em->getRepository(self::CATEGORY_UUID)
            ->childCount($food, true);
        static::assertSame(2, $childCount);

        $childCount = $this->em->getRepository(self::CATEGORY_UUID)
            ->childCount();
        static::assertSame(6, $childCount);

        // test children

        $children = $this->em->getRepository(self::CATEGORY_UUID)
            ->children($vegies);

        static::assertCount(2, $children);
        static::assertSame('Carrots', $children[0]->getTitle());
        static::assertSame('Potatoes', $children[1]->getTitle());

        $children = $this->em->getRepository(self::CATEGORY_UUID)
            ->children($food);

        static::assertCount(4, $children);
        static::assertSame('Fruits', $children[0]->getTitle());
        static::assertSame('Vegitables', $children[1]->getTitle());
        static::assertSame('Carrots', $children[2]->getTitle());
        static::assertSame('Potatoes', $children[3]->getTitle());

        $children = $this->em->getRepository(self::CATEGORY_UUID)
            ->children($food, true);

        static::assertCount(2, $children);
        static::assertSame('Fruits', $children[0]->getTitle());
        static::assertSame('Vegitables', $children[1]->getTitle());

        $children = $this->em->getRepository(self::CATEGORY_UUID)
            ->children();

        static::assertCount(6, $children);

        // path

        $path = $this->em->getRepository(self::CATEGORY_UUID)
            ->getPath($vegies);

        static::assertCount(2, $path);
        static::assertSame('Food', $path[0]->getTitle());
        static::assertSame('Vegitables', $path[1]->getTitle());

        $carrots = $this->em->getRepository(self::CATEGORY_UUID)
            ->findOneBy(['title' => 'Carrots']);

        $path = $this->em->getRepository(self::CATEGORY_UUID)
            ->getPath($carrots);

        static::assertCount(3, $path);
        static::assertSame('Food', $path[0]->getTitle());
        static::assertSame('Vegitables', $path[1]->getTitle());
        static::assertSame('Carrots', $path[2]->getTitle());

        // leafs

        $leafs = $this->em->getRepository(self::CATEGORY_UUID)
            ->getLeafs($path[0]);

        static::assertCount(3, $leafs);
        static::assertSame('Fruits', $leafs[0]->getTitle());
        static::assertSame('Carrots', $leafs[1]->getTitle());
        static::assertSame('Potatoes', $leafs[2]->getTitle());
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::CATEGORY,
            self::CATEGORY_UUID,
        ];
    }

    private function populateMore(): void
    {
        $vegies = $this->em->getRepository(self::CATEGORY)
            ->findOneBy(['title' => 'Vegitables']);

        $cabbages = new Category();
        $cabbages->setParent($vegies);
        $cabbages->setTitle('Cabbages');

        $onions = new Category();
        $onions->setParent($vegies);
        $onions->setTitle('Onions');

        $this->em->persist($cabbages);
        $this->em->persist($onions);
        $this->em->flush();
        $this->em->clear();
    }

    private function populate(): void
    {
        $root = new Category();
        $root->setTitle('Food');

        $root2 = new Category();
        $root2->setTitle('Sports');

        $child = new Category();
        $child->setTitle('Fruits');
        $child->setParent($root);

        $child2 = new Category();
        $child2->setTitle('Vegitables');
        $child2->setParent($root);

        $childsChild = new Category();
        $childsChild->setTitle('Carrots');
        $childsChild->setParent($child2);

        $potatoes = new Category();
        $potatoes->setTitle('Potatoes');
        $potatoes->setParent($child2);

        $this->em->persist($root);
        $this->em->persist($root2);
        $this->em->persist($child);
        $this->em->persist($child2);
        $this->em->persist($childsChild);
        $this->em->persist($potatoes);
        $this->em->flush();
        $this->em->clear();
    }

    private function populateUuid(): void
    {
        $root = new CategoryUuid();
        $root->setTitle('Food');

        $root2 = new CategoryUuid();
        $root2->setTitle('Sports');

        $child = new CategoryUuid();
        $child->setTitle('Fruits');
        $child->setParent($root);

        $child2 = new CategoryUuid();
        $child2->setTitle('Vegitables');
        $child2->setParent($root);

        $childsChild = new CategoryUuid();
        $childsChild->setTitle('Carrots');
        $childsChild->setParent($child2);

        $potatoes = new CategoryUuid();
        $potatoes->setTitle('Potatoes');
        $potatoes->setParent($child2);

        $this->em->persist($root);
        $this->em->persist($root2);
        $this->em->persist($child);
        $this->em->persist($child2);
        $this->em->persist($childsChild);
        $this->em->persist($potatoes);
        $this->em->flush();
        $this->em->clear();
    }
}
