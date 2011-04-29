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
//        $this->skipCollectionsOnDrop[] = 'Category';
    }

    public function testNodesUpdateInUOW()
    {
    	$this->markTestIncomplete('Need to fix the algorithm to update in memory nodes');

    	// populate db
        $this->populate();

    	// Fetch all nodes
        $nodes = $this->dm->getRepository(self::CATEGORY)
            ->findAll()
            ->toArray()
        ;

        $root = null;
        foreach ($nodes as $row) {
        	if ($row->getSortOrder() == 1)	{
        		$root = $row;
        	}
        }

        if (!$root) {
        	$this->markTestSkipped('Can not find a node with sort order of 1');
        }

        $newNode = new Category();
        $newNode->setTitle('Next sibling of root');
        $this->dm->persist($newNode);
        $this->dm->flush();
        $this->dm->clear();

        $class = get_class($newNode);
        $meta = $this->dm->getClassMetadata($class);

        $this->listener->getStrategy($this->dm, $class)
            ->updateNode($this->dm, $newNode, $root, Path::NEXT_SIBLING)
        ;

        $this->clearCollection();

        // Now loop though the nodes and make sure they all
        // have the values that will be in the DB
        foreach ($nodes as $node) {
        	if ($node->getTitle() == 'Root #1') {
                $this->assertEquals(1, $node->getChildCount());
                $this->assertEquals(1, $node->getSortOrder());
                $this->assertNull($node->getParent());
                $this->assertEquals('root-1,', $node->getPath());
        	} else if ($node->getTitle() == 'Child') {
                $this->assertEquals(2, $node->getChildCount());
                $this->assertEquals(3, $node->getSortOrder());
                $this->assertEquals('Root #1', $node->getParent()->getTitle());
                $this->assertEquals('root-1,child,', $node->getPath());
            } else if ($node->getTitle() == 'Sub Child') {
                $this->assertEquals(0, $node->getChildCount());
                $this->assertEquals(4, $node->getSortOrder());
                $this->assertEquals('Child', $node->getParent()->getTitle());
                $this->assertEquals('root-1,child,sub-child,', $node->getPath());
            } else if ($node->getTitle() == 'Sub Child #2') {
                $this->assertEquals(0, $node->getChildCount());
                $this->assertEquals(5, $node->getSortOrder());
                $this->assertEquals('Child', $node->getParent()->getTitle());
                $this->assertEquals('root-1,child,sub-child-2,', $node->getPath());
            }
        }
    }

    public function testInsertNodeAsNextSiblingOfRoot()
    {
        $this->populate();

        // Create a new root category first
        $newNode = new Category();
        $newNode->setTitle('Next sibling of child');
        $this->dm->persist($newNode);
        $this->dm->flush();
        $this->dm->clear();

        $class = get_class($newNode);
        $meta = $this->dm->getClassMetadata($class);
        $reference = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('title' => 'Root #1'))
        ;

        $this->listener->getStrategy($this->dm, $class)
            ->updateNode($this->dm, $newNode, $reference, Path::NEXT_SIBLING)
        ;

        $this->clearCollection();

        $this->markTestIncomplete('Need to add more assertions to make sure the tree is correct');
    }

    public function testInsertNodeAsPrevSiblingOfChild()
    {
        $this->populate();

        // Create a new root category first
        $newNode = new Category();
        $newNode->setTitle('Prev sibling of child');
        $this->dm->persist($newNode);
        $this->dm->flush();
        $this->dm->clear();

        $class = get_class($newNode);
        $meta = $this->dm->getClassMetadata($class);
        $reference = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('title' => 'Child'))
        ;

        $this->listener->getStrategy($this->dm, $class)
            ->updateNode($this->dm, $newNode, $reference, Path::PREV_SIBLING)
        ;

        // Test root is all correct
        $root = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('title' => 'Root #1'))
        ;

//        $this->clearCollection();

        $this->assertEquals(2, $root->getChildCount());
        $this->assertEquals('root-1,', $root->getPath());
        $this->assertEquals(1, $root->getSortOrder());

        // Test the new node is correct  w/o loading from DB
        $this->assertEquals(0, $newNode->getChildCount());
        $this->assertEquals('root-1,prev-sibling-of-child,', $newNode->getPath());
        $this->assertEquals(2, $newNode->getSortOrder());
        $this->assertEquals('Prev sibling of child', $newNode->getTitle());
        $this->assertEquals($root->getId(), $newNode->getParent()->getId());

        // Test if next sibling that we used as reference
        // is getting updated correctly without reloading
        $this->assertEquals(2, $reference->getChildCount());
        $this->assertEquals('root-1,child,', $reference->getPath());
        $this->assertEquals(3, $reference->getSortOrder());

        // Test Child's children
        $subChild = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('title' => 'Sub Child'))
        ;
        $this->assertEquals(0, $subChild->getChildCount());
        $this->assertEquals('root-1,child,sub-child,', $subChild->getPath());
        $this->assertEquals(4, $subChild->getSortOrder());
        $this->assertEquals($reference->getId(), $subChild->getParent()->getId());

        $subChild = $this->dm->getRepository(self::CATEGORY)
            ->findOneBy(array('title' => 'Sub Child #2'))
        ;
        $this->assertEquals(0, $subChild->getChildCount());
        $this->assertEquals('root-1,child,sub-child-2,', $subChild->getPath());
        $this->assertEquals(5, $subChild->getSortOrder());
        $this->assertEquals($reference->getId(), $subChild->getParent()->getId());
    }

    public function testInsertNodeAsFirstChild()
    {
    	$this->populate();

    	// Create a new root category first
    	$newNode = new Category();
        $newNode->setTitle('First child of child');
        $this->dm->persist($newNode);
        $this->dm->flush();
        $this->dm->clear();

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

        // Test root is all correct
        $this->assertEquals(1, $root->getChildCount());
        $this->assertEquals('root-1,', $root->getPath());
        $this->assertEquals(1, $root->getSortOrder());
        $this->assertEquals('Root #1', $root->getTitle());

        // Test the new first child node w/o loading from DB
        $this->assertEquals(0, $newNode->getChildCount());
        $this->assertEquals('root-1,child,first-child-of-child,', $newNode->getPath());
        $this->assertEquals(3, $newNode->getSortOrder());
        $this->assertEquals('First child of child', $newNode->getTitle());
    }

    public function testInsertExtraRoot()
    {
        $this->populate();

        $newNode = new Category();
        $newNode->setTitle('Root #2');
        $this->dm->persist($newNode);
        $this->dm->flush();

        $dbNode = $this->dm->getRepository(self::CATEGORY)->findOneBy(array('title' => 'Root #2'));

        $this->clearCollection();

        $this->assertEquals(0, $dbNode->getChildCount());
        $this->assertEquals(5, $dbNode->getSortOrder());
    }

    public function testSimpleTreeCreation()
    {
    	$this->populate();

        $result = $this->dm->createQueryBuilder(self::CATEGORY)
            ->field('title')->in(array('Root #1', 'Child', 'Sub Child', 'Sub Child #2'))
            ->getQuery()
            ->execute()
            ->toArray()
        ;

        $this->clearCollection();

        $this->assertTrue(count($result) == 4, sprintf('--> Should only have 4 results from the DB. Returned %s', count($result)));

        foreach ($result as $category)
        {
            if ($category->getTitle() == 'Root #1')
            {
                $this->assertEquals(1, $category->getChildCount(), '--> The root category should have 1 child');
                $this->assertEquals(1, $category->getSortOrder(), '--> The root category should have sort order set to 1 since its first');
            }
            else if ($category->getTitle() == 'Child')
            {
                $this->assertEquals(2, $category->getChildCount(), '--> The child category should have 2 children');
                $this->assertEquals(2, $category->getSortOrder(), '->> The child category should have a sort order of 2, one greater than that of its parent');
            }
            else if ($category->getTitle() == 'Sub Child')
            {
                $this->assertEquals(0, $category->getChildCount(), '--> The Sub Child category should have 0 children');
                $this->assertEquals(3, $category->getSortOrder(), '->> The Sub Child category should have a sort order of 3, one greater than that of its parent');
            }
            else if ($category->getTitle() == 'Sub Child #2')
            {
                $this->assertEquals(0, $category->getChildCount(), '--> The Sub Child #2 category should have 0 children');
                $this->assertEquals(4, $category->getSortOrder(), '->> The Sub Child #2 category should have a sort order of 4, one greater than that of its parent');
            }
        }
    }

    /**
     * Populate the DB with some default nodes to work with
     */
    private function populate()
    {
    	$root = new Category();
        $root->setTitle('Root #1');

        $child = new Category();
        $child->setTitle('Child');
        $child->setParent($root);

        $this->dm->persist($root);
        $this->dm->flush(array('safe' => true)); // Flush to insert root nodes

        $this->dm->persist($child);
        $this->dm->flush(array('safe' => true)); // Flush to insert children

        // We can insert multiple childs at once, but we can not insert multiple
        // parents at once.
        $subChild = new Category();
        $subChild->setTitle('Sub Child');
        $subChild->setParent($child);

        $subChild2 = new Category();
        $subChild2->setTitle('Sub Child #2');
        $subChild2->setParent($child);

        $this->dm->persist($subChild);
        $this->dm->persist($subChild2);
        $this->dm->flush(array('safe' => true)); // Flush to insert children
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