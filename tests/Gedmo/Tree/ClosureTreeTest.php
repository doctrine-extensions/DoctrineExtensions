<?php

namespace Gedmo\Tree;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Doctrine\Common\Util\Debug;
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
class ClosureTreeTest extends BaseTestCaseORM
{
    const CATEGORY = "Tree\\Fixture\\Closure\\Category";
    const CLOSURE = "Tree\\Fixture\\Closure\\CategoryClosure";

    private $food;
    private $sports;
    private $fruits;
    private $vegitables;
    private $carrots;
    private $potatoes;

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $evm->addEventSubscriber(new TreeListener);

        $this->getMockSqliteEntityManager($evm);
        $this->populate();
    }

    public function test_insertNodes_verifyClosurePaths()
    {
        // We check the inserted nodes fields from the closure table
        $repo = $this->em->getRepository(self::CLOSURE);
        $rows = $this->em->createQuery(sprintf('SELECT c FROM %s c ORDER BY c.ancestor ASC, c.descendant ASC, c.depth ASC', self::CLOSURE))
            ->getArrayResult();

        // Root self referencing row and descendants
        $this->assertEquals($rows[0]['ancestor'], $this->food->getId());
        $this->assertEquals($rows[0]['descendant'], $this->food->getId());
        $this->assertEquals($rows[0]['depth'], 0);

        $this->assertEquals($rows[1]['ancestor'], $this->food->getId());
        $this->assertEquals($rows[1]['descendant'], $this->fruits->getId());
        $this->assertEquals($rows[1]['depth'], 1);

        $this->assertEquals($rows[2]['ancestor'], $this->food->getId());
        $this->assertEquals($rows[2]['descendant'], $this->vegitables->getId());
        $this->assertEquals($rows[2]['depth'], 1);

        $this->assertEquals($rows[3]['ancestor'], $this->food->getId());
        $this->assertEquals($rows[3]['descendant'], $this->carrots->getId());
        $this->assertEquals($rows[3]['depth'], 2);

        $this->assertEquals($rows[4]['ancestor'], $this->food->getId());
        $this->assertEquals($rows[4]['descendant'], $this->potatoes->getId());
        $this->assertEquals($rows[4]['depth'], 2);

        // Sports self referencing row
        $this->assertEquals($rows[5]['ancestor'], $this->sports->getId());
        $this->assertEquals($rows[5]['descendant'], $this->sports->getId());
        $this->assertEquals($rows[5]['depth'], 0);

        // Fruits self referencing row
        $this->assertEquals($rows[6]['ancestor'], $this->fruits->getId());
        $this->assertEquals($rows[6]['descendant'], $this->fruits->getId());
        $this->assertEquals($rows[6]['depth'], 0);

        // Vegitables self referencing row and descendants
        $this->assertEquals($rows[7]['ancestor'], $this->vegitables->getId());
        $this->assertEquals($rows[7]['descendant'], $this->vegitables->getId());
        $this->assertEquals($rows[7]['depth'], 0);

        $this->assertEquals($rows[8]['ancestor'], $this->vegitables->getId());
        $this->assertEquals($rows[8]['descendant'], $this->carrots->getId());
        $this->assertEquals($rows[8]['depth'], 1);

        $this->assertEquals($rows[9]['ancestor'], $this->vegitables->getId());
        $this->assertEquals($rows[9]['descendant'], $this->potatoes->getId());
        $this->assertEquals($rows[9]['depth'], 1);

        // Carrots self referencing row
        $this->assertEquals($rows[10]['ancestor'], $this->carrots->getId());
        $this->assertEquals($rows[10]['descendant'], $this->carrots->getId());
        $this->assertEquals($rows[10]['depth'], 0);

        // Potatoes self referencing row
        $this->assertEquals($rows[11]['ancestor'], $this->potatoes->getId());
        $this->assertEquals($rows[11]['descendant'], $this->potatoes->getId());
        $this->assertEquals($rows[11]['depth'], 0);
    }

    public function test_childcount_afterInsertingNodesChildCountIsCalculated()
    {
        $this->em->refresh($this->food);
        $this->em->refresh($this->sports);
        $this->em->refresh($this->fruits);
        $this->em->refresh($this->vegitables);
        $this->em->refresh($this->carrots);
        $this->em->refresh($this->potatoes);

        $this->assertEquals($this->food->getChildCount(), 4);
        $this->assertEquals($this->sports->getChildCount(), 0);
        $this->assertEquals($this->fruits->getChildCount(), 0);
        $this->assertEquals($this->vegitables->getChildCount(), 2);
        $this->assertEquals($this->carrots->getChildCount(), 0);
        $this->assertEquals($this->potatoes->getChildCount(), 0);
    }

    public function test_updateNodes_moveASubtreeVerifyTreeClosurePathsAndVerifyChildCountField()
    {
        // We change a subtree's location
        $vegitables = $this->em->getRepository(self::CATEGORY)
            ->findOneByTitle('Vegitables');
        $sports = $this->em->getRepository(self::CATEGORY)
            ->findOneByTitle('Sports');
        $vegitables->setParent($sports);

        $this->em->persist($vegitables);
        $this->em->flush();

        // We then verify the closure paths
        $repo = $this->em->getRepository(self::CLOSURE);
        $rows = $this->em->createQuery(sprintf('SELECT c FROM %s c ORDER BY c.ancestor ASC, c.descendant ASC, c.depth ASC', self::CLOSURE))
            ->getArrayResult();

        // Food self referencing row and descendants
        $this->assertEquals($rows[0]['ancestor'], $this->food->getId());
        $this->assertEquals($rows[0]['descendant'], $this->food->getId());
        $this->assertEquals($rows[0]['depth'], 0);

        $this->assertEquals($rows[1]['ancestor'], $this->food->getId());
        $this->assertEquals($rows[1]['descendant'], $this->fruits->getId());
        $this->assertEquals($rows[1]['depth'], 1);

        // Sports self referencing row and descendants
        $this->assertEquals($rows[2]['ancestor'], $this->sports->getId());
        $this->assertEquals($rows[2]['descendant'], $this->sports->getId());
        $this->assertEquals($rows[2]['depth'], 0);

        $this->assertEquals($rows[3]['ancestor'], $this->sports->getId());
        $this->assertEquals($rows[3]['descendant'], $this->vegitables->getId());
        $this->assertEquals($rows[3]['depth'], 1);

        $this->assertEquals($rows[4]['ancestor'], $this->sports->getId());
        $this->assertEquals($rows[4]['descendant'], $this->carrots->getId());
        $this->assertEquals($rows[4]['depth'], 2);

        $this->assertEquals($rows[5]['ancestor'], $this->sports->getId());
        $this->assertEquals($rows[5]['descendant'], $this->potatoes->getId());
        $this->assertEquals($rows[5]['depth'], 2);

        // Fruits self referencing row
        $this->assertEquals($rows[6]['ancestor'], $this->fruits->getId());
        $this->assertEquals($rows[6]['descendant'], $this->fruits->getId());
        $this->assertEquals($rows[6]['depth'], 0);

        // Vegitables self referencing row and descendants
        $this->assertEquals($rows[7]['ancestor'], $this->vegitables->getId());
        $this->assertEquals($rows[7]['descendant'], $this->vegitables->getId());
        $this->assertEquals($rows[7]['depth'], 0);

        $this->assertEquals($rows[8]['ancestor'], $this->vegitables->getId());
        $this->assertEquals($rows[8]['descendant'], $this->carrots->getId());
        $this->assertEquals($rows[8]['depth'], 1);

        $this->assertEquals($rows[9]['ancestor'], $this->vegitables->getId());
        $this->assertEquals($rows[9]['descendant'], $this->potatoes->getId());
        $this->assertEquals($rows[9]['depth'], 1);

        // Carrots self referencing row
        $this->assertEquals($rows[10]['ancestor'], $this->carrots->getId());
        $this->assertEquals($rows[10]['descendant'], $this->carrots->getId());
        $this->assertEquals($rows[10]['depth'], 0);

        // Potatoes self referencing row
        $this->assertEquals($rows[11]['ancestor'], $this->potatoes->getId());
        $this->assertEquals($rows[11]['descendant'], $this->potatoes->getId());
        $this->assertEquals($rows[11]['depth'], 0);


        // We check the childCount field
        $this->em->refresh($this->food);
        $this->em->refresh($this->sports);
        $this->em->refresh($this->fruits);
        $this->em->refresh($this->vegitables);
        $this->em->refresh($this->carrots);
        $this->em->refresh($this->potatoes);

        $this->assertEquals($this->food->getChildCount(), 1);
        $this->assertEquals($this->sports->getChildCount(), 3);
        $this->assertEquals($this->fruits->getChildCount(), 0);
        $this->assertEquals($this->vegitables->getChildCount(), 2);
        $this->assertEquals($this->carrots->getChildCount(), 0);
        $this->assertEquals($this->potatoes->getChildCount(), 0);
    }

    public function test_removeNode_removesClosurePathsOfNodeAndVerifyTree()
    {
        // We remove a subtree
        $vegitables = $this->em->getRepository(self::CATEGORY)
            ->findOneByTitle('Vegitables');
        $this->em->remove($vegitables);
        $this->em->flush();

        // We then verify the closure paths
        $repo = $this->em->getRepository(self::CLOSURE);
        $rows = $this->em->createQuery(sprintf('SELECT c FROM %s c ORDER BY c.ancestor ASC, c.descendant ASC, c.depth ASC', self::CLOSURE))
            ->getArrayResult();

        // Food self referencing row and descendants
        $this->assertEquals($rows[0]['ancestor'], $this->food->getId());
        $this->assertEquals($rows[0]['descendant'], $this->food->getId());
        $this->assertEquals($rows[0]['depth'], 0);

        $this->assertEquals($rows[1]['ancestor'], $this->food->getId());
        $this->assertEquals($rows[1]['descendant'], $this->fruits->getId());
        $this->assertEquals($rows[1]['depth'], 1);

        // Sports self referencing row
        $this->assertEquals($rows[2]['ancestor'], $this->sports->getId());
        $this->assertEquals($rows[2]['descendant'], $this->sports->getId());
        $this->assertEquals($rows[2]['depth'], 0);

        // Fruits self referencing row
        $this->assertEquals($rows[3]['ancestor'], $this->fruits->getId());
        $this->assertEquals($rows[3]['descendant'], $this->fruits->getId());
        $this->assertEquals($rows[3]['depth'], 0);


        // We check the childCount field
        $this->em->refresh($this->food);
        $this->em->refresh($this->sports);
        $this->em->refresh($this->fruits);

        $this->assertEquals($this->food->getChildCount(), 1);
        $this->assertEquals($this->sports->getChildCount(), 0);
        $this->assertEquals($this->fruits->getChildCount(), 0);
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
    }
}
