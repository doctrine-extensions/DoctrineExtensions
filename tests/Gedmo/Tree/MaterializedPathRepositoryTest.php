<?php

namespace Gedmo\Tree;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseMongoODM;
use Doctrine\Common\Util\Debug;
use Tree\Fixture\RootCategory;

/**
 * These are tests for Tree behavior
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Tree
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class MaterializedPathRepositoryTest extends BaseTestCaseMongoODM
{
    const CATEGORY = "Tree\\Fixture\\Document\\Category";

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $evm->addEventSubscriber(new TreeListener);

        $this->getMockDocumentManager($evm);
        $this->populate();
    }

    /**
     * @test
     */
    function getRootNodes()
    {
        $repo = $this->dm->getRepository(self::CATEGORY);
        $result = $repo->getRootNodesQueryBuilder()->sort('title', 'asc')->getQuery()->execute();
        
        $this->assertEquals(2, count($result));
        $this->assertEquals('Food', $result->getNext()->getTitle());
        $this->assertEquals('Sports', $result->getNext()->getTitle());
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::CATEGORY
        );
    }

    public function createCategory()
    {
        $class = self::CATEGORY;
        return new $class;
    }

    private function populate()
    {
        $root = $this->createCategory();
        $root->setTitle("Food");

        $root2 = $this->createCategory();
        $root2->setTitle("Sports");

        $child = $this->createCategory();
        $child->setTitle("Fruits");
        $child->setParent($root);

        $child2 = $this->createCategory();
        $child2->setTitle("Vegitables");
        $child2->setParent($root);

        $childsChild = $this->createCategory();
        $childsChild->setTitle("Carrots");
        $childsChild->setParent($child2);

        $potatoes = $this->createCategory();
        $potatoes->setTitle("Potatoes");
        $potatoes->setParent($child2);

        $this->dm->persist($root);
        $this->dm->persist($root2);
        $this->dm->persist($child);
        $this->dm->persist($child2);
        $this->dm->persist($childsChild);
        $this->dm->persist($potatoes);
        $this->dm->flush();
    }
}
