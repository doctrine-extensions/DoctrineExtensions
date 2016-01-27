<?php

namespace Gedmo\Tree;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Tree\Fixture\RootRelationCategory;

/**
 * These are tests for Tree behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class NestedTreeRootRelationTest extends BaseTestCaseORM
{
    const CATEGORY = "Tree\\Fixture\\RootRelationCategory";

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new TreeListener());

        $this->getMockSqliteEntityManager($evm);
        $this->populate();
    }

    public function testRootEntity()
    {
        $repo = $this->em->getRepository(self::CATEGORY);

        // Foods
        $food = $repo->findOneByTitle('Food');
        $this->assertEquals($food->getId(), $food->getRoot()->getId());

        $fruits = $repo->findOneByTitle('Fruits');
        $this->assertEquals($food->getId(), $fruits->getRoot()->getId());

        $vegetables = $repo->findOneByTitle('Vegetables');
        $this->assertEquals($food->getId(), $vegetables->getRoot()->getId());

        $carrots = $repo->findOneByTitle('Carrots');
        $this->assertEquals($food->getId(), $carrots->getRoot()->getId());

        $potatoes = $repo->findOneByTitle('Potatoes');
        $this->assertEquals($food->getId(), $potatoes->getRoot()->getId());

        // Sports
        $sports = $repo->findOneByTitle('Sports');
        $this->assertEquals($sports->getId(), $sports->getRoot()->getId());
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::CATEGORY,
        );
    }

    private function populate()
    {
        $root = new RootRelationCategory();
        $root->setTitle("Food");

        $root2 = new RootRelationCategory();
        $root2->setTitle("Sports");

        $child = new RootRelationCategory();
        $child->setTitle("Fruits");
        $child->setParent($root);

        $child2 = new RootRelationCategory();
        $child2->setTitle("Vegetables");
        $child2->setParent($root);

        $childsChild = new RootRelationCategory();
        $childsChild->setTitle("Carrots");
        $childsChild->setParent($child2);

        $potatoes = new RootRelationCategory();
        $potatoes->setTitle("Potatoes");
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
