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
use Gedmo\Tests\Tree\Fixture\RootCategory;
use Gedmo\Tree\TreeListener;

/**
 * These are tests for Tree behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class NestedTreePositionTest extends BaseTestCaseORM
{
    public const CATEGORY = Category::class;
    public const ROOT_CATEGORY = RootCategory::class;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new TreeListener());

        $this->getDefaultMockSqliteEntityManager($evm);
    }

    public function testShouldFailToPersistRootSibling(): void
    {
        $food = new Category();
        $food->setTitle('Food');

        $sport = new Category();
        $sport->setTitle('Sport');

        $repo = $this->em->getRepository(self::CATEGORY);

        $repo->persistAsFirstChild($food);
        $repo->persistAsNextSiblingOf($sport, $food);

        $this->em->flush();
        static::assertSame(0, $sport->getLevel());
        static::assertSame(3, $sport->getLeft());
        static::assertSame(4, $sport->getRight());
    }

    public function testShouldFailToPersistRootAsSiblingForRootBasedTree(): void
    {
        $this->expectException('UnexpectedValueException');
        $food = new RootCategory();
        $food->setTitle('Food');

        $sport = new RootCategory();
        $sport->setTitle('Sport');

        $repo = $this->em->getRepository(self::ROOT_CATEGORY);

        $repo->persistAsFirstChild($food);
        $repo->persistAsNextSiblingOf($sport, $food);

        $this->em->flush();
    }

    public function testTreeChildPositionMove2(): void
    {
        $this->populate();
        $repo = $this->em->getRepository(self::ROOT_CATEGORY);

        $oranges = $repo->findOneBy(['title' => 'Oranges']);
        $meat = $repo->findOneBy(['title' => 'Meat']);

        static::assertSame(2, $oranges->getLevel());
        static::assertSame(7, $oranges->getLeft());
        static::assertSame(8, $oranges->getRight());

        $repo->persistAsNextSiblingOf($meat, $oranges);
        $this->em->flush();

        $oranges = $repo->findOneBy(['title' => 'Oranges']);
        $meat = $repo->findOneBy(['title' => 'Meat']);

        static::assertSame(7, $oranges->getLeft());
        static::assertSame(8, $oranges->getRight());

        // Normal test that pass
        static::assertSame(9, $meat->getLeft());
        static::assertSame(10, $meat->getRight());

        // Raw query to show the issue #108 with wrong left value by Doctrine
        $dql = 'SELECT c FROM '.self::ROOT_CATEGORY.' c';
        $dql .= ' WHERE c.id = 5'; // 5 == meat
        $meat_array = $this->em->createQuery($dql)->getScalarResult();

        static::assertSame(9, $meat_array[0]['c_lft']);
        static::assertSame(10, $meat_array[0]['c_rgt']);
        static::assertSame(2, $meat_array[0]['c_level']);
    }

    public function testTreeChildPositionMove3(): void
    {
        $this->populate();
        $repo = $this->em->getRepository(self::ROOT_CATEGORY);

        $oranges = $repo->findOneBy(['title' => 'Oranges']);
        $milk = $repo->findOneBy(['title' => 'Milk']);

        static::assertSame(2, $oranges->getLevel());
        static::assertSame(7, $oranges->getLeft());
        static::assertSame(8, $oranges->getRight());

        $repo->persistAsNextSiblingOf($milk, $oranges);
        $this->em->flush();

        static::assertSame(7, $oranges->getLeft());
        static::assertSame(8, $oranges->getRight());

        // Normal test that pass
        static::assertSame(9, $milk->getLeft());
        static::assertSame(10, $milk->getRight());

        // Raw query to show the issue #108 with wrong left value by Doctrine
        $dql = 'SELECT c FROM '.self::ROOT_CATEGORY.' c';
        $dql .= ' WHERE c.id = 4 '; // 4 == Milk
        $milk_array = $this->em->createQuery($dql)->getScalarResult();
        static::assertSame(9, $milk_array[0]['c_lft']);
        static::assertSame(10, $milk_array[0]['c_rgt']);
        static::assertSame(2, $milk_array[0]['c_level']);
    }

    public function testPositionedUpdates(): void
    {
        $this->populate();
        $repo = $this->em->getRepository(self::ROOT_CATEGORY);

        $citrons = $repo->findOneBy(['title' => 'Citrons']);
        $vegitables = $repo->findOneBy(['title' => 'Vegitables']);

        $repo->persistAsNextSiblingOf($vegitables, $citrons);
        $this->em->flush();

        static::assertSame(5, $vegitables->getLeft());
        static::assertSame(6, $vegitables->getRight());
        static::assertSame(2, $vegitables->getParent()->getId());

        $fruits = $repo->findOneBy(['title' => 'Fruits']);
        static::assertSame(2, $fruits->getLeft());
        static::assertSame(9, $fruits->getRight());

        $milk = $repo->findOneBy(['title' => 'Milk']);
        $repo->persistAsFirstChildOf($milk, $fruits);
        $this->em->flush();

        static::assertSame(3, $milk->getLeft());
        static::assertSame(4, $milk->getRight());

        static::assertSame(2, $fruits->getLeft());
        static::assertSame(11, $fruits->getRight());
    }

    public function testTreeChildPositionMove(): void
    {
        $this->populate();
        $repo = $this->em->getRepository(self::ROOT_CATEGORY);

        $oranges = $repo->findOneBy(['title' => 'Oranges']);
        $fruits = $repo->findOneBy(['title' => 'Fruits']);

        static::assertSame(2, $oranges->getLevel());

        $repo->persistAsNextSiblingOf($oranges, $fruits);
        $this->em->flush();

        static::assertSame(1, $oranges->getLevel());
        static::assertCount(1, $repo->children($fruits, true));

        $vegies = $repo->findOneBy(['title' => 'Vegitables']);
        static::assertSame(2, $vegies->getLeft());
        $repo->persistAsNextSiblingOf($vegies, $fruits);
        $this->em->flush();

        static::assertSame(6, $vegies->getLeft());
        $this->em->flush();
        static::assertSame(6, $vegies->getLeft());
    }

    public function testOnRootCategory(): void
    {
        // need to check if this does not produce errors
        $repo = $this->em->getRepository(self::ROOT_CATEGORY);

        $fruits = new RootCategory();
        $fruits->setTitle('Fruits');

        $vegitables = new RootCategory();
        $vegitables->setTitle('Vegitables');

        $milk = new RootCategory();
        $milk->setTitle('Milk');

        $meat = new RootCategory();
        $meat->setTitle('Meat');

        $repo
            ->persistAsFirstChild($fruits)
            ->persistAsFirstChild($vegitables)
            ->persistAsLastChild($milk)
            ->persistAsLastChild($meat);

        $cookies = new RootCategory();
        $cookies->setTitle('Cookies');

        $drinks = new RootCategory();
        $drinks->setTitle('Drinks');

        $repo
            ->persistAsNextSibling($cookies)
            ->persistAsPrevSibling($drinks);

        $this->em->flush();
        $dql = 'SELECT COUNT(c) FROM '.self::ROOT_CATEGORY.' c';
        $dql .= ' WHERE c.lft = 1 AND c.rgt = 2 AND c.parent IS NULL AND c.level = 0';
        $count = $this->em->createQuery($dql)->getSingleScalarResult();
        static::assertSame(6, (int) $count);

        $repo = $this->em->getRepository(self::CATEGORY);

        $fruits = new Category();
        $fruits->setTitle('Fruits');

        $vegitables = new Category();
        $vegitables->setTitle('Vegitables');

        $milk = new Category();
        $milk->setTitle('Milk');

        $meat = new Category();
        $meat->setTitle('Meat');

        $repo
            ->persistAsFirstChild($fruits)
            ->persistAsFirstChild($vegitables)
            ->persistAsLastChild($milk)
            ->persistAsLastChild($meat);

        $cookies = new Category();
        $cookies->setTitle('Cookies');

        $drinks = new Category();
        $drinks->setTitle('Drinks');

        $repo
            ->persistAsNextSibling($cookies)
            ->persistAsPrevSibling($drinks);

        $this->em->flush();
        $dql = 'SELECT COUNT(c) FROM '.self::CATEGORY.' c';
        $dql .= ' WHERE c.parentId IS NULL AND c.level = 0';
        $dql .= ' AND c.lft BETWEEN 1 AND 11';
        $count = $this->em->createQuery($dql)->getSingleScalarResult();
        static::assertSame(6, (int) $count);
    }

    public function testRootTreePositionedInserts(): void
    {
        $repo = $this->em->getRepository(self::ROOT_CATEGORY);

        // test child positioned inserts
        $food = new RootCategory();
        $food->setTitle('Food');

        $fruits = new RootCategory();
        $fruits->setTitle('Fruits');

        $vegitables = new RootCategory();
        $vegitables->setTitle('Vegitables');

        $milk = new RootCategory();
        $milk->setTitle('Milk');

        $meat = new RootCategory();
        $meat->setTitle('Meat');

        $repo
            ->persistAsFirstChild($food)
            ->persistAsFirstChildOf($fruits, $food)
            ->persistAsFirstChildOf($vegitables, $food)
            ->persistAsLastChildOf($milk, $food)
            ->persistAsLastChildOf($meat, $food);

        $this->em->flush();

        static::assertSame(4, $fruits->getLeft());
        static::assertSame(5, $fruits->getRight());

        static::assertSame(2, $vegitables->getLeft());
        static::assertSame(3, $vegitables->getRight());

        static::assertSame(6, $milk->getLeft());
        static::assertSame(7, $milk->getRight());

        static::assertSame(8, $meat->getLeft());
        static::assertSame(9, $meat->getRight());

        // test sibling positioned inserts
        $cookies = new RootCategory();
        $cookies->setTitle('Cookies');

        $drinks = new RootCategory();
        $drinks->setTitle('Drinks');

        $repo
            ->persistAsNextSiblingOf($cookies, $milk)
            ->persistAsPrevSiblingOf($drinks, $milk);

        $this->em->flush();

        static::assertSame(6, $drinks->getLeft());
        static::assertSame(7, $drinks->getRight());

        static::assertSame(10, $cookies->getLeft());
        static::assertSame(11, $cookies->getRight());

        static::assertTrue($repo->verify());
    }

    public function testRootlessTreeTopLevelInserts(): void
    {
        $repo = $this->em->getRepository(self::CATEGORY);

        // test top level positioned inserts
        $fruits = new Category();
        $fruits->setTitle('Fruits');

        $vegetables = new Category();
        $vegetables->setTitle('Vegetables');

        $milk = new Category();
        $milk->setTitle('Milk');

        $meat = new Category();
        $meat->setTitle('Meat');

        $repo
            ->persistAsFirstChild($fruits)
            ->persistAsFirstChild($vegetables)
            ->persistAsLastChild($milk)
            ->persistAsLastChild($meat);

        $this->em->flush();

        static::assertSame(3, $fruits->getLeft());
        static::assertSame(4, $fruits->getRight());

        static::assertSame(1, $vegetables->getLeft());
        static::assertSame(2, $vegetables->getRight());

        static::assertSame(5, $milk->getLeft());
        static::assertSame(6, $milk->getRight());

        static::assertSame(7, $meat->getLeft());
        static::assertSame(8, $meat->getRight());

        // test sibling positioned inserts
        $cookies = new Category();
        $cookies->setTitle('Cookies');

        $drinks = new Category();
        $drinks->setTitle('Drinks');

        $repo
            ->persistAsNextSiblingOf($cookies, $milk)
            ->persistAsPrevSiblingOf($drinks, $milk);

        $this->em->flush();

        static::assertSame(5, $drinks->getLeft());
        static::assertSame(6, $drinks->getRight());

        static::assertSame(9, $cookies->getLeft());
        static::assertSame(10, $cookies->getRight());

        static::assertTrue($repo->verify());
    }

    public function testSimpleTreePositionedInserts(): void
    {
        $repo = $this->em->getRepository(self::CATEGORY);

        // test child positioned inserts
        $food = new Category();
        $food->setTitle('Food');
        $repo->persistAsFirstChild($food);

        $fruits = new Category();
        $fruits->setTitle('Fruits');
        $fruits->setParent($food);
        $repo->persistAsFirstChild($fruits);

        $vegitables = new Category();
        $vegitables->setTitle('Vegitables');
        $vegitables->setParent($food);
        $repo->persistAsFirstChild($vegitables);

        $milk = new Category();
        $milk->setTitle('Milk');
        $milk->setParent($food);
        $repo->persistAsLastChild($milk);

        $meat = new Category();
        $meat->setTitle('Meat');
        $meat->setParent($food);
        $repo->persistAsLastChild($meat);

        $this->em->flush();

        static::assertSame(4, $fruits->getLeft());
        static::assertSame(5, $fruits->getRight());

        static::assertSame(2, $vegitables->getLeft());
        static::assertSame(3, $vegitables->getRight());

        static::assertSame(6, $milk->getLeft());
        static::assertSame(7, $milk->getRight());

        static::assertSame(8, $meat->getLeft());
        static::assertSame(9, $meat->getRight());

        // test sibling positioned inserts
        $cookies = new Category();
        $cookies->setTitle('Cookies');
        $cookies->setParent($milk);
        $repo->persistAsNextSibling($cookies);

        $drinks = new Category();
        $drinks->setTitle('Drinks');
        $drinks->setParent($milk);
        $repo->persistAsPrevSibling($drinks);

        $this->em->flush();

        static::assertSame(6, $drinks->getLeft());
        static::assertSame(7, $drinks->getRight());

        static::assertSame(10, $cookies->getLeft());
        static::assertSame(11, $cookies->getRight());

        static::assertTrue($repo->verify());
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::CATEGORY,
            self::ROOT_CATEGORY,
        ];
    }

    private function populate(): void
    {
        $repo = $this->em->getRepository(self::ROOT_CATEGORY);

        $food = new RootCategory();
        $food->setTitle('Food');

        $fruits = new RootCategory();
        $fruits->setTitle('Fruits');

        $vegitables = new RootCategory();
        $vegitables->setTitle('Vegitables');

        $milk = new RootCategory();
        $milk->setTitle('Milk');

        $meat = new RootCategory();
        $meat->setTitle('Meat');

        $oranges = new RootCategory();
        $oranges->setTitle('Oranges');

        $citrons = new RootCategory();
        $citrons->setTitle('Citrons');

        $repo
            ->persistAsFirstChild($food)
            ->persistAsFirstChildOf($fruits, $food)
            ->persistAsFirstChildOf($vegitables, $food)
            ->persistAsLastChildOf($milk, $food)
            ->persistAsLastChildOf($meat, $food)
            ->persistAsFirstChildOf($oranges, $fruits)
            ->persistAsFirstChildOf($citrons, $fruits);

        $this->em->flush();
    }
}
