<?php

namespace Gedmo\Timestampable;

use Doctrine\Common\EventManager;
use Timestampable\Fixture\Document\Book;
use Timestampable\Fixture\Document\Tag;
use Tool\BaseTestCaseMongoODM;

/**
 * These are tests for Timestampable behavior ODM implementation
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TimestampableEmbeddedDocumentTest extends BaseTestCaseMongoODM
{
    const BOOK = 'Timestampable\Fixture\Document\Book';

    protected function setUp()
    {
        parent::setUp();
        $evm = new EventManager();
        $evm->addEventSubscriber(new TimestampableListener());

        $this->getMockDocumentManager($evm);
    }

    /**
     * Test that no php notice is triggered while processing timestampable properties of embedded document
     */
    public function testPersistOnlyEmbeddedDocument()
    {
        $tag = new Tag();
        $tag->setName('cats');

        $this->dm->persist($tag);
        $this->dm->flush();
        $this->dm->clear();
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

        $bookFromRepo = $repo->findOneByTitle('Cats & Dogs');

        $this->assertNotNull($bookFromRepo);

        $date = new \DateTime();

        $this->assertEquals(
            $date->format('Y-m-d H:i'),
            $book->getTags()->get(0)->getCreated()->format('Y-m-d H:i')
        );

        $this->assertEquals(
            $date->format('Y-m-d H:i'),
            $book->getTags()->get(1)->getCreated()->format('Y-m-d H:i')
        );

        $this->assertEquals(
            $date->format('Y-m-d H:i'),
            $book->getTags()->get(0)->getUpdated()->format('Y-m-d H:i')
        );

        $this->assertEquals(
            $date->format('Y-m-d H:i'),
            $book->getTags()->get(1)->getUpdated()->format('Y-m-d H:i')
        );
    }
}
