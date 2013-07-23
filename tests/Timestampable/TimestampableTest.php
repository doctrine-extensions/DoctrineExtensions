<?php

namespace Timestampable;

use Doctrine\Common\EventManager;
use TestTool\ObjectManagerTestCase;
use Fixture\Timestampable\Article;
use Fixture\Timestampable\Comment;
use Fixture\Timestampable\Type;
use Gedmo\Timestampable\TimestampableListener;

class TimestampableTest extends ObjectManagerTestCase
{
    const ARTICLE = "Fixture\Timestampable\Article";
    const COMMENT = "Fixture\Timestampable\Comment";
    const TYPE = "Fixture\Timestampable\Type";

    private $em;

    protected function setUp()
    {
        $evm = new EventManager;
        $evm->addEventSubscriber(new TimestampableListener);

        $this->em = $this->createEntityManager($evm);
        $this->createSchema($this->em, array(
            self::ARTICLE,
            self::COMMENT,
            self::TYPE
        ));
    }

    protected function tearDown()
    {
        $this->releaseEntityManager($this->em);
    }

    /**
     * @test
     */
    function shouldPlaceAndUpdateTimestamps()
    {
        $sport = new Article;
        $sport->setTitle('Sport');
        $sport->setBody('Sport article body.');

        $sportComment = new Comment;
        $sportComment->setMessage('hello');
        $sportComment->setArticle($sport);
        $sportComment->setStatus(0);

        $this->em->persist($sport);
        $this->em->persist($sportComment);
        $this->em->flush();

        $this->assertNotNull($sport->getCreated());
        $this->assertNotNull($sport->getUpdated());
        $this->assertNull($sport->getContentChanged());
        $this->assertNull($sport->getPublished());

        $this->assertNotNull($sportComment->getModified());
        $this->assertNull($sportComment->getClosed());

        $sportComment->setStatus(1);
        $published = new Type();
        $published->setTitle('Published');

        $sport->setType($published);
        $this->em->persist($sport);
        $this->em->persist($published);
        $this->em->persist($sportComment);
        $this->em->flush();

        $this->assertNotNull($sportComment->getClosed());
        $this->assertNotNull($sport->getPublished());

        $updatedDateBefore = $sport->getUpdated();
        $createdDateBefore = $sport->getCreated();
        $publishedDateBefore = $sport->getPublished();

        $sport->setTitle('Updated');
        $this->em->persist($sport);
        $this->em->persist($published); // try to mock persistence
        $this->em->persist($sportComment); // try to mock persistence
        $this->em->flush();

        $this->assertNotSame($updatedDateBefore, $sport->getUpdated());
        $this->assertSame($createdDateBefore, $sport->getCreated());
        $this->assertSame($publishedDateBefore, $sport->getPublished());
        $this->assertNotNull($sport->getContentChanged());

        $updatedDateBefore = $sport->getUpdated();
        $createdDateBefore = $sport->getCreated();
        $publishedDateBefore = $sport->getPublished();
        $contentChangedDateBefore = $sport->getContentChanged();

        $sport->setBody('Body updated');
        $this->em->persist($sport);
        $this->em->persist($published); // try to mock persistence
        $this->em->persist($sportComment); // try to mock persistence
        $this->em->flush();

        $this->assertNotSame($updatedDateBefore, $sport->getUpdated());
        $this->assertSame($createdDateBefore, $sport->getCreated());
        $this->assertSame($publishedDateBefore, $sport->getPublished());
        $this->assertNotSame($contentChangedDateBefore, $sport->getContentChanged());
    }

    /**
     * @test
     */
    function shouldBeAbleToSetDatesManually()
    {
        $sport = new Article;
        $sport->setTitle('sport forced');
        $sport->setBody('Sport article body.');

        $sport->setCreated(new \DateTime('2000-01-01'));
        $sport->setUpdated(new \DateTime('2000-01-01 12:00:00'));
        $sport->setContentChanged(new \DateTime('2000-01-01 12:00:00'));

        $this->em->persist($sport);
        $this->em->flush();

        $this->assertSame('2000-01-01', $sport->getCreated()->format('Y-m-d'));
        $this->assertSame('2000-01-01 12:00:00', $sport->getUpdated()->format('Y-m-d H:i:s'));
        $this->assertSame('2000-01-01 12:00:00', $sport->getContentChanged()->format('Y-m-d H:i:s'));

        $published = new Type;
        $published->setTitle('Published');

        $sport->setType($published);
        $sport->setPublished(new \DateTime('2000-01-01 12:00:00'));
        $this->em->persist($sport);
        $this->em->persist($published);
        $this->em->flush();

        $this->assertEquals('2000-01-01 12:00:00', $sport->getPublished()->format('Y-m-d H:i:s'));
    }
}
