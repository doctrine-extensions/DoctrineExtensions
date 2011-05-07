<?php

namespace Gedmo\Tree;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Tree\Fixture\Closure\Category;

/**
 * These are tests for Tree behavior
 *
 * @author Gustavo Adrian <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Tree
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class ClosureTreeRepositoryTest extends BaseTestCaseORM
{
    const CATEGORY = "Tree\\Fixture\\Closure\\Category";
    const CLOSURE = "Tree\\Fixture\\Closure\\CategoryClosure";

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $evm->addEventSubscriber(new TreeListener);

        $this->getMockSqliteEntityManager($evm);
        $this->populate();
    }

    public function testChildCount()
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $food = $repo->findOneByTitle('Food');

        $directCount = $repo->childCount($food, true);
        $this->assertEquals(3, $directCount);

        $fruits = $repo->findOneByTitle('Fruits');
        $count = $repo->childCount($fruits);
        $this->assertEquals(4, $count);

        $rootCount = $repo->childCount(null, true);
        $this->assertEquals(1, $rootCount);
    }

    public function testPath()
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $fruits = $repo->findOneByTitle('Fruits');

        $path = $repo->getPath($fruits);
        $this->assertEquals(2, count($path));
        $this->assertEquals('Food', $path[0]->getTitle());
        $this->assertEquals('Fruits', $path[1]->getTitle());

        $strawberries = $repo->findOneByTitle('Strawberries');
        $path = $repo->getPath($strawberries);
        $this->assertEquals(4, count($path));
        $this->assertEquals('Food', $path[0]->getTitle());
        $this->assertEquals('Fruits', $path[1]->getTitle());
        $this->assertEquals('Berries', $path[2]->getTitle());
        $this->assertEquals('Strawberries', $path[3]->getTitle());
    }

    public function testChildren()
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $fruits = $repo->findOneByTitle('Fruits');

        // direct children of node, sorted by title ascending order
        $children = $repo->children($fruits, true, 'title');
        $this->assertEquals(3, count($children));
        $this->assertEquals('Berries', $children[0]->getTitle());
        $this->assertEquals('Lemons', $children[1]->getTitle());
        $this->assertEquals('Oranges', $children[2]->getTitle());

        // all children of node
        $children = $repo->children($fruits);
        $this->assertEquals(4, count($children));
        $this->assertEquals('Oranges', $children[0]->getTitle());
        $this->assertEquals('Lemons', $children[1]->getTitle());
        $this->assertEquals('Berries', $children[2]->getTitle());
        $this->assertEquals('Strawberries', $children[3]->getTitle());

        // direct root nodes
        $children = $repo->children(null, true, 'title');
        $this->assertEquals(1, count($children));
        $this->assertEquals('Food', $children[0]->getTitle());

        // all tree
        $children = $repo->children();
        $this->assertEquals(12, count($children));
    }

    public function testSingleNodeRemoval()
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $fruits = $repo->findOneByTitle('Fruits');

        $repo->removeFromTree($fruits);
        // ensure in memory node integrity
        $this->em->flush();

        $food = $repo->findOneByTitle('Food');
        $children = $repo->children($food, true);
        $this->assertEquals(5, count($children));

        $berries = $repo->findOneByTitle('Berries');
        $this->assertEquals(1, $repo->childCount($berries, true));

        $lemons = $repo->findOneByTitle('Lemons');
        $this->assertEquals(0, $repo->childCount($lemons, true));

        $repo->removeFromTree($food);

        $vegitables = $repo->findOneByTitle('Vegitables');
        $this->assertEquals(2, $repo->childCount($vegitables, true));
        $this->assertEquals(null, $vegitables->getParent());

        $repo->removeFromTree($lemons);
        $this->assertEquals(4, count($repo->children(null, true)));
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::CATEGORY,
            self::CLOSURE
        );
    }

    private function populate()
    {
        $food = new Category;
        $food->setTitle("Food");
        $this->em->persist($food);

        $fruits = new Category;
        $fruits->setTitle('Fruits');
        $fruits->setParent($food);
        $this->em->persist($fruits);

        $oranges = new Category;
        $oranges->setTitle('Oranges');
        $oranges->setParent($fruits);
        $this->em->persist($oranges);

        $lemons = new Category;
        $lemons->setTitle('Lemons');
        $lemons->setParent($fruits);
        $this->em->persist($lemons);

        $berries = new Category;
        $berries->setTitle('Berries');
        $berries->setParent($fruits);
        $this->em->persist($berries);

        $strawberries = new Category;
        $strawberries->setTitle('Strawberries');
        $strawberries->setParent($berries);
        $this->em->persist($strawberries);

        $vegitables = new Category;
        $vegitables->setTitle('Vegitables');
        $vegitables->setParent($food);
        $this->em->persist($vegitables);

        $cabbages = new Category;
        $cabbages->setTitle('Cabbages');
        $cabbages->setParent($vegitables);
        $this->em->persist($cabbages);

        $carrots = new Category;
        $carrots->setTitle('Carrots');
        $carrots->setParent($vegitables);
        $this->em->persist($carrots);

        $milk = new Category;
        $milk->setTitle('Milk');
        $milk->setParent($food);
        $this->em->persist($milk);

        $cheese = new Category;
        $cheese->setTitle('Cheese');
        $cheese->setParent($milk);
        $this->em->persist($cheese);

        $mouldCheese = new Category;
        $mouldCheese->setTitle('Mould cheese');
        $mouldCheese->setParent($cheese);
        $this->em->persist($mouldCheese);

        $this->em->flush();
    }
}
