<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sortable;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Gedmo\Sortable\SortableListener;
use Gedmo\Tests\Sortable\Fixture\Author;
use Gedmo\Tests\Sortable\Fixture\Category;
use Gedmo\Tests\Sortable\Fixture\Customer;
use Gedmo\Tests\Sortable\Fixture\CustomerType;
use Gedmo\Tests\Sortable\Fixture\Event;
use Gedmo\Tests\Sortable\Fixture\Item;
use Gedmo\Tests\Sortable\Fixture\Node;
use Gedmo\Tests\Sortable\Fixture\NotifyNode;
use Gedmo\Tests\Sortable\Fixture\Paper;
use Gedmo\Tests\Sortable\Fixture\SimpleListItem;
use Gedmo\Tests\Tool\BaseTestCaseORM;

/**
 * These are tests for sortable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class SortableTest extends BaseTestCaseORM
{
    public const NODE = Node::class;
    public const NOTIFY_NODE = NotifyNode::class;
    public const ITEM = Item::class;
    public const CATEGORY = Category::class;
    public const SIMPLE_LIST_ITEM = SimpleListItem::class;
    public const AUTHOR = Author::class;
    public const PAPER = Paper::class;
    public const EVENT = Event::class;
    public const CUSTOMER = Customer::class;
    public const CUSTOMER_TYPE = CustomerType::class;

    /**
     * @var int|null
     */
    private $nodeId;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new SortableListener());

        $this->getDefaultMockSqliteEntityManager($evm);
        $this->populate();
    }

    public function testShouldSetSortPositionToInsertedNode(): void
    {
        $node = $this->em->find(self::NODE, $this->nodeId);
        static::assertSame(0, $node->getPosition());
    }

    public function testMoveLastPosition(): void
    {
        for ($i = 2; $i <= 10; ++$i) {
            $node = new Node();
            $node->setName('Node'.$i);
            $node->setPath('/');
            $this->em->persist($node);
        }
        $this->em->flush();

        $repo = $this->em->getRepository(self::NODE);

        $node = $repo->findOneBy(['position' => 0]);
        $node->setPosition(-1);
        $this->em->flush();

        for ($i = 0; $i <= 8; ++$i) {
            $node = $repo->findOneBy(['position' => $i]);
            static::assertNotNull($node);
            static::assertSame('Node'.($i + 2), $node->getName());
        }

        $node = $repo->findOneBy(['position' => 9]);
        static::assertNotNull($node);
        static::assertSame('Node1', $node->getName());
    }

    public function testShouldSortManyNewNodes(): void
    {
        for ($i = 2; $i <= 10; ++$i) {
            $node = new Node();
            $node->setName('Node'.$i);
            $node->setPath('/');
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

        static::assertCount(10, $nodes);
        static::assertSame('Node1', $nodes[0]->getName());
        static::assertSame(2, $nodes[2]->getPosition());
    }

    public function testShouldShiftPositionForward(): void
    {
        $node2 = new Node();
        $node2->setName('Node2');
        $node2->setPath('/');
        $this->em->persist($node2);

        $node = new Node();
        $node->setName('Node3');
        $node->setPath('/');
        $this->em->persist($node);

        $node = new Node();
        $node->setName('Node4');
        $node->setPath('/');
        $this->em->persist($node);

        $node = new Node();
        $node->setName('Node5');
        $node->setPath('/');
        $this->em->persist($node);

        $this->em->flush();

        static::assertSame(1, $node2->getPosition());
        $node2->setPosition(3);
        $this->em->persist($node2);
        $this->em->flush();

        $repo = $this->em->getRepository(self::NODE);
        $nodes = $repo->getBySortableGroups(['path' => '/']);

        static::assertSame('Node1', $nodes[0]->getName());
        static::assertSame('Node3', $nodes[1]->getName());
        static::assertSame('Node4', $nodes[2]->getName());
        static::assertSame('Node2', $nodes[3]->getName());
        static::assertSame('Node5', $nodes[4]->getName());

        for ($i = 0; $i < count($nodes); ++$i) {
            static::assertSame($i, $nodes[$i]->getPosition());
        }
    }

    public function testShouldShiftPositionsProperlyWhenMoreThanOneWasUpdated(): void
    {
        $node2 = new Node();
        $node2->setName('Node2');
        $node2->setPath('/');
        $this->em->persist($node2);

        $node3 = new Node();
        $node3->setName('Node3');
        $node3->setPath('/');
        $this->em->persist($node3);

        $node = new Node();
        $node->setName('Node4');
        $node->setPath('/');
        $this->em->persist($node);

        $node = new Node();
        $node->setName('Node5');
        $node->setPath('/');
        $this->em->persist($node);

        $this->em->flush();

        static::assertSame(1, $node2->getPosition());
        $node2->setPosition(3);
        $node3->setPosition(4);
        $this->em->persist($node2);
        $this->em->persist($node3);
        $this->em->flush();

        $repo = $this->em->getRepository(self::NODE);
        $nodes = $repo->getBySortableGroups(['path' => '/']);

        static::assertSame('Node1', $nodes[0]->getName());
        static::assertSame('Node4', $nodes[1]->getName());
        static::assertSame('Node5', $nodes[2]->getName());
        static::assertSame('Node2', $nodes[3]->getName());
        static::assertSame('Node3', $nodes[4]->getName());

        for ($i = 0; $i < count($nodes); ++$i) {
            static::assertSame($i, $nodes[$i]->getPosition());
        }
    }

    public function testShouldShiftPositionBackward(): void
    {
        $node = new Node();
        $node->setName('Node2');
        $node->setPath('/');
        $this->em->persist($node);

        $node = new Node();
        $node->setName('Node3');
        $node->setPath('/');
        $this->em->persist($node);

        $node2 = new Node();
        $node2->setName('Node4');
        $node2->setPath('/');
        $this->em->persist($node2);

        $node = new Node();
        $node->setName('Node5');
        $node->setPath('/');
        $this->em->persist($node);

        $this->em->flush();
        static::assertSame(3, $node2->getPosition());

        $node2->setPosition(1);
        $this->em->persist($node2);
        $this->em->flush();
        $this->em->clear(); // to reload from database

        $repo = $this->em->getRepository(self::NODE);
        $nodes = $repo->getBySortableGroups(['path' => '/']);

        static::assertSame('Node1', $nodes[0]->getName());
        static::assertSame('Node4', $nodes[1]->getName());
        static::assertSame('Node2', $nodes[2]->getName());
        static::assertSame('Node3', $nodes[3]->getName());
        static::assertSame('Node5', $nodes[4]->getName());

        for ($i = 0; $i < count($nodes); ++$i) {
            static::assertSame($i, $nodes[$i]->getPosition());
        }
    }

    public function testShouldSyncPositionAfterDelete(): void
    {
        $repo = $this->em->getRepository(self::NODE);

        $node2 = new Node();
        $node2->setName('Node2');
        $node2->setPath('/');
        $this->em->persist($node2);

        $node3 = new Node();
        $node3->setName('Node3');
        $node3->setPath('/');
        $this->em->persist($node3);

        $this->em->flush();

        $node1 = $repo->findOneBy(['name' => 'Node1']);
        $this->em->remove($node2);
        $this->em->flush();

        // test if synced on objects in memory correctly
        static::assertSame(0, $node1->getPosition());
        static::assertSame(1, $node3->getPosition());

        // test if persisted correctly
        $this->em->clear();
        $nodes = $repo->findAll();
        static::assertCount(2, $nodes);
        static::assertSame(0, $nodes[0]->getPosition());
        static::assertSame(1, $nodes[1]->getPosition());
    }

    /**
     * Test if the sorting is correct if multiple items are deleted.
     *
     * Example:
     *     Position | Element | Action | Expected Position
     *            0 | Node1   |        |                 0
     *            1 | Node2   | delete |
     *            2 | Node3   | delete |
     *            3 | Node4   |        |                 1
     */
    public function testShouldSyncPositionAfterMultipleDeletes(): void
    {
        $repo = $this->em->getRepository(self::NODE);

        $node2 = new Node();
        $node2->setName('Node2');
        $node2->setPath('/');
        $this->em->persist($node2);

        $node3 = new Node();
        $node3->setName('Node3');
        $node3->setPath('/');
        $this->em->persist($node3);

        $node4 = new Node();
        $node4->setName('Node4');
        $node4->setPath('/');
        $this->em->persist($node4);

        $this->em->flush();

        $node1 = $repo->findOneBy(['name' => 'Node1']);
        $this->em->remove($node2);
        $this->em->remove($node3);
        $this->em->flush();

        // test if synced on objects in memory correctly
        static::assertSame(0, $node1->getPosition());
        static::assertSame(1, $node4->getPosition());

        // test if persisted correctly
        $this->em->clear();
        $nodes = $repo->findAll();
        static::assertCount(2, $nodes);
        static::assertSame(0, $nodes[0]->getPosition());
        static::assertSame(1, $nodes[1]->getPosition());
    }

    /**
     * Test if the sorting is correct if multiple items are added and deleted.
     *
     * Example:
     *     Position | Element | Action | Expected Position
     *            0 | Node1   |        |                 0
     *            1 | Node2   | delete |
     *            2 | Node3   | delete |
     *            3 | Node4   |        |                 1
     *              | Node5   | add    |                 2
     *              | Node6   | add    |                 3
     */
    public function testShouldSyncPositionAfterMultipleAddsAndMultipleDeletes(): void
    {
        $repo = $this->em->getRepository(self::NODE);

        $node2 = new Node();
        $node2->setName('Node2');
        $node2->setPath('/');
        $this->em->persist($node2);

        $node3 = new Node();
        $node3->setName('Node3');
        $node3->setPath('/');
        $this->em->persist($node3);

        $node4 = new Node();
        $node4->setName('Node4');
        $node4->setPath('/');
        $this->em->persist($node4);

        $this->em->flush();

        $node1 = $repo->findOneBy(['name' => 'Node1']);

        $this->em->remove($node2);

        $node5 = new Node();
        $node5->setName('Node5');
        $node5->setPath('/');
        $this->em->persist($node5);

        $node6 = new Node();
        $node6->setName('Node6');
        $node6->setPath('/');
        $this->em->persist($node6);

        $this->em->remove($node3);

        $this->em->flush();

        // test if synced on objects in memory correctly
        static::assertSame(0, $node1->getPosition());
        static::assertSame(1, $node4->getPosition());
        static::assertSame(2, $node5->getPosition());
        static::assertSame(3, $node6->getPosition());

        // test if persisted correctly
        $this->em->clear();
        $nodes = $repo->findAll();
        static::assertCount(4, $nodes);
        static::assertSame(0, $nodes[0]->getPosition());
        static::assertSame('Node1', $nodes[0]->getName());
        static::assertSame(1, $nodes[1]->getPosition());
        static::assertSame('Node4', $nodes[1]->getName());
        static::assertSame(2, $nodes[2]->getPosition());
        static::assertSame('Node5', $nodes[2]->getName());
        static::assertSame(3, $nodes[3]->getPosition());
        static::assertSame('Node6', $nodes[3]->getName());
    }

    /**
     * This is a test case for issue #1209
     */
    public function testShouldRollbackPositionAfterExceptionOnDelete(): void
    {
        $repo = $this->em->getRepository(self::CUSTOMER_TYPE);

        $customerType1 = new CustomerType();
        $customerType1->setName('CustomerType1');
        $this->em->persist($customerType1);

        $customerType2 = new CustomerType();
        $customerType2->setName('CustomerType2');
        $this->em->persist($customerType2);

        $customerType3 = new CustomerType();
        $customerType3->setName('CustomerType3');
        $this->em->persist($customerType3);

        $customer = new Customer();
        $customer->setName('Customer');
        $customer->setType($customerType2);
        $this->em->persist($customer);

        $this->em->flush();

        try {
            // now delete the second customer type, which should fail
            // because of the foreign key reference
            $this->em->remove($customerType2);
            $this->em->flush();

            static::fail('Foreign key constraint violation exception not thrown.');
        } catch (ForeignKeyConstraintViolationException $e) {
            $customerTypes = $repo->findAll();

            static::assertCount(3, $customerTypes);

            static::assertSame(0, $customerTypes[0]->getPosition(), 'The sorting position has not been rolled back.');
            static::assertSame(1, $customerTypes[1]->getPosition(), 'The sorting position has not been rolled back.');
            static::assertSame(2, $customerTypes[2]->getPosition(), 'The sorting position has not been rolled back.');
        }
    }

    public function testShouldGroupByAssociation(): void
    {
        $category1 = new Category();
        $category1->setName('Category1');
        $this->em->persist($category1);
        $category2 = new Category();
        $category2->setName('Category2');
        $this->em->persist($category2);
        $this->em->flush();

        $item3 = new Item();
        $item3->setName('Item3');
        $item3->setCategory($category1);
        $this->em->persist($item3);

        $item4 = new Item();
        $item4->setName('Item4');
        $item4->setCategory($category1);
        $this->em->persist($item4);

        $this->em->flush();

        $item1 = new Item();
        $item1->setName('Item1');
        $item1->setPosition(0);
        $item1->setCategory($category1);
        $this->em->persist($item1);

        $item2 = new Item();
        $item2->setName('Item2');
        $item2->setPosition(0);
        $item2->setCategory($category1);
        $this->em->persist($item2);

        $item2 = new Item();
        $item2->setName('Item2_2');
        $item2->setPosition(0);
        $item2->setCategory($category2);
        $this->em->persist($item2);
        $this->em->flush();

        $item1 = new Item();
        $item1->setName('Item1_2');
        $item1->setPosition(0);
        $item1->setCategory($category2);
        $this->em->persist($item1);
        $this->em->flush();

        $repo = $this->em->getRepository(self::CATEGORY);
        $category1 = $repo->findOneBy(['name' => 'Category1']);
        $category2 = $repo->findOneBy(['name' => 'Category2']);

        $repo = $this->em->getRepository(self::ITEM);

        $items = $repo->getBySortableGroups(['category' => $category1]);

        static::assertSame('Item1', $items[0]->getName());
        static::assertSame('Category1', $items[0]->getCategory()->getName());

        static::assertSame('Item2', $items[1]->getName());
        static::assertSame('Category1', $items[1]->getCategory()->getName());

        static::assertSame('Item3', $items[2]->getName());
        static::assertSame('Category1', $items[2]->getCategory()->getName());

        static::assertSame('Item4', $items[3]->getName());
        static::assertSame('Category1', $items[3]->getCategory()->getName());

        $items = $repo->getBySortableGroups(['category' => $category2]);

        static::assertSame('Item1_2', $items[0]->getName());
        static::assertSame('Category2', $items[0]->getCategory()->getName());

        static::assertSame('Item2_2', $items[1]->getName());
        static::assertSame('Category2', $items[1]->getCategory()->getName());
    }

    public function testShouldGroupByNewAssociation(): void
    {
        $category1 = new Category();
        $category1->setName('Category1');

        $item1 = new Item();
        $item1->setName('Item1');
        $item1->setPosition(0);
        $item1->setCategory($category1);
        $this->em->persist($item1);
        $this->em->persist($category1);
        $this->em->flush();

        $repo = $this->em->getRepository(self::CATEGORY);
        $category1 = $repo->findOneBy(['name' => 'Category1']);

        $repo = $this->em->getRepository(self::ITEM);

        $items = $repo->getBySortableGroups(['category' => $category1]);

        static::assertSame('Item1', $items[0]->getName());
        static::assertSame('Category1', $items[0]->getCategory()->getName());
    }

    public function testShouldGroupByDateTimeValue(): void
    {
        $event1 = new Event();
        $event1->setDateTime(new \DateTime('2012-09-15 00:00:00'));
        $event1->setName('Event1');
        $this->em->persist($event1);
        $event2 = new Event();
        $event2->setDateTime(new \DateTime('2012-09-15 00:00:00'));
        $event2->setName('Event2');
        $this->em->persist($event2);
        $event3 = new Event();
        $event3->setDateTime(new \DateTime('2012-09-16 00:00:00'));
        $event3->setName('Event3');
        $this->em->persist($event3);

        $this->em->flush();

        $event4 = new Event();
        $event4->setDateTime(new \DateTime('2012-09-15 00:00:00'));
        $event4->setName('Event4');
        $this->em->persist($event4);

        $event5 = new Event();
        $event5->setDateTime(new \DateTime('2012-09-16 00:00:00'));
        $event5->setName('Event5');
        $this->em->persist($event5);

        $this->em->flush();

        static::assertSame(0, $event1->getPosition());
        static::assertSame(1, $event2->getPosition());
        static::assertSame(0, $event3->getPosition());
        static::assertSame(2, $event4->getPosition());
        static::assertSame(1, $event5->getPosition());
    }

    public function testShouldFixIssue226(): void
    {
        $paper1 = new Paper();
        $paper1->setName('Paper1');
        $this->em->persist($paper1);

        $paper2 = new Paper();
        $paper2->setName('Paper2');
        $this->em->persist($paper2);

        $author1 = new Author();
        $author1->setName('Author1');
        $author1->setPaper($paper1);

        $author2 = new Author();
        $author2->setName('Author2');
        $author2->setPaper($paper1);

        $author3 = new Author();
        $author3->setName('Author3');
        $author3->setPaper($paper2);

        $this->em->persist($author1);
        $this->em->persist($author2);
        $this->em->persist($author3);
        $this->em->flush();

        static::assertSame(0, $author1->getPosition());
        static::assertSame(1, $author2->getPosition());
        static::assertSame(0, $author3->getPosition());

        // update position
        $author3->setPaper($paper1);
        $author3->setPosition(0); // same as before, no changes
        $this->em->persist($author3);
        $this->em->flush();

        static::assertSame(1, $author1->getPosition());
        static::assertSame(2, $author2->getPosition());
        static::assertSame(0, $author3->getPosition());

        // this is failing for whatever reasons
        $author3->setPosition(0);
        $this->em->persist($author3);
        $this->em->flush();

        $this->em->clear(); // @TODO: this should not be required

        $author1 = $this->em->find(self::AUTHOR, $author1->getId());
        $author2 = $this->em->find(self::AUTHOR, $author2->getId());
        $author3 = $this->em->find(self::AUTHOR, $author3->getId());

        static::assertSame(1, $author1->getPosition());
        static::assertSame(2, $author2->getPosition());
        static::assertSame(0, $author3->getPosition());
    }

    public function testShouldFixIssue1445(): void
    {
        $paper1 = new Paper();
        $paper1->setName('Paper1');
        $this->em->persist($paper1);

        $paper2 = new Paper();
        $paper2->setName('Paper2');
        $this->em->persist($paper2);

        $author1 = new Author();
        $author1->setName('Author1');
        $author1->setPaper($paper1);

        $author2 = new Author();
        $author2->setName('Author2');
        $author2->setPaper($paper1);

        $this->em->persist($author1);
        $this->em->persist($author2);
        $this->em->flush();

        static::assertSame(0, $author1->getPosition());
        static::assertSame(1, $author2->getPosition());

        // update position
        $author2->setPaper($paper2);
        $author2->setPosition(0); // Position has changed author2 was at position 1 in paper1 and now 0 in paper2, so it can be in changeSets
        $this->em->persist($author2);
        $this->em->flush();

        static::assertSame(0, $author1->getPosition());
        static::assertSame(0, $author2->getPosition());

        $this->em->clear(); // @TODO: this should not be required

        $repo = $this->em->getRepository(self::AUTHOR);
        $author1 = $repo->findOneBy(['id' => $author1->getId()]);
        $author2 = $repo->findOneBy(['id' => $author2->getId()]);

        static::assertSame(0, $author1->getPosition());
        static::assertSame(0, $author2->getPosition());
    }

    public function testShouldFixIssue1462(): void
    {
        $paper1 = new Paper();
        $paper1->setName('Paper1');
        $this->em->persist($paper1);

        $paper2 = new Paper();
        $paper2->setName('Paper2');
        $this->em->persist($paper2);

        $author1 = new Author();
        $author1->setName('Author1');
        $author1->setPaper($paper1);

        $author2 = new Author();
        $author2->setName('Author2');
        $author2->setPaper($paper1);

        $author3 = new Author();
        $author3->setName('Author3');
        $author3->setPaper($paper2);

        $author4 = new Author();
        $author4->setName('Author4');
        $author4->setPaper($paper2);

        $author5 = new Author();
        $author5->setName('Author5');
        $author5->setPaper($paper1);

        $this->em->persist($author1);
        $this->em->persist($author2);
        $this->em->persist($author3);
        $this->em->persist($author4);
        $this->em->persist($author5);
        $this->em->flush();

        static::assertSame(0, $author1->getPosition());
        static::assertSame(1, $author2->getPosition());
        static::assertSame(2, $author5->getPosition());

        static::assertSame(0, $author3->getPosition());
        static::assertSame(1, $author4->getPosition());

        // update paper: the position is still 1.
        $author4->setPaper($paper1);
        $this->em->persist($author4);
        $this->em->flush();

        static::assertSame(0, $author1->getPosition());
        static::assertSame(1, $author4->getPosition());
        static::assertSame(2, $author2->getPosition());
        static::assertSame(3, $author5->getPosition());

        static::assertSame(0, $author3->getPosition());

        $this->em->clear(); // @TODO: this should not be required

        $repo = $this->em->getRepository(self::AUTHOR);
        $author1 = $repo->findOneBy(['id' => $author1->getId()]);
        $author2 = $repo->findOneBy(['id' => $author2->getId()]);
        $author3 = $repo->findOneBy(['id' => $author3->getId()]);
        $author4 = $repo->findOneBy(['id' => $author4->getId()]);
        $author5 = $repo->findOneBy(['id' => $author5->getId()]);

        static::assertSame(0, $author1->getPosition());
        static::assertSame(1, $author4->getPosition());
        static::assertSame(2, $author2->getPosition());
        static::assertSame(3, $author5->getPosition());

        static::assertSame(0, $author3->getPosition());
    }

    public function testPositionShouldBeTheSameAfterFlush(): void
    {
        $nodes = [];
        for ($i = 2; $i <= 10; ++$i) {
            $node = new Node();
            $node->setName('Node'.$i);
            $node->setPath('/');
            $this->em->persist($node);
            $nodes[] = $node;
        }
        $this->em->flush();

        $node1 = $this->em->find(self::NODE, $this->nodeId);
        $node1->setPosition(5);

        $this->em->flush();

        static::assertSame(5, $node1->getPosition());

        $this->em->detach($node1);
        $node1 = $this->em->find(self::NODE, $this->nodeId);
        static::assertSame(5, $node1->getPosition());
    }

    public function testIncrementPositionOfLastObjectByOne(): void
    {
        $node0 = $this->em->find(self::NODE, $this->nodeId);

        $nodes = [$node0];

        for ($i = 2; $i <= 5; ++$i) {
            $node = new Node();
            $node->setName('Node'.$i);
            $node->setPath('/');
            $this->em->persist($node);
            $nodes[] = $node;
        }
        $this->em->flush();

        static::assertSame(4, $nodes[4]->getPosition());

        $node4NewPosition = $nodes[4]->getPosition();
        ++$node4NewPosition;

        $nodes[4]->setPosition($node4NewPosition);

        $this->em->persist($nodes[4]);
        $this->em->flush();

        static::assertSame(4, $nodes[4]->getPosition());
    }

    public function testSetOutOfBoundsHighPosition(): void
    {
        $node0 = $this->em->find(self::NODE, $this->nodeId);

        $nodes = [$node0];

        for ($i = 2; $i <= 5; ++$i) {
            $node = new Node();
            $node->setName('Node'.$i);
            $node->setPath('/');
            $this->em->persist($node);
            $nodes[] = $node;
        }
        $this->em->flush();

        static::assertSame(4, $nodes[4]->getPosition());

        $nodes[4]->setPosition(100);

        $this->em->persist($nodes[4]);
        $this->em->flush();

        static::assertSame(4, $nodes[4]->getPosition());
    }

    public function testShouldFixIssue1809(): void
    {
        $manager = $this->em;
        $nodes = [];
        for ($i = 1; $i <= 3; ++$i) {
            $node = new NotifyNode();
            $node->setName('Node'.$i);
            $node->setPath('/');
            $manager->persist($node);
            $nodes[] = $node;
            $manager->flush();
        }
        foreach ($nodes as $i => $node) {
            $position = $node->getPosition();
            static::assertSame($i, $position);
        }
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::NODE,
            self::NOTIFY_NODE,
            self::ITEM,
            self::CATEGORY,
            self::SIMPLE_LIST_ITEM,
            self::AUTHOR,
            self::PAPER,
            self::EVENT,
            self::CUSTOMER,
            self::CUSTOMER_TYPE,
        ];
    }

    private function populate(): void
    {
        $node = new Node();
        $node->setName('Node1');
        $node->setPath('/');

        $this->em->persist($node);
        $this->em->flush();
        $this->nodeId = $node->getId();
    }
}
