<?php

namespace Sortable;

use Doctrine\Common\EventManager;
use TestTool\ObjectManagerTestCase;
use Fixture\Sortable\Node;
use Fixture\Sortable\Item;
use Fixture\Sortable\Category;
use Fixture\Sortable\SimpleListItem;
use Fixture\Sortable\Author;
use Fixture\Sortable\Paper;
use Fixture\Sortable\Event;
use Gedmo\Sortable\SortableListener;

class SortableTest extends ObjectManagerTestCase
{
    const NODE = 'Fixture\Sortable\Node';
    const ITEM = 'Fixture\Sortable\Item';
    const CATEGORY = 'Fixture\Sortable\Category';
    const SIMPLE_LIST_ITEM = 'Fixture\Sortable\SimpleListItem';
    const AUTHOR = 'Fixture\Sortable\Author';
    const PAPER = 'Fixture\Sortable\Paper';
    const EVENT = 'Fixture\Sortable\Event';

    private $nodeId, $em;

    protected function setUp()
    {
        $evm = new EventManager;
        $evm->addEventSubscriber(new SortableListener);

        $this->em = $this->createEntityManager($evm);
        $this->createSchema($this->em, array(
            self::NODE,
            self::ITEM,
            self::CATEGORY,
            self::SIMPLE_LIST_ITEM,
            self::AUTHOR,
            self::PAPER,
            self::EVENT,
        ));
        $this->populate();
    }

    protected function tearDown()
    {
        $this->releaseEntityManager($this->em);
    }

    /**
     * @test
     */
    function shouldSetSortPositionToInsertedNode()
    {
        $node = $this->em->find(self::NODE, $this->nodeId);
        $this->assertSame(0, $node->getPosition());
    }

    /**
     * @test
     */
    function shouldSortManyNewNodes()
    {
        for ($i = 2; $i <= 10; $i++) {
            $node = new Node();
            $node->setName("Node".$i);
            $node->setPath("/");
            $this->em->persist($node);
        }
        $this->em->flush();

        $dql = 'SELECT node FROM '.self::NODE.' node';
        $dql .= ' WHERE node.path = :path ORDER BY node.position';
        $nodes = $this->em
            ->createQuery($dql)
            ->setParameter('path', '/')
            ->getResult()
        ;

        $this->assertCount(10, $nodes);
        $this->assertSame('Node1', $nodes[0]->getName());
        $this->assertSame(2, $nodes[2]->getPosition());
    }

    /**
     * @test
     */
    function shouldShiftPositionForward()
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

        $this->assertSame(1, $node2->getPosition());
        $node2->setPosition(3);
        $this->em->persist($node2);
        $this->em->flush();

        $repo = $this->em->getRepository(self::NODE);
        $nodes = $repo->getBySortableGroups(array('path' => '/'));

        $this->assertSame('Node1', $nodes[0]->getName());
        $this->assertSame('Node3', $nodes[1]->getName());
        $this->assertSame('Node4', $nodes[2]->getName());
        $this->assertSame('Node2', $nodes[3]->getName());
        $this->assertSame('Node5', $nodes[4]->getName());

        for ($i = 0; $i < count($nodes); $i++) {
            $this->assertSame($i, $nodes[$i]->getPosition());
        }
    }

    /**
     * @test
     */
    public function shouldShiftPositionBackward()
    {
        $node = new Node;
        $node->setName("Node2");
        $node->setPath("/");
        $this->em->persist($node);

        $node = new Node;
        $node->setName("Node3");
        $node->setPath("/");
        $this->em->persist($node);

        $node2 = new Node;
        $node2->setName("Node4");
        $node2->setPath("/");
        $this->em->persist($node2);

        $node = new Node;
        $node->setName("Node5");
        $node->setPath("/");
        $this->em->persist($node);

        $this->em->flush();
        $this->assertSame(3, $node2->getPosition());


        $node2->setPosition(1);
        $this->em->persist($node2);
        $this->em->flush();
        $this->em->clear(); // to reload from database

        $repo = $this->em->getRepository(self::NODE);
        $nodes = $repo->getBySortableGroups(array('path' => '/'));

        $this->assertSame('Node1', $nodes[0]->getName());
        $this->assertSame('Node4', $nodes[1]->getName());
        $this->assertSame('Node2', $nodes[2]->getName());
        $this->assertSame('Node3', $nodes[3]->getName());
        $this->assertSame('Node5', $nodes[4]->getName());

        for ($i = 0; $i < count($nodes); $i++) {
            $this->assertSame($i, $nodes[$i]->getPosition());
        }
    }

    /**
     * @test
     */
    public function shouldSyncPositionAfterDelete()
    {
        $repo = $this->em->getRepository(self::NODE);

        $node2 = new Node;
        $node2->setName("Node2");
        $node2->setPath("/");
        $this->em->persist($node2);

        $node3 = new Node;
        $node3->setName("Node3");
        $node3->setPath("/");
        $this->em->persist($node3);

        $this->em->flush();

        $node1 = $repo->findOneByName('Node1');
        $this->em->remove($node2);
        $this->em->flush();

        $this->assertSame(0, $node1->getPosition());
        $this->assertSame(1, $node3->getPosition());
    }

    /**
     * @test
     */
    public function shouldGroupByAssociation()
    {
        $category1 = new Category;
        $category1->setName("Category1");
        $this->em->persist($category1);
        $category2 = new Category;
        $category2->setName("Category2");
        $this->em->persist($category2);
        $this->em->flush();

        $item3 = new Item;
        $item3->setName("Item3");
        $item3->setCategory($category1);
        $this->em->persist($item3);

        $item4 = new Item;
        $item4->setName("Item4");
        $item4->setCategory($category1);
        $this->em->persist($item4);

        $this->em->flush();

        $item1 = new Item;
        $item1->setName("Item1");
        $item1->setPosition(0);
        $item1->setCategory($category1);
        $this->em->persist($item1);

        $item2 = new Item;
        $item2->setName("Item2");
        $item2->setPosition(0);
        $item2->setCategory($category1);
        $this->em->persist($item2);

        $item2 = new Item;
        $item2->setName("Item2_2");
        $item2->setPosition(0);
        $item2->setCategory($category2);
        $this->em->persist($item2);
        $this->em->flush();

        $item1 = new Item;
        $item1->setName("Item1_2");
        $item1->setPosition(0);
        $item1->setCategory($category2);
        $this->em->persist($item1);
        $this->em->flush();

        $repo = $this->em->getRepository(self::ITEM);
        $items = $repo->getBySortableGroups(array('category' => $category1));

        $this->assertSame("Item1", $items[0]->getName());
        $this->assertSame("Category1", $items[0]->getCategory()->getName());

        $this->assertSame("Item2", $items[1]->getName());
        $this->assertSame("Category1", $items[1]->getCategory()->getName());

        $this->assertSame("Item3", $items[2]->getName());
        $this->assertSame("Category1", $items[2]->getCategory()->getName());

        $this->assertSame("Item4", $items[3]->getName());
        $this->assertSame("Category1", $items[3]->getCategory()->getName());

        $items = $repo->getBySortableGroups(array('category' => $category2));

        $this->assertSame("Item1_2", $items[0]->getName());
        $this->assertSame("Category2", $items[0]->getCategory()->getName());

        $this->assertSame("Item2_2", $items[1]->getName());
        $this->assertSame("Category2", $items[1]->getCategory()->getName());
    }

    /**
     * @test
     */
    public function shouldGroupByDateTimeValue()
    {
        $event1 = new Event;
        $event1->setDateTime(new \DateTime("2012-09-15 00:00:00"));
        $event1->setName("Event1");
        $this->em->persist($event1);
        $event2 = new Event;
        $event2->setDateTime(new \DateTime("2012-09-15 00:00:00"));
        $event2->setName("Event2");
        $this->em->persist($event2);
        $event3 = new Event;
        $event3->setDateTime(new \DateTime("2012-09-16 00:00:00"));
        $event3->setName("Event3");
        $this->em->persist($event3);

        $this->em->flush();

        $event4 = new Event;
        $event4->setDateTime(new \DateTime("2012-09-15 00:00:00"));
        $event4->setName("Event4");
        $this->em->persist($event4);

        $event5 = new Event;
        $event5->setDateTime(new \DateTime("2012-09-16 00:00:00"));
        $event5->setName("Event5");
        $this->em->persist($event5);

        $this->em->flush();

        $this->assertSame(0, $event1->getPosition());
        $this->assertSame(1, $event2->getPosition());
        $this->assertSame(0, $event3->getPosition());
        $this->assertSame(2, $event4->getPosition());
        $this->assertSame(1, $event5->getPosition());
    }

    /**
     * @test
     */
    public function shouldFixIssue219()
    {
        $item1 = new SimpleListItem;
        $item1->setName("Item 1");
        $this->em->persist($item1);

        $this->em->flush();

        $item1->setName("Update...");
        $item1->setPosition(1);
        $this->em->persist($item1);
        $this->em->flush();

        $this->em->remove($item1);
        $this->em->flush();
    }

    /**
     * @test
     */
    public function shouldFixIssue226()
    {
        $paper1 = new Paper;
        $paper1->setName("Paper1");
        $this->em->persist($paper1);

        $paper2 = new Paper;
        $paper2->setName("Paper2");
        $this->em->persist($paper2);

        $author1 = new Author;
        $author1->setName("Author1");
        $author1->setPaper($paper1);

        $author2 = new Author;
        $author2->setName("Author2");
        $author2->setPaper($paper1);

        $author3 = new Author;
        $author3->setName("Author3");
        $author3->setPaper($paper2);

        $this->em->persist($author1);
        $this->em->persist($author2);
        $this->em->persist($author3);
        $this->em->flush();

        $this->assertSame(0, $author1->getPosition());
        $this->assertSame(1, $author2->getPosition());
        $this->assertSame(0, $author3->getPosition());

        //update position
        $author3->setPaper($paper1);
        $author3->setPosition(0); // same as before, no changes
        $this->em->persist($author3);
        $this->em->flush();

        $this->assertSame(0, $author1->getPosition());
        $this->assertSame(1, $author2->getPosition());
        // it is 2 because the changeset for position is NONE and theres a new group, it will recalculate
        $this->assertSame(2, $author3->getPosition());

        // this is failing for whatever reasons
        $author3->setPosition(0);
        $this->em->persist($author3);
        $this->em->flush();

        $this->em->clear(); // @TODO: this should not be required

        $author1 = $this->em->find(self::AUTHOR, $author1->getId());
        $author2 = $this->em->find(self::AUTHOR, $author2->getId());
        $author3 = $this->em->find(self::AUTHOR, $author3->getId());

        $this->assertSame(1, $author1->getPosition());
        $this->assertSame(2, $author2->getPosition());
        $this->assertSame(0, $author3->getPosition());
    }

    /**
     * @test
     */
    function shouldFixIssue275()
    {
        $nodes = array();
        for ($i = 2; $i <= 10; $i++) {
            $node = new Node();
            $node->setName("Node".$i);
            $node->setPath("/");
            $this->em->persist($node);
            $nodes[] = $node;
        }
        $this->em->flush();

        $node1 = $this->em->find(self::NODE, $this->nodeId);
        $this->em->remove($node1);
        $this->em->flush();

        for ($i = 1; $i <= 9; $i++) {
            $nodes[$i-1]->setPosition($i);
            $this->em->persist($nodes[$i-1]);
        }
        $this->em->flush();
    }

    /**
     * @test
     */
    function positionShouldBeTheSameAfterFlush()
    {
        $nodes = array();
        for ($i = 2; $i <= 10; $i++) {
            $node = new Node();
            $node->setName("Node".$i);
            $node->setPath("/");
            $this->em->persist($node);
            $nodes[] = $node;
        }
        $this->em->flush();

        $node1 = $this->em->find(self::NODE, $this->nodeId);
        $node1->setPosition(5);

        $this->em->flush();

        $this->assertSame(5, $node1->getPosition());

        $this->em->detach($node1);
        $node1 = $this->em->find(self::NODE, $this->nodeId);
        $this->assertSame(5, $node1->getPosition());
    }

    /**
     * @test
     */
    function testIncrementPositionOfLastObjectByOne()
    {
        $node0 = $this->em->find(self::NODE, $this->nodeId);

        $nodes = array($node0);

        for ($i = 2; $i <= 5; $i++) {
            $node = new Node();
            $node->setName("Node".$i);
            $node->setPath("/");
            $this->em->persist($node);
            $nodes[] = $node;
        }
        $this->em->flush();

        $this->assertEquals(4, $nodes[4]->getPosition());
        
        $node4NewPosition = $nodes[4]->getPosition();
        $node4NewPosition++;

        $nodes[4]->setPosition($node4NewPosition);

        $this->em->persist($nodes[4]);
        $this->em->flush();

        $this->assertEquals(4, $nodes[4]->getPosition());
    }

    /**
     * @test
     */
    function testSetOutOfBoundsHighPosition()
    {
        $node0 = $this->em->find(self::NODE, $this->nodeId);

        $nodes = array($node0);

        for ($i = 2; $i <= 5; $i++) {
            $node = new Node();
            $node->setName("Node".$i);
            $node->setPath("/");
            $this->em->persist($node);
            $nodes[] = $node;
        }
        $this->em->flush();

        $this->assertEquals(4, $nodes[4]->getPosition());

        $nodes[4]->setPosition(100);

        $this->em->persist($nodes[4]);
        $this->em->flush();

        $this->assertEquals(4, $nodes[4]->getPosition());
    }

    private function populate()
    {
        $node = new Node;
        $node->setName("Node1");
        $node->setPath("/");

        $this->em->persist($node);
        $this->em->flush();
        $this->nodeId = $node->getId();
    }
}
