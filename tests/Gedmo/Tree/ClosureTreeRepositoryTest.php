<?php

namespace Gedmo\Tree;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Doctrine\Common\Util\Debug;
use Tree\Fixture\Closure\Category;
use Tool\Logging\DBAL\QueryAnalyzer;

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

    public function test_childCount_returnsNumberOfChilds()
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $food = $repo->findOneByTitle('Food');
        $closureRepo = $this->em->getRepository(self::CLOSURE);
        $childCount = $closureRepo->childCount($food);

        $this->assertEquals($childCount, 4);
    }

    public function test_childCount_returnsNumberOfDirectChilds()
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $food = $repo->findOneByTitle('Food');
        $closureRepo = $this->em->getRepository(self::CLOSURE);
        $childCount = $closureRepo->childCount($food, true);

        $this->assertEquals($childCount, 2);
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
        $root = new Category();
        $root->setTitle("Food");
        $this->food = $root;

        $root2 = new Category();
        $root2->setTitle("Sports");
        $this->sports = $root2;

        $child = new Category();
        $child->setTitle("Fruits");
        $child->setParent($root);
        $this->fruits = $child;

        $child2 = new Category();
        $child2->setTitle("Vegitables");
        $child2->setParent($root);
        $this->vegitables = $child2;

        $childsChild = new Category();
        $childsChild->setTitle("Carrots");
        $childsChild->setParent($child2);
        $this->carrots = $childsChild;

        $potatoes = new Category();
        $potatoes->setTitle("Potatoes");
        $potatoes->setParent($child2);
        $this->potatoes = $potatoes;

        $this->em->persist($this->food);
        $this->em->persist($this->sports);
        $this->em->persist($this->fruits);
        $this->em->persist($this->vegitables);
        $this->em->persist($this->carrots);
        $this->em->persist($this->potatoes);

        $this->em->flush();
        $this->em->clear();
    }
}
