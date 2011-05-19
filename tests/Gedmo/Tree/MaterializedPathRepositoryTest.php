<?php

namespace Gedmo\Tree;

use Tool\BaseTestCaseMongoODM;
use Tree\Fixture\Path\Category;
use Gedmo\Tree\Strategy\ODM\Path;

/**
 *
 *
 * @author Michael Williams <michael.williams@funsational.com>
 * @package Gedmo.Tree
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class MaterializedPathRepositoryTest extends BaseTestCaseMongoODM
{
    const CATEGORY = 'Tree\\Fixture\\Path\\Category';

    private $listener;

    public function setUp()
    {
        parent::setUp();

        $this->getMockDocumentManager(null, true);

        $treeListener = null;
        foreach ($this->dm->getEventManager()->getListeners() as $event => $listeners) {
            foreach ($listeners as $hash => $listener) {
                if ($listener instanceof \Gedmo\Tree\TreeListener) {
                    $treeListener = $listener;
                    break;
                }
            }
            if ($treeListener) {
                break;
            }
        }

        if (is_null($treeListener)) {
            $this->markTestSkipped('Can not find the tree listener. Did you attach it to the Doctrine Event Manager?');
        }

        $this->listener = $treeListener;

        // @todo Remove test code
        $this->skipCollectionsOnDrop[] = 'Category';
    }

    public function testRepositoryChildrenMagicMethods()
    {
        $repo = $this->dm->getRepository(self::CATEGORY);

        $root1 = new Category;
        $root1->setTitle('Root 1');

        $root2 = new Category;
        $root2->setTitle('Root 2');

        $subChild = new Category;
        $subChild->setTitle('Sub Child of root 2');

        $root3 = new Category;
        $root3->setTitle('Root 3');

        $root4 = new Category;
        $root4->setTitle('Root 4');

        /**
         * Creats a tree like:
         *
         * root 1
         * root 2
         *     sub child of root 2
         * root 3
         * root 4
         *
         */
        $repo
            ->persistAsFirstChild($root2)
            ->persistAsFirstChildOf($subChild, $root2)
            ->persistAsFirstChild($root1)
            ->persistAsLastChild($root3)
            ->persistAsLastChild($root4)
        ;

        $this->dm->flush();

        $this->clearCollection();

        $this->assertEquals(1, $root1->getSortOrder());
        $this->assertEquals(0, $root1->getChildCount());
        $this->assertEquals('root-1,', $root1->getPath());
        $this->assertNull($root1->getParent());

        $this->assertEquals(2, $root2->getSortOrder());
        $this->assertEquals(1, $root2->getChildCount());
        $this->assertEquals('root-2,', $root2->getPath());
        $this->assertNull($root2->getParent());

        $this->assertEquals(3, $subChild->getSortOrder());
        $this->assertEquals(0, $subChild->getChildCount());
        $this->assertEquals('root-2,sub-child-of-root-2,', $subChild->getPath());
        $this->assertEquals($root2, $subChild->getParent());

        $this->assertEquals(4, $root3->getSortOrder());
        $this->assertEquals(0, $root3->getChildCount());
        $this->assertEquals('root-3,', $root3->getPath());
        $this->assertNull($root3->getParent());

        $this->assertEquals(5, $root4->getSortOrder());
        $this->assertEquals(0, $root4->getChildCount());
        $this->assertEquals('root-4,', $root4->getPath());
        $this->assertNull($root4->getParent());
    }

    public function testRepositorySiblingMagicMethods()
    {
        $repo = $this->dm->getRepository(self::CATEGORY);

        /*
         * root 1
         * root 2
         *     child of root 2
         *     child 2 inserted after child 2 of root 2
         *     child 2 of root 2
         * root 3
         * root 4
         *
         */
        $root1 = new Category;
        $root1->setTitle('Root 1');

        $root2 = new Category;
        $root2->setTitle('Root 2');

        $child1 = new Category();
        $child1->setTitle('Child 1');

        $child2 = new Category();
        $child2->setTitle('Child 2');

        $child3 = new Category();
        $child3->setTitle('Child 3');

        $root3 = new Category;
        $root3->setTitle('Root 3');

        $root4 = new Category;
        $root4->setTitle('Root 4');

        // Make a complicated insert... :)
        $repo
            ->persistAsNextSibling($root3)
            ->persistAsNextSiblingOf($root4, $root3) // Make $root4 next sibling of $root3
            ->persistAsPrevSibling($root2)
            ->persistAsFirstChildOf($child2, $root2)
            ->persistAsFirstChildOf($child1, $root2)
            ->persistAsNextSiblingOf($child3, $child2)
            ->persistAsPrevSiblingOf($root1, $root2) // Make $root1 prev sibling of $root2
        ;

        $this->dm->flush();

        $this->clearCollection();

        $this->assertEquals(1, $root1->getSortOrder());
        $this->assertEquals(0, $root1->getChildCount());
        $this->assertEquals('root-1,', $root1->getPath());
        $this->assertNull($root1->getParent());

        $this->assertEquals(2, $root2->getSortOrder());
        $this->assertEquals(3, $root2->getChildCount());
        $this->assertEquals('root-2,', $root2->getPath());
        $this->assertNull($root2->getParent());

        $this->assertEquals(3, $child1->getSortOrder());
        $this->assertEquals(0, $child1->getChildCount());
        $this->assertEquals('root-2,child-1,', $child1->getPath());
        $this->assertEquals($root2, $child1->getParent());

        $this->assertEquals(4, $child2->getSortOrder());
        $this->assertEquals(0, $child2->getChildCount());
        $this->assertEquals('root-2,child-2,', $child2->getPath());
        $this->assertEquals($root2, $child2->getParent());

        $this->assertEquals(5, $child3->getSortOrder());
        $this->assertEquals(0, $child3->getChildCount());
        $this->assertEquals('root-2,child-3,', $child3->getPath());
        $this->assertEquals($root2, $child3->getParent());

        $this->assertEquals(6, $root3->getSortOrder());
        $this->assertEquals(0, $root3->getChildCount());
        $this->assertEquals('root-3,', $root3->getPath());
        $this->assertNull($root3->getParent());

        $this->assertEquals(7, $root4->getSortOrder());
        $this->assertEquals(0, $root4->getChildCount());
        $this->assertEquals('root-4,', $root4->getPath());
        $this->assertNull($root4->getParent());
    }

    /**
     * Remove all nodes from the collection so a different test does not
     * have to deal with them
     */
    private function clearCollection()
    {
        $this->dm->createQueryBuilder(self::CATEGORY)->remove()->getQuery()->execute();
    }
}