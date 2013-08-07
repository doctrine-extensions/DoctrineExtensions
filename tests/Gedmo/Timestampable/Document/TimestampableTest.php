<?php

namespace Gedmo\Timestampable\Document;

use Gedmo\TestTool\ObjectManagerTestCase;
use Doctrine\Common\EventManager;
use Gedmo\Timestampable\TimestampableListener;
use Gedmo\Fixture\Timestampable\Document\Article;
use Gedmo\Fixture\Timestampable\Document\Type;

class TimestampableTest extends ObjectManagerTestCase
{
    const ARTICLE = 'Gedmo\Fixture\Timestampable\Document\Article';
    const TYPE = 'Gedmo\Fixture\Timestampable\Document\Type';

    private $dm;

    protected function setUp()
    {
        $evm = new EventManager;
        $evm->addEventSubscriber(new TimestampableListener);
        $this->dm = $this->createDocumentManager($evm);
    }

    protected function tearDown()
    {
        $this->releaseDocumentManager($this->dm);
    }

    /**
     * @test
     */
    function shouldHandleBaseTimestampableFunctionality()
    {
        $stamps = new Article;
        $stamps->setTitle('Stamps');

        $this->dm->persist($stamps);
        $this->dm->flush();

        $this->assertNotNull($dateCreated = $stamps->getCreated());
        $this->assertNotNull($dateUpdated = $stamps->getUpdated());
        $this->assertNull($stamps->getPublished());

        $published = new Type;
        $published->setIdentifier('published');
        $published->setTitle('Published');

        $stamps->setType($published);
        $this->dm->persist($stamps);
        $this->dm->persist($published);
        $this->dm->flush();

        $this->assertNotNull($datePublished = $stamps->getPublished());
        $this->assertSame($dateCreated, $stamps->getCreated());
        $this->assertNotSame($dateUpdated, $stamps->getUpdated());

        $published->setIdentifier('changed');
        $published->setTitle('changed');
        $this->dm->persist($published);
        $this->dm->flush();

        $this->assertSame($datePublished, $stamps->getPublished());
    }

    /**
     * @test
     */
    function shouldHandleManuallySetDates()
    {
        $sport = new Article;
        $sport->setTitle('sport forced');
        $sport->setCreated($dateCreated = strtotime('2000-01-01 12:00:00'));
        $sport->setUpdated($dateUpdated = new \DateTime('2000-01-01 12:00:00'));

        $this->dm->persist($sport);
        $this->dm->flush();

        $this->assertSame($dateCreated, intval($sport->getCreated()));
        $this->assertSame('2000-01-01 12:00:00', $sport->getUpdated()->format('Y-m-d H:i:s'));
        $this->assertSame($dateUpdated, $sport->getUpdated());

        $published = new Type;
        $published->setIdentifier('published');
        $published->setTitle('Published');

        $sport->setType($published);
        $sport->setPublished($datePublished = new \DateTime('2000-01-01 12:00:00'));
        $this->dm->persist($sport);
        $this->dm->persist($published);
        $this->dm->flush();

        $this->assertSame('2000-01-01 12:00:00', $sport->getPublished()->format('Y-m-d H:i:s'));
        $this->assertSame($datePublished, $sport->getPublished());
    }

    /**
     * @test
     */
    function shouldHandleOnChangeWithBooleanValue()
    {
        $sport = new Article;
        $sport->setTitle('Sport');
        $this->dm->persist($sport);
        $this->dm->flush();

        $this->assertNull($sport->getReady());

        $sport->setIsReady(true);
        $this->dm->persist($sport);
        $this->dm->flush();

        $this->assertNotNull($sport->getReady());
    }
}
