<?php

namespace Gedmo\Tree;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Logging\DebugStack;
use Doctrine\ORM\Query;
use Tool\BaseTestCaseORM;
use Tree\Fixture\RootCategory;

/**
 * Tests the tree object hydrator
 *
 * @author Ilija Tovilo <ilija.tovilo@me.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TreeObjectHydratorTest extends BaseTestCaseORM
{
    const CATEGORY = "Tree\\Fixture\\Category";
    const ROOT_CATEGORY = "Tree\\Fixture\\RootCategory";

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new TreeListener());

        $this->getMockSqliteEntityManager($evm);

        $this->em->getConfiguration()->addCustomHydrationMode('tree', 'Gedmo\Tree\Hydrator\ORM\TreeObjectHydrator');
    }

    public function testFullTreeHydration()
    {
        $this->populate();

        $stack = new DebugStack();
        $this->em->getConfiguration()->setSQLLogger($stack);

        $repo = $this->em->getRepository(self::ROOT_CATEGORY);

        $result = $repo->createQueryBuilder('node')
            ->getQuery()
            ->setHint(Query::HINT_INCLUDE_META_COLUMNS, true)
            ->getResult('tree');

        $this->assertEquals(count($result), 1);

        $food = $result[0];
        $this->assertEquals($food->getTitle(), 'Food');
        $this->assertEquals(count($food->getChildren()), 4);

        $fruits = $food->getChildren()->get(0);
        $this->assertEquals($fruits->getTitle(), 'Fruits');
        $this->assertEquals(count($fruits->getChildren()), 2);

        $vegetables = $food->getChildren()->get(1);
        $this->assertEquals($vegetables->getTitle(), 'Vegetables');
        $this->assertEquals(count($vegetables->getChildren()), 0);

        $milk = $food->getChildren()->get(2);
        $this->assertEquals($milk->getTitle(), 'Milk');
        $this->assertEquals(count($milk->getChildren()), 0);

        $meat = $food->getChildren()->get(3);
        $this->assertEquals($meat->getTitle(), 'Meat');
        $this->assertEquals(count($meat->getChildren()), 0);

        $oranges = $fruits->getChildren()->get(0);
        $this->assertEquals($oranges->getTitle(), 'Oranges');
        $this->assertEquals(count($oranges->getChildren()), 0);

        $citrons = $fruits->getChildren()->get(1);
        $this->assertEquals($citrons->getTitle(), 'Citrons');
        $this->assertEquals(count($citrons->getChildren()), 0);

        // Make sure only one query was executed
        $this->assertEquals(count($stack->queries), 1);
    }

    private function populate()
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
            ->persistAsFirstChildOf($fruits, $food)
            ->persistAsFirstChildOf($vegetables, $food)
            ->persistAsLastChildOf($milk, $food)
            ->persistAsLastChildOf($meat, $food)
            ->persistAsFirstChildOf($oranges, $fruits)
            ->persistAsFirstChildOf($citrons, $fruits);

        $this->em->flush();
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::CATEGORY,
            self::ROOT_CATEGORY,
        );
    }
}
