<?php

namespace Gedmo\Sortable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Sortable\Fixture\Node;
use Sortable\Fixture\Item;
use Sortable\Fixture\Category;

/**
 * These are tests for sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Sluggable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SortableTest extends BaseTestCaseORM
{
    const NODE = 'Sortable\\Fixture\\Node';
    const ITEM = 'Sortable\\Fixture\\Item';
    const CATEGORY = 'Sortable\\Fixture\\Category';
    private $nodeId;
    
    protected function setUp()
    {
        parent::setUp();
        
        $evm = new EventManager;
        $evm->addEventSubscriber(new SortableListener);

        $this->getMockSqliteEntityManager($evm);
        //$this->startQueryLog();
        
        $this->populate();
    }
    
    protected function tearDown()
    {
        //$this->stopQueryLog();
    }
    
    public function testInsertedNewNode()
    {
        $node = $this->em->find(self::NODE, $this->nodeId);

        //$this->assertTrue($node instanceof Sortable);
        $this->assertEquals(0, $node->getPosition());
    }
    
    public function testInsertSortedList()
    {
        for ($i = 2; $i <= 10; $i++) {
            $node = new Node();
            $node->setName("Node".$i);
            $node->setPath("/");
            $this->em->persist($node);
        }
        $this->em->flush();
        $this->em->clear();
        
        $nodes = $this->em->createQuery("SELECT node FROM Sortable\Fixture\Node node "
                                        ."WHERE node.path = :path ORDER BY node.position")
                 ->setParameter('path', '/')
                 ->getResult();
        
        $this->assertEquals(10, count($nodes));
        $this->assertEquals('Node1', $nodes[0]->getName());
    }
    
    public function testShiftForward()
    {
        $node2 = new Node();
        $node2->setName("Node2");
        $node2->setPath("/");
        $this->em->persist($node2);
        
        $node = new Node();
        $node->setName("Node3");
        $node->setPath("/");
        $this->em->persist($node);
        
        $node = new Node();
        $node->setName("Node4");
        $node->setPath("/");
        $this->em->persist($node);
        
        $node = new Node();
        $node->setName("Node5");
        $node->setPath("/");
        $this->em->persist($node);
        
        $this->em->flush();
        
        $this->assertEquals(1, $node2->getPosition());
        $node2->setPosition(3);
        $this->em->persist($node2);
        $this->em->flush();
        $this->em->clear();
        
        $repo = $this->em->getRepository('Sortable\\Fixture\\Node');
        $nodes = $repo->getBySortableGroups(array('path' => '/'));
        
        $this->assertEquals('Node1', $nodes[0]->getName());
        $this->assertEquals('Node3', $nodes[1]->getName());
        $this->assertEquals('Node4', $nodes[2]->getName());
        $this->assertEquals('Node2', $nodes[3]->getName());
        $this->assertEquals('Node5', $nodes[4]->getName());
    }

    public function testShiftBackward()
    {
        $node = new Node();
        $node->setName("Node2");
        $node->setPath("/");
        $this->em->persist($node);
        
        $node = new Node();
        $node->setName("Node3");
        $node->setPath("/");
        $this->em->persist($node);
        
        $node2 = new Node();
        $node2->setName("Node4");
        $node2->setPath("/");
        $this->em->persist($node2);
        
        $node = new Node();
        $node->setName("Node5");
        $node->setPath("/");
        $this->em->persist($node);
        
        $this->em->flush();
        $this->assertEquals(3, $node2->getPosition());
        
        
        $node2->setPosition(1);
        $this->em->persist($node2);
        $this->em->flush();
        $this->em->clear();
        
        $repo = $this->em->getRepository('Sortable\\Fixture\\Node');
        $nodes = $repo->getBySortableGroups(array('path' => '/'));
        
        $this->assertEquals('Node1', $nodes[0]->getName());
        $this->assertEquals('Node4', $nodes[1]->getName());
        $this->assertEquals('Node2', $nodes[2]->getName());
        $this->assertEquals('Node3', $nodes[3]->getName());
        $this->assertEquals('Node5', $nodes[4]->getName());
    }

    public function testDelete()
    {
        $node2 = new Node();
        $node2->setName("Node2");
        $node2->setPath("/");
        $this->em->persist($node2);
        
        $node3 = new Node();
        $node3->setName("Node3");
        $node3->setPath("/");
        $this->em->persist($node3);
        
        $this->em->flush();
        
        $this->em->remove($node2);
        $this->em->flush();
        $this->em->clear();
        
        $repo = $this->em->getRepository('Sortable\\Fixture\\Node');
        $nodes = $repo->getBySortableGroups(array('path' => '/'));
        
        $this->assertEquals('Node1', $nodes[0]->getName());
        $this->assertEquals('Node3', $nodes[1]->getName());
        $this->assertEquals(0, $nodes[0]->getPosition());
        $this->assertEquals(1, $nodes[1]->getPosition());
    }
    
    public function testGroupByAssociation()
    {
        $category1 = new Category();
        $category1->setName("Category1");
        $this->em->persist($category1);
        $category2 = new Category();
        $category2->setName("Category2");
        $this->em->persist($category2);
        $this->em->flush();
        
        $item3 = new Item();
        $item3->setName("Item3");
        $item3->setCategory($category1);
        $this->em->persist($item3);
        
        $item4 = new Item();
        $item4->setName("Item4");
        $item4->setCategory($category1);
        $this->em->persist($item4);
        
        $this->em->flush();
        
        $item1 = new Item();
        $item1->setName("Item1");
        $item1->setPosition(0);
        $item1->setCategory($category1);
        $this->em->persist($item1);
        
        $item2 = new Item();
        $item2->setName("Item2");
        $item2->setPosition(0);
        $item2->setCategory($category1);
        $this->em->persist($item2);
        
        $item2 = new Item();
        $item2->setName("Item2_2");
        $item2->setPosition(0);
        $item2->setCategory($category2);
        $this->em->persist($item2);
        $this->em->flush();
        
        $item1 = new Item();
        $item1->setName("Item1_2");
        $item1->setPosition(0);
        $item1->setCategory($category2);
        $this->em->persist($item1);
        $this->em->flush();
        
        $this->em->clear();
        
        $repo = $this->em->getRepository('Sortable\\Fixture\\Category');
        $category1 = $repo->findOneByName('Category1');
        $category2 = $repo->findOneByName('Category2');
        
        $repo = $this->em->getRepository('Sortable\\Fixture\\Item');
        
        $items = $repo->getBySortableGroups(array('category' => $category1));
        
        $this->assertEquals("Item1", $items[0]->getName());
        $this->assertEquals("Category1", $items[0]->getCategory()->getName());
        
        $this->assertEquals("Item2", $items[1]->getName());
        $this->assertEquals("Category1", $items[1]->getCategory()->getName());
        
        $this->assertEquals("Item3", $items[2]->getName());
        $this->assertEquals("Category1", $items[2]->getCategory()->getName());
        
        $this->assertEquals("Item4", $items[3]->getName());
        $this->assertEquals("Category1", $items[3]->getCategory()->getName());
        
        $items = $repo->getBySortableGroups(array('category' => $category2));
        
        $this->assertEquals("Item1_2", $items[0]->getName());
        $this->assertEquals("Category2", $items[0]->getCategory()->getName());
        
        $this->assertEquals("Item2_2", $items[1]->getName());
        $this->assertEquals("Category2", $items[1]->getCategory()->getName());
    }
    
    protected function getUsedEntityFixtures()
    {
        return array(
            self::NODE,
            self::ITEM,
            self::CATEGORY
        );
    }
    
    private function populate()
    {
        $node = new Node();
        $node->setName("Node1");
        $node->setPath("/");
        
        $this->em->persist($node);
        $this->em->flush();
        $this->em->clear();
        $this->nodeId = $node->getId();
    }
}
