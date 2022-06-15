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
use Doctrine\DBAL\Logging\DebugStack;
use Doctrine\ORM\Query;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Tests\Tree\Fixture\Category;
use Gedmo\Tests\Tree\Fixture\RootCategory;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use Gedmo\Tree\Hydrator\ORM\TreeObjectHydrator;
use Gedmo\Tree\TreeListener;

/**
 * Tests the tree object hydrator
 *
 * @author Ilija Tovilo <ilija.tovilo@me.com>
 */
final class TreeObjectHydratorTest extends BaseTestCaseORM
{
    public const CATEGORY = Category::class;
    public const ROOT_CATEGORY = RootCategory::class;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new TreeListener());

        $this->getDefaultMockSqliteEntityManager($evm);

        $this->em->getConfiguration()->addCustomHydrationMode('tree', TreeObjectHydrator::class);
    }

    public function testFullTreeHydration(): void
    {
        $this->populate();
        $this->em->clear();

        $stack = new DebugStack();
        $this->em->getConfiguration()->setSQLLogger($stack);

        $repo = $this->em->getRepository(self::ROOT_CATEGORY);

        $result = $repo->createQueryBuilder('node')
            ->orderBy('node.lft', 'ASC')
            ->getQuery()
            ->setHint(Query::HINT_INCLUDE_META_COLUMNS, true)
            ->getResult('tree');

        static::assertCount(1, $result);

        $food = $result[0];
        static::assertSame('Food', $food->getTitle());
        static::assertCount(4, $food->getChildren());

        $fruits = $food->getChildren()->get(0);
        static::assertSame('Fruits', $fruits->getTitle());
        static::assertCount(2, $fruits->getChildren());

        $vegetables = $food->getChildren()->get(1);
        static::assertSame('Vegetables', $vegetables->getTitle());
        static::assertCount(0, $vegetables->getChildren());

        $milk = $food->getChildren()->get(2);
        static::assertSame('Milk', $milk->getTitle());
        static::assertCount(0, $milk->getChildren());

        $meat = $food->getChildren()->get(3);
        static::assertSame('Meat', $meat->getTitle());
        static::assertCount(0, $meat->getChildren());

        $oranges = $fruits->getChildren()->get(0);
        static::assertSame('Oranges', $oranges->getTitle());
        static::assertCount(0, $oranges->getChildren());

        $citrons = $fruits->getChildren()->get(1);
        static::assertSame('Citrons', $citrons->getTitle());
        static::assertCount(0, $citrons->getChildren());

        // Make sure only one query was executed
        static::assertCount(1, $stack->queries);
    }

    public function testPartialTreeHydration(): void
    {
        $this->populate();
        $this->em->clear();

        $stack = new DebugStack();
        $this->em->getConfiguration()->setSQLLogger($stack);

        /** @var NestedTreeRepository $repo */
        $repo = $this->em->getRepository(self::ROOT_CATEGORY);

        $fruits = $repo->findOneBy(['title' => 'Fruits']);

        $result = $repo->getChildrenQuery($fruits, false, null, 'ASC', true)
            ->setHint(Query::HINT_INCLUDE_META_COLUMNS, true)
            ->getResult('tree');

        static::assertCount(1, $result);

        $fruits = $result[0];
        static::assertSame('Fruits', $fruits->getTitle());
        static::assertCount(2, $fruits->getChildren());

        $oranges = $fruits->getChildren()->get(0);
        static::assertSame('Oranges', $oranges->getTitle());
        static::assertCount(0, $oranges->getChildren());

        $citrons = $fruits->getChildren()->get(1);
        static::assertSame('Citrons', $citrons->getTitle());
        static::assertCount(0, $citrons->getChildren());

        static::assertCount(2, $stack->queries);
    }

    public function testMultipleRootNodesTreeHydration(): void
    {
        $this->populate();
        $this->em->clear();

        $stack = new DebugStack();
        $this->em->getConfiguration()->setSQLLogger($stack);

        /** @var NestedTreeRepository $repo */
        $repo = $this->em->getRepository(self::ROOT_CATEGORY);

        $food = $repo->findOneBy(['title' => 'Food']);

        $result = $repo->getChildrenQuery($food)
            ->setHint(Query::HINT_INCLUDE_META_COLUMNS, true)
            ->getResult('tree');

        static::assertCount(4, $result);

        $fruits = $result[0];
        static::assertSame('Fruits', $fruits->getTitle());
        static::assertCount(2, $fruits->getChildren());

        $vegetables = $result[1];
        static::assertSame('Vegetables', $vegetables->getTitle());
        static::assertCount(0, $vegetables->getChildren());

        $milk = $result[2];
        static::assertSame('Milk', $milk->getTitle());
        static::assertCount(0, $milk->getChildren());

        $meat = $result[3];
        static::assertSame('Meat', $meat->getTitle());
        static::assertCount(0, $meat->getChildren());

        $oranges = $fruits->getChildren()->get(0);
        static::assertSame('Oranges', $oranges->getTitle());
        static::assertCount(0, $oranges->getChildren());

        $citrons = $fruits->getChildren()->get(1);
        static::assertSame('Citrons', $citrons->getTitle());
        static::assertCount(0, $citrons->getChildren());

        static::assertCount(2, $stack->queries);
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

        $vegetables = new RootCategory();
        $vegetables->setTitle('Vegetables');

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
            ->persistAsLastChildOf($fruits, $food)
            ->persistAsLastChildOf($vegetables, $food)
            ->persistAsLastChildOf($milk, $food)
            ->persistAsLastChildOf($meat, $food)
            ->persistAsLastChildOf($oranges, $fruits)
            ->persistAsLastChildOf($citrons, $fruits);

        $this->em->flush();
    }
}
