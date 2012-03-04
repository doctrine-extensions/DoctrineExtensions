<?php

namespace Gedmo\Tree;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
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
class MaterializedPathORMRepositoryTest extends BaseTestCaseORM
{
    const CATEGORY = "Tree\\Fixture\\MPCategory";

    protected function setUp()
    {
        parent::setUp();

        $this->listener = new TreeListener;

        $evm = new EventManager;
        $evm->addEventSubscriber($this->listener);

        $this->getMockSqliteEntityManager($evm);

        $meta = $this->em->getClassMetadata(self::CATEGORY);
        $this->config = $this->listener->getConfiguration($this->em, $meta->name);
        $this->populate();
    }

    /**
     * @test
     */
    function getRootNodes()
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $result = $repo->getRootNodes('title');
        
        $this->assertEquals(2, count($result));
        $this->assertEquals('Food', $result[0]->getTitle());
        $this->assertEquals('Sports', $result[1]->getTitle());
    }

    /**
     * @test
     */
    function getChildren()
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $root = $repo->findOneByTitle('Food');

        // Get all children from the root
        $result = $repo->getChildren($root, false, 'title');

        $this->assertEquals(4, count($result));
        $this->assertEquals('Carrots', $result[0]->getTitle());
        $this->assertEquals('Fruits', $result[1]->getTitle());
        $this->assertEquals('Potatoes', $result[2]->getTitle());
        $this->assertEquals('Vegitables', $result[3]->getTitle());

        // Get direct children from the root
        $result = $repo->getChildren($root, true, 'title');

        $this->assertEquals(2, count($result));
        $this->assertEquals('Fruits', $result[0]->getTitle());
        $this->assertEquals('Vegitables', $result[1]->getTitle());

        // Get ALL nodes
        $result = $repo->getChildren(null, false, 'title');

        $this->assertEquals(6, count($result));
        $this->assertEquals('Carrots', $result[0]->getTitle());
        $this->assertEquals('Food', $result[1]->getTitle());
        $this->assertEquals('Fruits', $result[2]->getTitle());
        $this->assertEquals('Potatoes', $result[3]->getTitle());
        $this->assertEquals('Sports', $result[4]->getTitle());
        $this->assertEquals('Vegitables', $result[5]->getTitle());
    }

    /**
     * @test
     */
    function getTree()
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $tree = $repo->getTree();

        $this->assertEquals(6, count($tree));
        $this->assertEquals('Food', $tree[0]->getTitle());
        $this->assertEquals('Fruits', $tree[1]->getTitle());
        $this->assertEquals('Vegitables', $tree[2]->getTitle());
        $this->assertEquals('Carrots', $tree[3]->getTitle());
        $this->assertEquals('Potatoes', $tree[4]->getTitle());
        $this->assertEquals('Sports', $tree[5]->getTitle());
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

        $this->em->persist($root);
        $this->em->persist($root2);
        $this->em->persist($child);
        $this->em->persist($child2);
        $this->em->persist($childsChild);
        $this->em->persist($potatoes);
        $this->em->flush();
    }
}
