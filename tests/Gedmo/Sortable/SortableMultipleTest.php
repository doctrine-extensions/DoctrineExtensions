<?php

namespace Gedmo\Sortable;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Sortable\Fixture\Book;
use Tool\BaseTestCaseORM;
use Sortable\Fixture\Node;
use Sortable\Fixture\Item;
use Sortable\Fixture\Category;
use Sortable\Fixture\SimpleListItem;
use Sortable\Fixture\Author;
use Sortable\Fixture\Paper;
use Sortable\Fixture\Event;
use Sortable\Fixture\Customer;
use Sortable\Fixture\CustomerType;

/**
 * These are tests for multiple sortable behavior
 */
class SortableMultipleTest extends BaseTestCaseORM
{
    const CATEGORY = 'Sortable\\Fixture\\Category';
    const AUTHOR = 'Sortable\\Fixture\\Author';
    const BOOK = 'Sortable\\Fixture\\Book';

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new SortableListener());

        $this->getMockSqliteEntityManager($evm);
    }

    /**
     * @test
     */
    public function shouldSetBothPositions()
    {
        $author = new Author();
        $author->setName('test');

        $this->em->persist($author);

        $category = new Category();
        $category->setName('test');

        $this->em->persist($category);

        $book = new Book();
        $book->setAuthor($author);
        $book->setCategory($category);

        $this->em->persist($book);
        $this->em->flush();

        $this->assertEquals(0, $book->getPositionByAuthor());
        $this->assertEquals(0, $book->getPositionByCategory());
    }

    private function get3Books()
    {
        $author = new Author();
        $author->setName('test');

        $this->em->persist($author);

        $category = new Category();
        $category->setName('test');

        $this->em->persist($category);

        $book1 = new Book();
        $book1->setAuthor($author);
        $book1->setCategory($category);

        $book2 = new Book();
        $book2->setAuthor($author);
        $book2->setCategory($category);

        $book3 = new Book();
        $book3->setAuthor($author);
        $book3->setCategory($category);

        $this->em->persist($book1);
        $this->em->persist($book2);
        $this->em->persist($book3);
        $this->em->flush();

        return compact('author', 'category', 'book1', 'book2', 'book3');
    }

    /**
     * @test
     */
    public function shouldUpdateOnlyOnePosition()
    {
        /**
         * @var Author $author
         * @var Category $category
         * @var Book $book1
         * @var Book $book2
         * @var Book $book3
         */
        extract($this->get3Books());

        $this->assertEquals(0, $book1->getPositionByAuthor());
        $this->assertEquals(1, $book2->getPositionByAuthor());
        $this->assertEquals(2, $book3->getPositionByAuthor());

        $this->assertEquals(0, $book1->getPositionByCategory());
        $this->assertEquals(1, $book2->getPositionByCategory());
        $this->assertEquals(2, $book3->getPositionByCategory());

        $book3->setPositionByAuthor(0);
        $this->em->flush();

        // author position should update
        $this->assertEquals(1, $book1->getPositionByAuthor());
        $this->assertEquals(2, $book2->getPositionByAuthor());
        $this->assertEquals(0, $book3->getPositionByAuthor());

        // category position should be unchanged
        $this->assertEquals(0, $book1->getPositionByCategory());
        $this->assertEquals(1, $book2->getPositionByCategory());
        $this->assertEquals(2, $book3->getPositionByCategory());
    }

    /**
     * @test
     */
    public function shouldUpdateBothPositions()
    {
        /**
         * @var Author $author
         * @var Category $category
         * @var Book $book1
         * @var Book $book2
         * @var Book $book3
         */
        extract($this->get3Books());

        $this->assertEquals(0, $book1->getPositionByAuthor());
        $this->assertEquals(1, $book2->getPositionByAuthor());
        $this->assertEquals(2, $book3->getPositionByAuthor());

        $this->assertEquals(0, $book1->getPositionByCategory());
        $this->assertEquals(1, $book2->getPositionByCategory());
        $this->assertEquals(2, $book3->getPositionByCategory());

        $book3->setPositionByAuthor(0);
        $book3->setPositionByCategory(0);
        $this->em->flush();

        $this->assertEquals(1, $book1->getPositionByAuthor());
        $this->assertEquals(2, $book2->getPositionByAuthor());
        $this->assertEquals(0, $book3->getPositionByAuthor());

        $this->assertEquals(1, $book1->getPositionByCategory());
        $this->assertEquals(2, $book2->getPositionByCategory());
        $this->assertEquals(0, $book3->getPositionByCategory());
    }

    /**
     * @test
     */
    public function shouldUpdateOnCategoryChange()
    {
        /**
         * @var Author $author
         * @var Category $category
         * @var Book $book1
         * @var Book $book2
         * @var Book $book3
         */
        extract($this->get3Books());

        $this->assertEquals(0, $book1->getPositionByAuthor());
        $this->assertEquals(1, $book2->getPositionByAuthor());
        $this->assertEquals(2, $book3->getPositionByAuthor());

        $this->assertEquals(0, $book1->getPositionByCategory());
        $this->assertEquals(1, $book2->getPositionByCategory());
        $this->assertEquals(2, $book3->getPositionByCategory());

        $book3->setAuthor(null);
        $this->em->flush();

        $this->assertEquals(0, $book1->getPositionByAuthor());
        $this->assertEquals(1, $book2->getPositionByAuthor());
        $this->assertEquals(0, $book3->getPositionByAuthor());

        // should remain unchanged
        $this->assertEquals(0, $book1->getPositionByCategory());
        $this->assertEquals(1, $book2->getPositionByCategory());
        $this->assertEquals(2, $book3->getPositionByCategory());

        $book3->setCategory(null);
        $this->em->flush();

        $this->assertEquals(0, $book1->getPositionByAuthor());
        $this->assertEquals(1, $book2->getPositionByAuthor());
        $this->assertEquals(0, $book3->getPositionByAuthor());

        $this->assertEquals(0, $book1->getPositionByCategory());
        $this->assertEquals(1, $book2->getPositionByCategory());
        $this->assertEquals(0, $book3->getPositionByCategory());
    }

    /**
     * @test
     */
    public function shouldUpdateBothPositionsOnMutualCategoryChange()
    {
        /**
         * @var Author $author
         * @var Category $category
         * @var Book $book1
         * @var Book $book2
         * @var Book $book3
         */
        extract($this->get3Books());

        $this->assertEquals(0, $book1->getPositionByAuthor());
        $this->assertEquals(1, $book2->getPositionByAuthor());
        $this->assertEquals(2, $book3->getPositionByAuthor());

        $this->assertEquals(0, $book1->getPositionByCategory());
        $this->assertEquals(1, $book2->getPositionByCategory());
        $this->assertEquals(2, $book3->getPositionByCategory());

        $book3->setPublisher('penguin');
        $this->em->flush();

        $this->assertEquals(0, $book1->getPositionByAuthor());
        $this->assertEquals(1, $book2->getPositionByAuthor());
        $this->assertEquals(0, $book3->getPositionByAuthor());

        $this->assertEquals(0, $book1->getPositionByCategory());
        $this->assertEquals(1, $book2->getPositionByCategory());
        $this->assertEquals(0, $book3->getPositionByCategory());

        $book2->setPublisher('penguin');
        $this->em->flush();

        $this->assertEquals(0, $book1->getPositionByAuthor());
        $this->assertEquals(1, $book2->getPositionByAuthor());
        $this->assertEquals(0, $book3->getPositionByAuthor());

        $this->assertEquals(0, $book1->getPositionByCategory());
        $this->assertEquals(1, $book2->getPositionByCategory());
        $this->assertEquals(0, $book3->getPositionByCategory());

        $book1->setPublisher('penguin');
        $this->em->flush();

        $this->assertEquals(0, $book1->getPositionByAuthor());
        $this->assertEquals(2, $book2->getPositionByAuthor());
        $this->assertEquals(1, $book3->getPositionByAuthor());

        $this->assertEquals(0, $book1->getPositionByCategory());
        $this->assertEquals(2, $book2->getPositionByCategory());
        $this->assertEquals(1, $book3->getPositionByCategory());
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::CATEGORY,
            self::AUTHOR,
            self::BOOK,
        );
    }
}
