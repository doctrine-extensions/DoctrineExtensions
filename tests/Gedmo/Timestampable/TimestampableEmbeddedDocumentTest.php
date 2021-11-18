<?php

namespace Gedmo\Tests\Timestampable;

use Doctrine\Common\EventManager;
use Gedmo\Tests\Timestampable\Fixture\Document\Book;
use Gedmo\Tests\Timestampable\Fixture\Document\Tag;
use Gedmo\Tests\Tool\BaseTestCaseMongoODM;
use Gedmo\Timestampable\TimestampableListener;

/**
 * These are tests for Timestampable behavior ODM implementation
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class TimestampableEmbeddedDocumentTest extends BaseTestCaseMongoODM
{
    public const BOOK = Book::class;

    protected function setUp(): void
    {
        parent::setUp();
        $evm = new EventManager();
        $evm->addEventSubscriber(new TimestampableListener());

        $this->getMockDocumentManager($evm);
    }

    public function testPersistEmbeddedDocumentWithParent()
    {
        $tag1 = new Tag();
        $tag1->setName('cats');

        $tag2 = new Tag();
        $tag2->setName('dogs');

        $book = new Book();
        $book->setTitle('Cats & Dogs');
        $book->addTag($tag1);
        $book->addTag($tag2);

        $this->dm->persist($book);
        $this->dm->flush();
        $this->dm->clear();

        $repo = $this->dm->getRepository(self::BOOK);

        $bookFromRepo = $repo->findOneBy(['title' => 'Cats & Dogs']);

        static::assertNotNull($bookFromRepo);

        $date = new \DateTime();

        static::assertSame(
            $date->format('Y-m-d H:i'),
            $book->getTags()->get(0)->getCreated()->format('Y-m-d H:i')
        );

        static::assertSame(
            $date->format('Y-m-d H:i'),
            $book->getTags()->get(1)->getCreated()->format('Y-m-d H:i')
        );

        static::assertSame(
            $date->format('Y-m-d H:i'),
            $book->getTags()->get(0)->getUpdated()->format('Y-m-d H:i')
        );

        static::assertSame(
            $date->format('Y-m-d H:i'),
            $book->getTags()->get(1)->getUpdated()->format('Y-m-d H:i')
        );
    }
}
