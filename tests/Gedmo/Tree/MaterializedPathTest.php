<?php

namespace Gedmo\Tree;

use Tool\BaseTestCaseMongoODM;
use Doctrine\Common\EventManager;
use Translatable\Fixture\Document\SimpleArticle as Article;
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
class MaterializedPathTest extends BaseTestCaseMongoODM
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

    public function testInsertNextSiblingOfRoot()
    {
        $this->populate();

        $root1 = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('title' => 'Root #1'))
        ;

        $child = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('title' => 'Child'))
        ;

        $subChild = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('title' => 'Sub Child'))
        ;

        $subChild2 = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('title' => 'Sub Child #2'))
        ;

        $subChild = null;
        $subChild = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('title' => 'Sub Child'))
        ;

        $newNode = new Category();
        $newNode->setTitle('Next sibling of root');
        $this->dm->persist($newNode);
        $this->dm->flush();

        $class = get_class($newNode);
        $meta = $this->dm->getClassMetadata($class);

        $this->listener->getStrategy($this->dm, $class)
            ->updateNode($this->dm, $newNode, $root1, Path::NEXT_SIBLING)
        ;

        $this->clearCollection();

        // Root
        $this->assertEquals(1, $root1->getChildCount());
        $this->assertEquals(1, $root1->getSortOrder());
        $this->assertNull($root1->getParent());
        $this->assertEquals('root-1,', $root1->getPath());

        // Child
        $this->assertEquals(2, $child->getChildCount());
        $this->assertEquals(2, $child->getSortOrder());
        $this->assertEquals($root1, $child->getParent());
        $this->assertEquals('root-1,child,', $child->getPath());

        // Sub child 1
        $this->assertEquals(0, $subChild->getChildCount());
        $this->assertEquals(3, $subChild->getSortOrder());
        $this->assertEquals($child, $subChild->getParent());
        $this->assertEquals('root-1,child,sub-child,', $subChild->getPath());

        // Sub child 2
        $this->assertEquals(0, $subChild2->getChildCount());
        $this->assertEquals(4, $subChild2->getSortOrder());
        $this->assertEquals($child, $subChild2->getParent());
        $this->assertEquals('root-1,child,sub-child-2,', $subChild2->getPath());

        $this->assertEquals(0, $newNode->getChildCount());
        $this->assertEquals(5, $newNode->getSortOrder());
        $this->assertNull($newNode->getParent());
        $this->assertEquals('next-sibling-of-root,', $newNode->getPath());
    }

    public function testNodesUpdateInUOWAndInsertAsNextSiblingOfChildNode()
    {
    	// Populate db
        $this->populate();

        $root1 = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('title' => 'Root #1'))
        ;

        $child = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('title' => 'Child'))
        ;

        $subChild = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('title' => 'Sub Child'))
        ;

        $subChild2 = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('title' => 'Sub Child #2'))
        ;

        $subChild = null;
        $subChild = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('title' => 'Sub Child'))
        ;

        if (!$subChild) {
        	$this->markTestSkipped('Can not find a node with a title of "Sub Child"');
        }

        $newNode = new Category();
        $newNode->setTitle('Next sibling of sub child');
        $this->dm->persist($newNode);
        $this->dm->flush();

        $class = get_class($newNode);
        $meta = $this->dm->getClassMetadata($class);

        $this->listener->getStrategy($this->dm, $class)
            ->updateNode($this->dm, $newNode, $child, Path::NEXT_SIBLING)
        ;

        $this->clearCollection();

        // Root
        $this->assertEquals(2, $root1->getChildCount());
        $this->assertEquals(1, $root1->getSortOrder());
        $this->assertNull($root1->getParent());
        $this->assertEquals('root-1,', $root1->getPath());

        // Child
        $this->assertEquals(2, $child->getChildCount());
        $this->assertEquals(2, $child->getSortOrder());
        $this->assertEquals($root1, $child->getParent());
        $this->assertEquals('root-1,child,', $child->getPath());

        // Sub child 1
        $this->assertEquals(0, $subChild->getChildCount());
        $this->assertEquals(3, $subChild->getSortOrder());
        $this->assertEquals($child, $subChild->getParent());
        $this->assertEquals('root-1,child,sub-child,', $subChild->getPath());

        // Sub child 2
        $this->assertEquals(0, $subChild2->getChildCount());
        $this->assertEquals(4, $subChild2->getSortOrder());
        $this->assertEquals($child, $subChild2->getParent());
        $this->assertEquals('root-1,child,sub-child-2,', $subChild2->getPath());

        // New node
        $this->assertEquals(0, $newNode->getChildCount());
        $this->assertEquals(5, $newNode->getSortOrder());
        $this->assertEquals($root1, $newNode->getParent());
        $this->assertEquals('root-1,next-sibling-of-sub-child,', $newNode->getPath());
    }

    public function testInsertNodeAsNextSiblingOfRoot()
    {
        $this->populate();

        // Create a new root category first
        $newNode = new Category();
        $newNode->setTitle('Next sibling of root');
        $this->dm->persist($newNode);
        $this->dm->flush();

        $class = get_class($newNode);
        $meta = $this->dm->getClassMetadata($class);
        $reference = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('title' => 'Root #1'))
        ;

        $this->listener->getStrategy($this->dm, $class)
            ->updateNode($this->dm, $newNode, $reference, Path::NEXT_SIBLING)
        ;

        $root1 = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('title' => 'Root #1'))
        ;

        $child = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('title' => 'Child'))
        ;

        $subChild = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('title' => 'Sub Child'))
        ;

        $subChild2 = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('title' => 'Sub Child #2'))
        ;

        $newNode = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('title' => 'Next sibling of root'))
        ;

        $this->clearCollection();

        // Root node
        $this->assertEquals(1, $root1->getChildCount());
        $this->assertEquals('root-1,', $root1->getPath());
        $this->assertEquals(1, $root1->getSortOrder());
        $this->assertNull($root1->getParent());

        // Child
        $this->assertEquals(2, $child->getChildCount());
        $this->assertEquals('root-1,child,', $child->getPath());
        $this->assertEquals(2, $child->getSortOrder());
        $this->assertEquals($root1, $child->getParent());

        // Sub-child
        $this->assertEquals(0, $subChild->getChildCount());
        $this->assertEquals('root-1,child,sub-child,', $subChild->getPath());
        $this->assertEquals(3, $subChild->getSortOrder());
        $this->assertEquals($child, $subChild->getParent());

        // Sub child 2
        $this->assertEquals(0, $subChild2->getChildCount());
        $this->assertEquals('root-1,child,sub-child-2,', $subChild2->getPath());
        $this->assertEquals(4, $subChild2->getSortOrder());
        $this->assertEquals($child, $subChild2->getParent());

        // New sibling of root
        $this->assertEquals(0, $newNode->getChildCount());
        $this->assertEquals('next-sibling-of-root,', $newNode->getPath());
        $this->assertEquals(5, $newNode->getSortOrder());
        $this->assertNull($newNode->getParent());
    }

    public function testInsertNodeAsPrevSiblingOfChild()
    {
        $this->populate();

        $newNode = new Category();
        $newNode->setTitle('Prev sibling of child');
        $this->dm->persist($newNode);
        $this->dm->flush();

        $class = get_class($newNode);
        $meta = $this->dm->getClassMetadata($class);
        $reference = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('title' => 'Child'))
        ;

        $this->listener->getStrategy($this->dm, $class)
            ->updateNode($this->dm, $newNode, $reference, Path::PREV_SIBLING)
        ;

        $root = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('title' => 'Root #1'))
        ;

        $subChild = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('title' => 'Sub Child'))
        ;

        $subChild2 = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('title' => 'Sub Child #2'))
        ;

        $this->clearCollection();

        $this->assertEquals(2, $root->getChildCount());
        $this->assertEquals('root-1,', $root->getPath());
        $this->assertEquals(1, $root->getSortOrder());
        $this->assertNull($root->getParent());

        // Test the new node is correct  w/o loading from DB
        $this->assertEquals(0, $newNode->getChildCount());
        $this->assertEquals('root-1,prev-sibling-of-child,', $newNode->getPath());
        $this->assertEquals(2, $newNode->getSortOrder());
        $this->assertEquals($root, $newNode->getParent());

        // Test if next sibling that we used as reference
        // is getting updated correctly without reloading
        $this->assertEquals(2, $reference->getChildCount());
        $this->assertEquals('root-1,child,', $reference->getPath());
        $this->assertEquals(3, $reference->getSortOrder());

        // Test Child's children
        $this->assertEquals(0, $subChild->getChildCount());
        $this->assertEquals('root-1,child,sub-child,', $subChild->getPath());
        $this->assertEquals(4, $subChild->getSortOrder());
        $this->assertEquals($reference, $subChild->getParent());

        $this->assertEquals(0, $subChild2->getChildCount());
        $this->assertEquals('root-1,child,sub-child-2,', $subChild2->getPath());
        $this->assertEquals(5, $subChild2->getSortOrder());
        $this->assertEquals($reference, $subChild2->getParent());
    }

    public function testInsertNodeAsFirstChild()
    {
    	$this->populate();

    	// Create a new root category first
    	$newNode = new Category();
        $newNode->setTitle('First child of child');
        $this->dm->persist($newNode);
        $this->dm->flush();

        $class = get_class($newNode);
        $meta = $this->dm->getClassMetadata($class);
        $parent = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('title' => 'Child'))
        ;

        $this->listener->getStrategy($this->dm, $class)
            ->updateNode($this->dm, $newNode, $parent, Path::FIRST_CHILD)
        ;

        $root = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('title' => 'Root #1'))
        ;

        $this->clearCollection();

        // Test if parent is getting update correctly without realoading
        $this->assertEquals(3, $parent->getChildCount());
        $this->assertEquals('root-1,child,', $parent->getPath());
        $this->assertEquals(2, $parent->getSortOrder());
        $this->assertEquals($root, $parent->getParent());

        // Test root is correct
        $this->assertEquals(1, $root->getChildCount());
        $this->assertEquals('root-1,', $root->getPath());
        $this->assertEquals(1, $root->getSortOrder());
        $this->assertEquals('Root #1', $root->getTitle());
        $this->assertNull($root->getParent());

        // Test the new first child node w/o loading from DB
        $this->assertEquals(0, $newNode->getChildCount());
        $this->assertEquals('root-1,child,first-child-of-child,', $newNode->getPath());
        $this->assertEquals(3, $newNode->getSortOrder());
        $this->assertEquals('First child of child', $newNode->getTitle());
    }

    public function testInsertExtraRoot()
    {
        $this->clearCollection();
        $this->populate();

        $newNode = new Category();
        $newNode->setTitle('Root #2');
        $this->dm->persist($newNode);
        $this->dm->flush();

        $root2 = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('title' => 'Root #2'))
        ;

        $this->clearCollection();

        $this->assertEquals(0, $root2->getChildCount());
        $this->assertEquals(5, $root2->getSortOrder());
        $this->assertEquals('root-2,', $root2->getPath());
        $this->assertNull($root2->getParent());
    }

    public function testSimpleTreeCreation()
    {
        $this->populate();

    	// Load everything from DB
        $root = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('title' => 'Root #1'))
        ;
        $child = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('title' => 'Child'))
        ;
        $subChild = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('title' => 'Sub Child'))
        ;
        $subChild2 = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('title' => 'Sub Child #2'))
        ;

        // Clear collection now incase tests fail
        $this->clearCollection();

        $this->assertEquals(1, $root->getChildCount(), '--> The root category should have 1 child');
        $this->assertEquals(1, $root->getSortOrder(), '--> The root category should have sort order set to 1 since its first');
        $this->assertEquals('root-1,', $root->getPath());
        $this->assertEquals(1, $root->getChildCount());

        $this->assertEquals(2, $child->getChildCount(), '--> The child category should have 2 children');
        $this->assertEquals(2, $child->getSortOrder(), '->> The child category should have a sort order of 2, one greater than that of its parent');
        $this->assertEquals('root-1,child,', $child->getPath());
        $this->assertEquals($root, $child->getParent());

        $this->assertEquals(0, $subChild->getChildCount(), '--> The Sub Child category should have 0 children');
        $this->assertEquals(3, $subChild->getSortOrder(), '->> The Sub Child category should have a sort order of 3, one greater than that of its parent');
        $this->assertEquals('root-1,child,sub-child,', $subChild->getPath());
        $this->assertEquals($child, $subChild->getParent());

        $this->assertEquals(0, $subChild2->getChildCount(), '--> The Sub Child #2 category should have 0 children');
        $this->assertEquals(4, $subChild2->getSortOrder(), '->> The Sub Child #2 category should have a sort order of 4, one greater than that of its parent');
        $this->assertEquals('root-1,child,sub-child-2,', $subChild2->getPath());
        $this->assertEquals($child, $subChild2->getParent());
    }

    /*
     * Updating methods
     */
    public function testUpdateNodeSlug()
    {
    	$this->clearCollection();

        $this->populate();

        // Clear dm so that all new nodes we just inserted are not managed
        $this->dm->clear();

        $repo = $this->dm->getRepository(self::CATEGORY);

        $root = $repo->findOneBy(array('path' => 'root-1,'));

        // Update one in DB
        $root->setTitle('New Title');
        $this->dm->flush();

        $child = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('title' => 'Child'))
        ;
        $subChild = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('title' => 'Sub Child'))
        ;
        $subChild2 = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('title' => 'Sub Child #2'))
        ;

        $this->assertEquals(1, $root->getChildCount());
        $this->assertEquals(1, $root->getSortOrder());
        $this->assertEquals('new-title,', $root->getPath());
        $this->assertEquals(1, $root->getChildCount());

        $this->assertEquals(2, $child->getChildCount());
        $this->assertEquals(2, $child->getSortOrder());
        $this->assertEquals('new-title,child,', $child->getPath());
        $this->assertEquals($root, $child->getParent());

        $this->assertEquals(0, $subChild->getChildCount());
        $this->assertEquals(3, $subChild->getSortOrder());
        $this->assertEquals('new-title,child,sub-child,', $subChild->getPath());
        $this->assertEquals($child, $subChild->getParent());

        $this->assertEquals(0, $subChild2->getChildCount());
        $this->assertEquals(4, $subChild2->getSortOrder());
        $this->assertEquals('new-title,child,sub-child-2,', $subChild2->getPath());
        $this->assertEquals($child, $subChild2->getParent());

        $this->clearCollection();
    }

    public function testUpdateMultipleNodeSlugs()
    {
        $this->clearCollection();

        $this->populate();

        // Clear dm so that all new nodes we just inserted are not managed
        $this->dm->clear();

        $repo = $this->dm->getRepository(self::CATEGORY);

        $root = $repo->findOneBy(array('path' => 'root-1,'));
        $child = $repo->findOneBy(array('path' => 'root-1,child,'));

        // Update one in DB
        $root->setTitle('New Title');
        $child->setTitle('New Child Title');
        $this->dm->flush();

        $subChild = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('title' => 'Sub Child'))
        ;
        $subChild2 = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('title' => 'Sub Child #2'))
        ;

        $this->assertEquals(1, $root->getChildCount());
        $this->assertEquals(1, $root->getSortOrder());
        $this->assertEquals('new-title,', $root->getPath());
        $this->assertEquals(1, $root->getChildCount());

        $this->assertEquals(2, $child->getChildCount());
        $this->assertEquals(2, $child->getSortOrder());
        $this->assertEquals('new-title,new-child-title,', $child->getPath());
        $this->assertEquals($root, $child->getParent());

        $this->assertEquals(0, $subChild->getChildCount());
        $this->assertEquals(3, $subChild->getSortOrder());
        $this->assertEquals('new-title,new-child-title,sub-child,', $subChild->getPath());
        $this->assertEquals($child, $subChild->getParent());

        $this->assertEquals(0, $subChild2->getChildCount());
        $this->assertEquals(4, $subChild2->getSortOrder());
        $this->assertEquals('new-title,new-child-title,sub-child-2,', $subChild2->getPath());
        $this->assertEquals($child, $subChild2->getParent());

        $this->clearCollection();
    }

    public function testSimpleDeleteNode()
    {
    	$this->clearCollection();

    	$this->populate();

        // Clear dm so that all new nodes we just inserted are not managed
        $this->dm->clear();

        $repo = $this->dm->getRepository(self::CATEGORY);

        $subChild = $repo->findOneBy(array('path' => 'root-1,child,sub-child,'));
        $this->dm->remove($subChild);
        $this->dm->flush();

        $root = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('title' => 'Root #1'))
        ;
        $child = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('title' => 'Child'))
        ;
        $subChild2 = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('title' => 'Sub Child #2'))
        ;

        $this->clearCollection();

        $this->assertEquals(1, $root->getChildCount());
        $this->assertEquals(1, $root->getSortOrder());
        $this->assertEquals('root-1,', $root->getPath());
        $this->assertEquals(1, $root->getChildCount());

        $this->assertEquals(1, $child->getChildCount());
        $this->assertEquals(2, $child->getSortOrder());
        $this->assertEquals('root-1,child,', $child->getPath());
        $this->assertEquals($root, $child->getParent());

        $this->assertEquals(0, $subChild2->getChildCount());
        $this->assertEquals(3, $subChild2->getSortOrder());
        $this->assertEquals('root-1,child,sub-child-2,', $subChild2->getPath());
        $this->assertEquals($child, $subChild2->getParent());
    }

    public function testDeleteNodeWithSubChildren()
    {
        $this->clearCollection();

        $this->populate();

        $reference = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('path' => 'root-1,child,'))
        ;

        $newNode = new Category();
        $newNode->setTitle('Next sibling child');
//        $newNode->setParent($reference); // Do not set parent @todo note this
        $this->dm->persist($newNode);
        $this->dm->flush();

        $class = get_class($newNode);
        $meta = $this->dm->getClassMetadata($class);
        $this->listener->getStrategy($this->dm, $class)
            ->updateNode($this->dm, $newNode, $reference, Path::NEXT_SIBLING)
        ;

        $root1 = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('title' => 'Root #1'))
        ;

        $child = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('title' => 'Child'))
        ;
        $this->dm->remove($child);
        $this->dm->flush();

        $subChild = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('title' => 'Sub Child'))
        ;

        $subChild2 = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('title' => 'Sub Child #2'))
        ;

        $this->clearCollection();

        // Root node
        $this->assertEquals(1, $root1->getChildCount());
        $this->assertEquals('root-1,', $root1->getPath());
        $this->assertEquals(1, $root1->getSortOrder());
        $this->assertNull($root1->getParent());

        $this->assertEquals(0, $newNode->getChildCount());
        $this->assertEquals('root-1,next-sibling-child,', $newNode->getPath());
        $this->assertEquals(2, $newNode->getSortOrder());
        $this->assertEquals($root1, $newNode->getParent());
    }

    /**
     * Populate the DB with some default nodes to work with.
     *
     * Creates a tree like
     *
     * Root #1
     *  child
     *      Sub Child
     *      Sub Child #2
     */
    private function populate()
    {
    	$root = new Category();
        $root->setTitle('Root #1');

        $child = new Category();
        $child->setTitle('Child');
        $child->setParent($root);

        $subChild = new Category();
        $subChild->setTitle('Sub Child');
        $subChild->setParent($child);

        $subChild2 = new Category();
        $subChild2->setTitle('Sub Child #2');
        $subChild2->setParent($child);

        $this->dm->persist($root);
        $this->dm->persist($child);
        $this->dm->persist($subChild);
        $this->dm->persist($subChild2);

        $this->dm->flush(array('safe' => true));
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