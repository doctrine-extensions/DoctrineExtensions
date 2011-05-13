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
        $this->clearCollection();

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
        $this->dm->clear();
    }

    public function testNodesUpdateInUOWAndInsertAsNextSiblingOfChildNode()
    {
        $this->clearCollection();

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
        $this->dm->clear();
    }

    public function testInsertNodeAsNextSiblingOfRoot()
    {
        $this->clearCollection();

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
        $this->dm->clear(); // Clear so we get fresh data

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

        $this->dm->clear();
    }

    public function testInsertNodeAsPrevSiblingOfChild()
    {
        $this->clearCollection();

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

        $this->dm->clear();

        $root = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('title' => 'Root #1'))
        ;

        $newNode = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('title' => 'Prev sibling of child'))
        ;

        $reference = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('title' => 'Child'))
        ;

        $subChild = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('title' => 'Sub Child'))
        ;

        $subChild2 = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('title' => 'Sub Child #2'))
        ;

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
        $this->dm->clear();
    }

    public function testInsertNodeAsFirstChild()
    {
        $this->clearCollection();

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
        $this->dm->clear();
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

        $this->assertEquals(0, $root2->getChildCount());
        $this->assertEquals(5, $root2->getSortOrder());
        $this->assertEquals('root-2,', $root2->getPath());
        $this->assertNull($root2->getParent());
        $this->dm->clear();
    }

    /**
     * @see Gedmo\Tree\Strategy\ODM\Path for why this test fails
     *
     * @expectedException InvalidArgumentException
     */
    public function testInsertChildWithInvalidParent()
    {
    	$this->clearCollection();

    	$this->markTestIncomplete('This functionality does not exist yet');

        $root = new Category();
        $root->setTitle('Root #1');

        $child1 = new Category();
        $child1->setTitle('Child #1');
        $child1->setParent($root);

        // Since $child1 is being persisted first, it will cause an invalid
        // tree since the parent does not exist in the DB.
        $this->dm->persist($child1);
        $this->dm->persist($root);
        $this->dm->flush();
        $this->dm->clear();
    }

    public function testSimpleTreeCreation()
    {
        $this->clearCollection();

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
        $this->dm->clear();
    }

    public function testInsertInIncorrectOrder()
    {
        $this->clearCollection();

        $root = new Category();
        $root->setTitle('Root #1');

        $child1 = new Category();
        $child1->setTitle('Child #1');
        $child1->setParent($root);

        $subChild1 = new Category();
        $subChild1->setTitle('Sub Child of child #1');
        $subChild1->setParent($child1);

        $child2 = new Category();
        $child2->setTitle('Child #2');
        $child2->setParent($root);

        $subChild2 = new Category();
        $subChild2->setTitle('Sub Child of child #2');
        $subChild2->setParent($child2);

        $subChild3 = new Category();
        $subChild3->setTitle('Sub Child #2 of child #2');
        $subChild3->setParent($child2);

        $child3 = new Category();
        $child3->setTitle('Child #3');
        $child3->setParent($root);

        $subChild4 = new Category();
        $subChild4->setTitle('Sub Child of child #3');
        $subChild4->setParent($child3);

        $subChild5 = new Category();
        $subChild5->setTitle('Sub Child #2 of child #3');
        $subChild5->setParent($child3);

        // @todo Note that you have to persist root then children
        // then more  next root and that roots children
        $this->dm->persist($root);
        $this->dm->persist($child1);
        $this->dm->persist($subChild1);
        $this->dm->persist($subChild2); // Sub child 2 is a child of child #2 which does not exist
        $this->dm->persist($child2);
        $this->dm->persist($subChild3);
        $this->dm->persist($child3);
        $this->dm->persist($subChild4);
        $this->dm->persist($subChild5);
        $this->dm->clear();
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
        $this->dm->clear();
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
        $this->dm->clear();
    }

    public function testMovingNodeAsFirstNodeInTree()
    {
        $this->clearCollection();

        $this->populate();

        // Clear dm so that all new nodes we just inserted are not managed
        $this->dm->clear();

        $repo = $this->dm->getRepository(self::CATEGORY);

        $root = $repo->findOneBy(array('path' => 'root-1,'));
        $child = $repo->findOneBy(array('path' => 'root-1,child,'));

        $class = get_class($child);
        $meta = $this->dm->getClassMetadata($class);
        $this->listener->getStrategy($this->dm, $class)
            ->moveNode($this->dm, $child, null)
        ;

        $this->dm->clear();
        $root = $repo->findOneBy(array('path' => 'root-1,'));
        $child = $repo->findOneBy(array('path' => 'child,'));

        $subChild = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('title' => 'Sub Child'))
        ;
        $subChild2 = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('title' => 'Sub Child #2'))
        ;

        $this->assertEquals(0, $root->getChildCount());
        $this->assertEquals(4, $root->getSortOrder());
        $this->assertEquals('root-1,', $root->getPath());

        $this->assertEquals(2, $child->getChildCount());
        $this->assertEquals(1, $child->getSortOrder());
        $this->assertEquals('child,', $child->getPath());
        $this->assertNull($child->getParent());

        $this->assertEquals(0, $subChild->getChildCount());
        $this->assertEquals(2, $subChild->getSortOrder());
        $this->assertEquals('child,sub-child,', $subChild->getPath());
        $this->assertEquals($child, $subChild->getParent());

        $this->assertEquals(0, $subChild2->getChildCount());
        $this->assertEquals(3, $subChild2->getSortOrder());
        $this->assertEquals('child,sub-child-2,', $subChild2->getPath());
        $this->assertEquals($child, $subChild2->getParent());
        $this->dm->clear();
    }

    public function testMovingNodeToNewRootOfLowerNode()
    {
        $this->clearCollection();

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

        $root2 = new Category();
        $root2->setTitle('Root #2');

        $this->dm->persist($root);
        $this->dm->persist($child);
        $this->dm->persist($subChild);
        $this->dm->persist($subChild2);
        $this->dm->persist($root2);

        $this->dm->flush(array('safe' => true));

        $repo = $this->dm->getRepository(self::CATEGORY);
        $class = get_class($child);
        $meta = $this->dm->getClassMetadata($class);
        $this->listener->getStrategy($this->dm, $class)
            ->moveNode($this->dm, $child, $root2, Path::FIRST_CHILD)
        ;

        $this->dm->clear(); // Clear so we don't get the nodes from memory

        $root = $repo->findOneBy(array('path' => 'root-1,'));
        $root2 = $repo->findOneBy(array('path' => 'root-2,'));
        $child = $repo->findOneBy(array('path' => 'root-2,child,'));
        $subChild = $repo->findOneBy(array('path' => 'root-2,child,sub-child,'));
        $subChild2 = $repo->findOneBy(array('path' => 'root-2,child,sub-child-2,'));

        $this->assertEquals(0, $root->getChildCount());
        $this->assertEquals(1, $root->getSortOrder());
        $this->assertNull($root->getParent());

        $this->assertEquals(1, $root2->getChildCount());
        $this->assertEquals(2, $root2->getSortOrder());
        $this->assertNull($root2->getParent());

        $this->assertEquals(2, $child->getChildCount());
        $this->assertEquals(3, $child->getSortOrder());
        $this->assertEquals($root2, $child->getParent());

        $this->assertEquals(0, $subChild->getChildCount());
        $this->assertEquals(4, $subChild->getSortOrder());
        $this->assertEquals($child, $subChild->getParent());

        $this->assertEquals(0, $subChild2->getChildCount());
        $this->assertEquals(5, $subChild2->getSortOrder());
        $this->assertEquals($child, $subChild2->getParent());
        $this->dm->clear();
    }

    public function testMovingNodeToNewRootOfHigherNode()
    {
        $this->clearCollection();

        $root = new Category();
        $root->setTitle('Root #1');

        $rootChild = new Category();
        $rootChild->setTitle('Sub of Root #1');
        $rootChild->setParent($root);

        $root2 = new Category();
        $root2->setTitle('Root #2');

        $child = new Category();
        $child->setTitle('Child');
        $child->setParent($root2);

        $subChild = new Category();
        $subChild->setTitle('Sub Child');
        $subChild->setParent($child);

        $subChild2 = new Category();
        $subChild2->setTitle('Sub Child #2');
        $subChild2->setParent($child);

        $this->dm->persist($root);
        $this->dm->persist($rootChild);
        $this->dm->persist($root2);
        $this->dm->persist($child);
        $this->dm->persist($subChild);
        $this->dm->persist($subChild2);

        $this->dm->flush(array('safe' => true));

        $repo = $this->dm->getRepository(self::CATEGORY);
        $class = get_class($child);
        $meta = $this->dm->getClassMetadata($class);
        $this->listener->getStrategy($this->dm, $class)
            ->moveNode($this->dm, $child, $root, Path::FIRST_CHILD)
        ;

        $this->dm->clear(); // Clear so we don't get the nodes from memory

        $root = $repo->findOneBy(array('path' => 'root-1,'));
        $root2 = $repo->findOneBy(array('path' => 'root-2,'));
        $rootChild = $repo->findOneBy(array('path' => 'root-1,sub-of-root-1,'));
        $child = $repo->findOneBy(array('path' => 'root-1,child,'));
        $subChild = $repo->findOneBy(array('path' => 'root-1,child,sub-child,'));
        $subChild2 = $repo->findOneBy(array('path' => 'root-1,child,sub-child-2,'));

        $this->assertEquals(2, $root->getChildCount());
        $this->assertEquals(1, $root->getSortOrder());
        $this->assertNull($root->getParent());

        $this->assertEquals(2, $child->getChildCount());
        $this->assertEquals(2, $child->getSortOrder());
        $this->assertEquals($root, $child->getParent());

        $this->assertEquals(0, $subChild->getChildCount());
        $this->assertEquals(3, $subChild->getSortOrder());
        $this->assertEquals($child, $subChild->getParent());

        $this->assertEquals(0, $subChild2->getChildCount());
        $this->assertEquals(4, $subChild2->getSortOrder());
        $this->assertEquals($child, $subChild2->getParent());

        $this->assertEquals(0, $rootChild->getChildCount());
        $this->assertEquals(5, $rootChild->getSortOrder());
        $this->assertEquals($root, $rootChild->getParent());

        $this->assertEquals(0, $root2->getChildCount());
        $this->assertEquals(6, $root2->getSortOrder());
        $this->assertNull($root2->getParent());
        $this->dm->clear();
    }

    /**
     * @todo Remove test once the switch cases are changed
     */
    public function testMovingNodeAsPrevSibling()
    {
    	$this->clearCollection();

        $root = new Category();
        $root->setTitle('Root #1');

        $child1 = new Category();
        $child1->setTitle('Child #1');
        $child1->setParent($root);

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
        $this->dm->persist($child1);
        $this->dm->persist($child);
        $this->dm->persist($subChild);
        $this->dm->persist($subChild2);

        $this->dm->flush(array('safe' => true));

        // Clear dm so that all new nodes we just inserted are not managed
        $this->dm->clear();

        $repo = $this->dm->getRepository(self::CATEGORY);

        $class = get_class($child);
        $meta = $this->dm->getClassMetadata($class);
        $this->listener->getStrategy($this->dm, $class)
            ->moveNode($this->dm, $child, $child1, Path::PREV_SIBLING)
        ;

        $this->dm->clear(); // Clear so we don't get the nodes from memory

        $root = $repo->findOneBy(array('path' => 'root-1,'));
        $child = $repo->findOneBy(array('path' => 'root-1,child,'));
        $child1 = $repo->findOneBy(array('path' => 'root-1,child-1,'));
        $subChild = $repo->findOneBy(array('title' => 'Sub Child'));
        $subChild2 = $repo->findOneBy(array('title' => 'Sub Child #2'));

        $this->assertEquals(2, $root->getChildCount());
        $this->assertEquals(1, $root->getSortOrder());
        $this->assertEquals('root-1,', $root->getPath());
        $this->assertNull($root->getParent());

        $this->assertEquals(2, $child->getChildCount());
        $this->assertEquals(2, $child->getSortOrder());
        $this->assertEquals('root-1,child,', $child->getPath());
        $this->assertEquals($root, $child->getParent());

        $this->assertEquals(0, $subChild->getChildCount());
        $this->assertEquals(3, $subChild->getSortOrder());
        $this->assertEquals('root-1,child,sub-child,', $subChild->getPath());
        $this->assertEquals($child, $subChild->getParent());

        $this->assertEquals(0, $subChild2->getChildCount());
        $this->assertEquals(4, $subChild2->getSortOrder());
        $this->assertEquals('root-1,child,sub-child-2,', $subChild2->getPath());
        $this->assertEquals($child, $subChild2->getParent());

        $this->assertEquals(0, $child1->getChildCount());
        $this->assertEquals(5, $child1->getSortOrder());
        $this->assertEquals('root-1,child-1,', $child1->getPath());
        $this->assertEquals($root, $child1->getParent());
        $this->dm->clear();
    }

//    public function testMovingNodeAsPrevSibling()
//    {
//        $this->clearCollection();
//
//        $root = new Category();
//        $root->setTitle('Root #1');
//
//        $child1 = new Category();
//        $child1->setTitle('Child #1');
//        $child1->setParent($root);
//
//        $child = new Category();
//        $child->setTitle('Child');
//        $child->setParent($root);
//
//        $subChild = new Category();
//        $subChild->setTitle('Sub Child');
//        $subChild->setParent($child);
//
//        $subChild2 = new Category();
//        $subChild2->setTitle('Sub Child #2');
//        $subChild2->setParent($child);
//
//        $this->dm->persist($root);
//        $this->dm->persist($child1);
//        $this->dm->persist($child);
//        $this->dm->persist($subChild);
//        $this->dm->persist($subChild2);
//
//        $this->dm->flush(array('safe' => true));
//
//        // Clear dm so that all new nodes we just inserted are not managed
//        $this->dm->clear();
//
//        $repo = $this->dm->getRepository(self::CATEGORY);
//
//        $class = get_class($child);
//        $meta = $this->dm->getClassMetadata($class);
//        $this->listener->getStrategy($this->dm, $class)
//            ->moveNode($this->dm, $child, $child1, Path::PREV_SIBLING)
//        ;
//
//        $this->dm->clear(); // Clear so we don't get the nodes from memory
//
//        $root = $repo->findOneBy(array('path' => 'root-1,'));
//        $child = $repo->findOneBy(array('path' => 'root-1,child,'));
//        $child1 = $repo->findOneBy(array('path' => 'root-1,child-1,'));
//        $subChild = $repo->findOneBy(array('title' => 'Sub Child'));
//        $subChild2 = $repo->findOneBy(array('title' => 'Sub Child #2'));
//
//        $this->assertEquals(2, $root->getChildCount());
//        $this->assertEquals(1, $root->getSortOrder());
//        $this->assertEquals('root-1,', $root->getPath());
//        $this->assertNull($root->getParent());
//
//        $this->assertEquals(2, $child->getChildCount());
//        $this->assertEquals(2, $child->getSortOrder());
//        $this->assertEquals('root-1,child,', $child->getPath());
//        $this->assertEquals($root, $child->getParent());
//
//        $this->assertEquals(0, $subChild->getChildCount());
//        $this->assertEquals(3, $subChild->getSortOrder());
//        $this->assertEquals('root-1,child,sub-child,', $subChild->getPath());
//        $this->assertEquals($child, $subChild->getParent());
//
//        $this->assertEquals(0, $subChild2->getChildCount());
//        $this->assertEquals(4, $subChild2->getSortOrder());
//        $this->assertEquals('root-1,child,sub-child-2,', $subChild2->getPath());
//        $this->assertEquals($child, $subChild2->getParent());
//
//        $this->assertEquals(0, $child1->getChildCount());
//        $this->assertEquals(5, $child1->getSortOrder());
//        $this->assertEquals('root-1,child-1,', $child1->getPath());
//        $this->assertEquals($root, $child1->getParent());
//        $this->dm->clear();
//    }

    public function testMovingNodeUpdatesMemory()
    {
    	$this->markTestIncomplete('Code still needs to be added');

        $this->clearCollection();

        $this->populate();

        // Clear dm so that all new nodes we just inserted are not managed
        $this->dm->clear();

        $repo = $this->dm->getRepository(self::CATEGORY);

        $root = $repo->findOneBy(array('path' => 'root-1,'));
        $child = $repo->findOneBy(array('path' => 'root-1,child,'));

        $class = get_class($child);
        $meta = $this->dm->getClassMetadata($class);
        $this->listener->getStrategy($this->dm, $class)
            ->moveNode($this->dm, $child, null)
        ;

        $this->dm->clear();

        $subChild = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('title' => 'Sub Child'))
        ;
        $subChild2 = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('title' => 'Sub Child #2'))
        ;

        $this->assertEquals(0, $root->getChildCount());
        $this->assertEquals(4, $root->getSortOrder());
        $this->assertEquals('root-1,', $root->getPath());

        $this->assertEquals(2, $child->getChildCount());
        $this->assertEquals(1, $child->getSortOrder());
        $this->assertEquals('child,', $child->getPath());
        $this->assertNull($child->getParent());

        $this->assertEquals(0, $subChild->getChildCount());
        $this->assertEquals(2, $subChild->getSortOrder());
        $this->assertEquals('child,sub-child,', $subChild->getPath());
        $this->assertEquals($child, $subChild->getParent());

        $this->assertEquals(0, $subChild2->getChildCount());
        $this->assertEquals(3, $subChild2->getSortOrder());
        $this->assertEquals('child,sub-child-2,', $subChild2->getPath());
        $this->assertEquals($child, $subChild2->getParent());
        $this->dm->clear();
    }

    public function testMovingNodeAsNextSiblingOfHigherNodeWithManyDescendants()
    {
        $this->clearCollection();

        $root = new Category();
        $root->setTitle('Root #1');

        $child1 = new Category();
        $child1->setTitle('Child #1');
        $child1->setParent($root);

        $subChild1 = new Category();
        $subChild1->setTitle('Sub Child of child #1');
        $subChild1->setParent($child1);

        $child2 = new Category();
        $child2->setTitle('Child #2');
        $child2->setParent($root);

        $subChild2 = new Category();
        $subChild2->setTitle('Sub Child of child #2');
        $subChild2->setParent($child2);

        $subChild3 = new Category();
        $subChild3->setTitle('Sub Child #2 of child #2');
        $subChild3->setParent($child2);

        $child3 = new Category();
        $child3->setTitle('Child #3');
        $child3->setParent($root);

        $subChild4 = new Category();
        $subChild4->setTitle('Sub Child of child #3');
        $subChild4->setParent($child3);

        $subChild5 = new Category();
        $subChild5->setTitle('Sub Child #2 of child #3');
        $subChild5->setParent($child3);

        // @todo Note that you have to persist root then children
        // then more  next root and that roots children
        $this->dm->persist($root);
        $this->dm->persist($child1);
        $this->dm->persist($subChild1);
        $this->dm->persist($child2);
        $this->dm->persist($subChild2);
        $this->dm->persist($subChild3);
        $this->dm->persist($child3);
        $this->dm->persist($subChild4);
        $this->dm->persist($subChild5);

        $this->dm->flush(array('safe' => true));

        // Clear dm so that all new nodes we just inserted are not managed
        $this->dm->clear();

        $repo = $this->dm->getRepository(self::CATEGORY);

        $class = get_class($child3);
        $meta = $this->dm->getClassMetadata($class);
        $this->listener->getStrategy($this->dm, $class)
            ->moveNode($this->dm, $child3, $child1, Path::NEXT_SIBLING)
        ;

        $this->dm->clear(); // Clear so we don't get the nodes from memory

        $root = $repo->findOneBy(array('path' => 'root-1,'));
        $child1 = $repo->findOneBy(array('path' => 'root-1,child-1,'));
        $subChild1 = $repo->findOneBy(array('path' => 'root-1,child-1,sub-child-of-child-1,'));
        $child2 = $repo->findOneBy(array('path' => 'root-1,child-2,'));
        $subChild2 = $repo->findOneBy(array('path' => 'root-1,child-2,sub-child-of-child-2,'));
        $subChild3 = $repo->findOneBy(array('path' => 'root-1,child-2,sub-child-2-of-child-2,'));
        $child3 = $repo->findOneBy(array('path' => 'root-1,child-3,'));
        $subChild4 = $repo->findOneBy(array('path' => 'root-1,child-3,sub-child-of-child-3,'));
        $subChild5 = $repo->findOneBy(array('path' => 'root-1,child-3,sub-child-2-of-child-3,'));

        $this->assertEquals(3, $root->getChildCount());
        $this->assertEquals(1, $root->getSortOrder());
        $this->assertNull($root->getParent());

        $this->assertEquals(1, $child1->getChildCount());
        $this->assertEquals(2, $child1->getSortOrder());
        $this->assertEquals($root, $child1->getParent());

        $this->assertEquals(0, $subChild1->getChildCount());
        $this->assertEquals(3, $subChild1->getSortOrder());
        $this->assertEquals($child1, $subChild1->getParent());

        $this->assertEquals(2, $child3->getChildCount());
        $this->assertEquals(4, $child3->getSortOrder());
        $this->assertEquals($root, $child3->getParent());

        $this->assertEquals(0, $subChild4->getChildCount());
        $this->assertEquals(5, $subChild4->getSortOrder());
        $this->assertEquals($child3, $subChild4->getParent());

        $this->assertEquals(0, $subChild5->getChildCount());
        $this->assertEquals(6, $subChild5->getSortOrder());
        $this->assertEquals($child3, $subChild5->getParent());

        $this->assertEquals(2, $child2->getChildCount());
        $this->assertEquals(7, $child2->getSortOrder());
        $this->assertEquals($root, $child2->getParent());

        $this->assertEquals(0, $subChild2->getChildCount());
        $this->assertEquals(8, $subChild2->getSortOrder());
        $this->assertEquals($child2, $subChild2->getParent());

        $this->assertEquals(0, $subChild3->getChildCount());
        $this->assertEquals(9, $subChild3->getSortOrder());
        $this->assertEquals($child2, $subChild3->getParent());
        $this->dm->clear();
    }

    public function testMovingNodeAsNextSiblingOfLowerNode()
    {
        $this->clearCollection();

        $root = new Category();
        $root->setTitle('Root #1');

        $child1 = new Category();
        $child1->setTitle('Child #1');
        $child1->setParent($root);

        $subChild1 = new Category();
        $subChild1->setTitle('Sub Child of child #1');
        $subChild1->setParent($child1);

        $child2 = new Category();
        $child2->setTitle('Child #2');
        $child2->setParent($root);

        $subChild2 = new Category();
        $subChild2->setTitle('Sub Child of child #2');
        $subChild2->setParent($child2);

        $subChild3 = new Category();
        $subChild3->setTitle('Sub Child #2 of child #2');
        $subChild3->setParent($child2);

        $child3 = new Category();
        $child3->setTitle('Child #3');
        $child3->setParent($root);

        $subChild4 = new Category();
        $subChild4->setTitle('Sub Child of child #3');
        $subChild4->setParent($child3);

        $subChild5 = new Category();
        $subChild5->setTitle('Sub Child #2 of child #3');
        $subChild5->setParent($child3);

        // @todo Note that you have to persist root then children
        // then more  next root and that roots children
        $this->dm->persist($root);
        $this->dm->persist($child1);
        $this->dm->persist($subChild1);
        $this->dm->persist($child2);
        $this->dm->persist($subChild2);
        $this->dm->persist($subChild3);
        $this->dm->persist($child3);
        $this->dm->persist($subChild4);
        $this->dm->persist($subChild5);

        $this->dm->flush(array('safe' => true));

        // Clear dm so that all new nodes we just inserted are not managed
        $this->dm->clear();

        $repo = $this->dm->getRepository(self::CATEGORY);

        $class = get_class($child3);
        $meta = $this->dm->getClassMetadata($class);
        $this->listener->getStrategy($this->dm, $class)
            ->moveNode($this->dm, $child2, $child3, Path::NEXT_SIBLING)
        ;

        $this->dm->clear(); // Clear so we don't get the nodes from memory

        $root = $repo->findOneBy(array('path' => 'root-1,'));
        $child1 = $repo->findOneBy(array('path' => 'root-1,child-1,'));
        $subChild1 = $repo->findOneBy(array('path' => 'root-1,child-1,sub-child-of-child-1,'));
        $child2 = $repo->findOneBy(array('path' => 'root-1,child-2,'));
        $subChild2 = $repo->findOneBy(array('path' => 'root-1,child-2,sub-child-of-child-2,'));
        $subChild3 = $repo->findOneBy(array('path' => 'root-1,child-2,sub-child-2-of-child-2,'));
        $child3 = $repo->findOneBy(array('path' => 'root-1,child-3,'));
        $subChild4 = $repo->findOneBy(array('path' => 'root-1,child-3,sub-child-of-child-3,'));
        $subChild5 = $repo->findOneBy(array('path' => 'root-1,child-3,sub-child-2-of-child-3,'));

        $this->assertEquals(3, $root->getChildCount());
        $this->assertEquals(1, $root->getSortOrder());
        $this->assertNull($root->getParent());

        $this->assertEquals(1, $child1->getChildCount());
        $this->assertEquals(2, $child1->getSortOrder());
        $this->assertEquals($root, $child1->getParent());

        $this->assertEquals(0, $subChild1->getChildCount());
        $this->assertEquals(3, $subChild1->getSortOrder());
        $this->assertEquals($child1, $subChild1->getParent());

        $this->assertEquals(2, $child3->getChildCount());
        $this->assertEquals(4, $child3->getSortOrder());
        $this->assertEquals($root, $child3->getParent());

        $this->assertEquals(0, $subChild4->getChildCount());
        $this->assertEquals(5, $subChild4->getSortOrder());
        $this->assertEquals($child3, $subChild4->getParent());

        $this->assertEquals(0, $subChild5->getChildCount());
        $this->assertEquals(6, $subChild5->getSortOrder());
        $this->assertEquals($child3, $subChild5->getParent());

        $this->assertEquals(2, $child2->getChildCount());
        $this->assertEquals(7, $child2->getSortOrder());
        $this->assertEquals($root, $child2->getParent());

        $this->assertEquals(0, $subChild2->getChildCount());
        $this->assertEquals(8, $subChild2->getSortOrder());
        $this->assertEquals($child2, $subChild2->getParent());

        $this->assertEquals(0, $subChild3->getChildCount());
        $this->assertEquals(9, $subChild3->getSortOrder());
        $this->assertEquals($child2, $subChild3->getParent());
        $this->dm->clear();
    }

    public function testMovingNodeAsLastChildOfHigherNode()
    {
        $this->clearCollection();

        /**
         * Creates a tree like:
         *  root 1 - 1
         *      child 1 - 2
         *          sub child of child 1 - 3
         *      child 2 - 4
         *          sub child of child 2 - 5
         *          sub child 2 of child 2 - 6
         *      child 3 - 7
         *          sub child of child 3 - 8
         *          sub child 2 of child 3 - 9
         *
         */
        $root = new Category();
        $root->setTitle('Root #1');

        $child1 = new Category();
        $child1->setTitle('Child #1');
        $child1->setParent($root);

        $subChild1 = new Category();
        $subChild1->setTitle('Sub Child of child #1');
        $subChild1->setParent($child1);

        $child2 = new Category();
        $child2->setTitle('Child #2');
        $child2->setParent($root);

        $subChild2 = new Category();
        $subChild2->setTitle('Sub Child of child #2');
        $subChild2->setParent($child2);

        $subChild3 = new Category();
        $subChild3->setTitle('Sub Child #2 of child #2');
        $subChild3->setParent($child2);

        $child3 = new Category();
        $child3->setTitle('Child #3');
        $child3->setParent($root);

        $subChild4 = new Category();
        $subChild4->setTitle('Sub Child of child #3');
        $subChild4->setParent($child3);

        $subChild5 = new Category();
        $subChild5->setTitle('Sub Child #2 of child #3');
        $subChild5->setParent($child3);

        $this->dm->persist($root);
        $this->dm->persist($child1);
        $this->dm->persist($subChild1);
        $this->dm->persist($child2);
        $this->dm->persist($subChild2);
        $this->dm->persist($subChild3);
        $this->dm->persist($child3);
        $this->dm->persist($subChild4);
        $this->dm->persist($subChild5);

        $this->dm->flush(array('safe' => true));

        $repo = $this->dm->getRepository(self::CATEGORY);

        /**
         * Move node to make:
         *  root 1 - 1
         *      child 1 - 2
         *          sub child of child 1 - 3
         *          child 3 - 4
         *              sub child of child 3 - 5
         *              sub child 2 of child 3 - 6
         *      child 2 - 7
         *          sub child of child 2 - 8
         *          sub child 2 of child 2 - 9
         *
         */
        $class = get_class($child3);
        $meta = $this->dm->getClassMetadata($class);
        $this->listener->getStrategy($this->dm, $class)
            ->moveNode($this->dm, $child3, $child1, Path::LAST_CHILD)
        ;

        $this->dm->clear(); // Clear so we don't get the nodes from memory

        $root = $repo->findOneBy(array('path' => 'root-1,'));
        $child1 = $repo->findOneBy(array('path' => 'root-1,child-1,'));
        $subChild1 = $repo->findOneBy(array('path' => 'root-1,child-1,sub-child-of-child-1,'));
        $child3 = $repo->findOneBy(array('path' => 'root-1,child-1,child-3,'));
        $subChild4 = $repo->findOneBy(array('path' => 'root-1,child-1,child-3,sub-child-of-child-3,'));
        $subChild5 = $repo->findOneBy(array('path' => 'root-1,child-1,child-3,sub-child-2-of-child-3,'));
        $child2 = $repo->findOneBy(array('path' => 'root-1,child-2,'));
        $subChild2 = $repo->findOneBy(array('path' => 'root-1,child-2,sub-child-of-child-2,'));
        $subChild3 = $repo->findOneBy(array('path' => 'root-1,child-2,sub-child-2-of-child-2,'));

        $this->assertEquals(2, $root->getChildCount());
        $this->assertEquals(1, $root->getSortOrder());
        $this->assertNull($root->getParent());

        $this->assertEquals(2, $child1->getChildCount());
        $this->assertEquals(2, $child1->getSortOrder());
        $this->assertEquals($root, $child1->getParent());

        $this->assertEquals(0, $subChild1->getChildCount());
        $this->assertEquals(3, $subChild1->getSortOrder());
        $this->assertEquals($child1, $subChild1->getParent());

        $this->assertEquals(2, $child3->getChildCount());
        $this->assertEquals(4, $child3->getSortOrder());
        $this->assertEquals($child1, $child3->getParent());

        $this->assertEquals(0, $subChild4->getChildCount());
        $this->assertEquals(5, $subChild4->getSortOrder());
        $this->assertEquals($child3, $subChild4->getParent());

        $this->assertEquals(0, $subChild5->getChildCount());
        $this->assertEquals(6, $subChild5->getSortOrder());
        $this->assertEquals($child3, $subChild5->getParent());

        $this->assertEquals(2, $child2->getChildCount());
        $this->assertEquals(7, $child2->getSortOrder());
        $this->assertEquals($root, $child2->getParent());

        $this->assertEquals(0, $subChild2->getChildCount());
        $this->assertEquals(8, $subChild2->getSortOrder());
        $this->assertEquals($child2, $subChild2->getParent());

        $this->assertEquals(0, $subChild3->getChildCount());
        $this->assertEquals(9, $subChild3->getSortOrder());
        $this->assertEquals($child2, $subChild3->getParent());
        $this->dm->clear();
    }

    public function testMovingNodeAsLastChildOfLowerNode()
    {
        $this->clearCollection();

        /**
         * Creates a tree like:
         *  root 1 - 1
         *      child 1 - 2
         *          sub child of child 1 - 3
         *      child 2 - 4
         *          sub child of child 2 - 5
         *          sub child 2 of child 2 - 6
         *      child 3 - 7
         *          sub child of child 3 - 8
         *          sub child 2 of child 3 - 9
         *
         */
        $root = new Category();
        $root->setTitle('Root #1');

        $child1 = new Category();
        $child1->setTitle('Child #1');
        $child1->setParent($root);

        $subChild1 = new Category();
        $subChild1->setTitle('Sub Child of child #1');
        $subChild1->setParent($child1);

        $child2 = new Category();
        $child2->setTitle('Child #2');
        $child2->setParent($root);

        $subChild2 = new Category();
        $subChild2->setTitle('Sub Child of child #2');
        $subChild2->setParent($child2);

        $subChild3 = new Category();
        $subChild3->setTitle('Sub Child #2 of child #2');
        $subChild3->setParent($child2);

        $child3 = new Category();
        $child3->setTitle('Child #3');
        $child3->setParent($root);

        $subChild4 = new Category();
        $subChild4->setTitle('Sub Child of child #3');
        $subChild4->setParent($child3);

        $subChild5 = new Category();
        $subChild5->setTitle('Sub Child #2 of child #3');
        $subChild5->setParent($child3);

        $this->dm->persist($root);
        $this->dm->persist($child1);
        $this->dm->persist($subChild1);
        $this->dm->persist($child2);
        $this->dm->persist($subChild2);
        $this->dm->persist($subChild3);
        $this->dm->persist($child3);
        $this->dm->persist($subChild4);
        $this->dm->persist($subChild5);

        $this->dm->flush(array('safe' => true));

        $repo = $this->dm->getRepository(self::CATEGORY);

        /**
         * Move node to make:
         *  root 1 - 1
         *      child 2 - 2
         *          sub child of child 2 - 3
         *          sub child 2 of child 2 - 4
         *      child 3 - 5
         *          sub child of child 3 - 6
         *          sub child 2 of child 3 - 7
         *          child 1 - 8
         *              sub child of child 1 - 9
         *
         */
        $class = get_class($child3);
        $meta = $this->dm->getClassMetadata($class);
        $this->listener->getStrategy($this->dm, $class)
            ->moveNode($this->dm, $child1, $child3, Path::LAST_CHILD)
        ;

        $this->dm->clear(); // Clear so we don't get the nodes from memory

        $root = $repo->findOneBy(array('path' => 'root-1,'));
        $child2 = $repo->findOneBy(array('path' => 'root-1,child-2,'));
        $subChild2 = $repo->findOneBy(array('path' => 'root-1,child-2,sub-child-of-child-2,'));
        $subChild3 = $repo->findOneBy(array('path' => 'root-1,child-2,sub-child-2-of-child-2,'));
        $child3 = $repo->findOneBy(array('path' => 'root-1,child-3,'));
        $subChild4 = $repo->findOneBy(array('path' => 'root-1,child-3,sub-child-of-child-3,'));
        $subChild5 = $repo->findOneBy(array('path' => 'root-1,child-3,sub-child-2-of-child-3,'));
        $child1 = $repo->findOneBy(array('path' => 'root-1,child-3,child-1,'));
        $subChild1 = $repo->findOneBy(array('path' => 'root-1,child-3,child-1,sub-child-of-child-1,'));

        $this->assertEquals(2, $root->getChildCount());
        $this->assertEquals(1, $root->getSortOrder());
        $this->assertNull($root->getParent());

        $this->assertEquals(2, $child2->getChildCount());
        $this->assertEquals(2, $child2->getSortOrder());
        $this->assertEquals($root, $child2->getParent());

        $this->assertEquals(0, $subChild2->getChildCount());
        $this->assertEquals(3, $subChild2->getSortOrder());
        $this->assertEquals($child2, $subChild2->getParent());

        $this->assertEquals(0, $subChild3->getChildCount());
        $this->assertEquals(4, $subChild3->getSortOrder());
        $this->assertEquals($child2, $subChild3->getParent());

        $this->assertEquals(3, $child3->getChildCount());
        $this->assertEquals(5, $child3->getSortOrder());
        $this->assertEquals($root, $child3->getParent());

        $this->assertEquals(0, $subChild4->getChildCount());
        $this->assertEquals(6, $subChild4->getSortOrder());
        $this->assertEquals($child3, $subChild4->getParent());

        $this->assertEquals(0, $subChild5->getChildCount());
        $this->assertEquals(7, $subChild5->getSortOrder());
        $this->assertEquals($child3, $subChild5->getParent());

        $this->assertEquals(1, $child1->getChildCount());
        $this->assertEquals(8, $child1->getSortOrder());
        $this->assertEquals($child3, $child1->getParent());

        $this->assertEquals(0, $subChild1->getChildCount());
        $this->assertEquals(9, $subChild1->getSortOrder());
        $this->assertEquals($child1, $subChild1->getParent());

        $this->dm->clear();
    }

    public function testSimpleDeleteNodeUpdatesMemory()
    {
    	$this->clearCollection();

    	$this->populate();
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
        $this->dm->clear();
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
        $this->dm->clear();
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
        $this->dm->clear();
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