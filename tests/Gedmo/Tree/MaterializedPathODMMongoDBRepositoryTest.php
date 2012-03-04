<?php

namespace Gedmo\Tree;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseMongoODM;
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
class MaterializedPathODMMongoDBRepositoryTest extends BaseTestCaseMongoODM
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
        $result = $repo->getRootNodes('title');
        
        $this->assertEquals(2, $result->count());
        $this->assertEquals('Food', $result->getNext()->getTitle());
        $this->assertEquals('Sports', $result->getNext()->getTitle());
    }

    /**
     * @test
     */
    function getChildren()
    {
        $repo = $this->dm->getRepository(self::CATEGORY);
        $root = $repo->findOneByTitle('Food');

        // Get all children from the root
        $result = $repo->getChildren($root, false, 'title');

        $this->assertEquals(4, count($result));
        $this->assertEquals('Carrots', $result->getNext()->getTitle());
        $this->assertEquals('Fruits', $result->getNext()->getTitle());
        $this->assertEquals('Potatoes', $result->getNext()->getTitle());
        $this->assertEquals('Vegitables', $result->getNext()->getTitle());

        // Get direct children from the root
        $result = $repo->getChildren($root, true, 'title');

        $this->assertEquals(2, $result->count());
        $this->assertEquals('Fruits', $result->getNext()->getTitle());
        $this->assertEquals('Vegitables', $result->getNext()->getTitle());

        // Get ALL nodes
        $result = $repo->getChildren(null, false, 'title');

        $this->assertEquals(6, $result->count());
        $this->assertEquals('Carrots', $result->getNext()->getTitle());
        $this->assertEquals('Food', $result->getNext()->getTitle());
        $this->assertEquals('Fruits', $result->getNext()->getTitle());
        $this->assertEquals('Potatoes', $result->getNext()->getTitle());
        $this->assertEquals('Sports', $result->getNext()->getTitle());
        $this->assertEquals('Vegitables', $result->getNext()->getTitle());
    }

    /**
     * @test
     */
    function getTree()
    {
        $repo = $this->dm->getRepository(self::CATEGORY);
        $tree = $repo->getTree();

        $this->assertEquals(6, $tree->count());
        $this->assertEquals('Food', $tree->getNext()->getTitle());
        $this->assertEquals('Fruits', $tree->getNext()->getTitle());
        $this->assertEquals('Vegitables', $tree->getNext()->getTitle());
        $this->assertEquals('Carrots', $tree->getNext()->getTitle());
        $this->assertEquals('Potatoes', $tree->getNext()->getTitle());
        $this->assertEquals('Sports', $tree->getNext()->getTitle());
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
