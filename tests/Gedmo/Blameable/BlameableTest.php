<?php

namespace Gedmo\Blameable;

use Doctrine\Common\EventManager;
use Gedmo\TestTool\ObjectManagerTestCase;
use Gedmo\Fixture\Blameable\Article;
use Gedmo\Fixture\Blameable\Comment;
use Gedmo\Fixture\Blameable\Type;
use Gedmo\Blameable\BlameableListener;

class BlameableTest extends ObjectManagerTestCase
{
    const ARTICLE = "Gedmo\Fixture\Blameable\Article";
    const COMMENT = "Gedmo\Fixture\Blameable\Comment";
    const TYPE = "Gedmo\Fixture\Blameable\Type";

    private $em;

    protected function setUp()
    {
        $listener = new BlameableListener;
        $listener->setUserValue('testuser');

        $evm = new EventManager;
        $evm->addEventSubscriber($listener);

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
    function shouldHandleGeneralBlameableFunctionality()
    {
        $sport = new Article;
        $sport->setTitle('Sport');
        $sport->setBody('Body');

        $sportComment = new Comment();
        $sportComment->setMessage('hello');
        $sportComment->setArticle($sport);
        $sportComment->setStatus(0);

        $this->em->persist($sport);
        $this->em->persist($sportComment);
        $this->em->flush();

        $this->assertSame('testuser', $sport->getCreatedBy());
        $this->assertSame('testuser', $sport->getChangedBy());
        $this->assertSame('testuser', $sport->getUpdatedBy());
        $this->assertNull($sport->getPublishedBy());

        $this->assertSame('testuser', $sportComment->getModifiedBy());
        $this->assertNull($sportComment->getClosedBy());

        $sportComment->setStatus(1);
        $published = new Type;
        $published->setTitle('Published');

        $sport->setTitle('Updated');
        $sport->setType($published);

        $this->em->persist($sport);
        $this->em->persist($published);
        $this->em->persist($sportComment);
        $this->em->flush();

        $this->assertSame('testuser', $sportComment->getClosedBy());
        $this->assertSame('testuser', $sport->getPublishedBy());
    }

    /**
     * @test
     */
    function shouldHandleManualValues()
    {
        $sport = new Article;
        $sport->setTitle('sport forced');
        $sport->setBody('body');
        $sport->setCreatedBy('myuser');
        $sport->setUpdatedBy('myuser');
        $sport->setChangedBy('myuser');

        $this->em->persist($sport);
        $this->em->flush();

        $this->assertSame('myuser', $sport->getCreatedBy());
        $this->assertSame('myuser', $sport->getUpdatedBy());
        $this->assertSame('myuser', $sport->getChangedBy());

        $published = new Type;
        $published->setTitle('Published');

        $sport->setType($published);
        $sport->setPublishedBy('myuser');
        $this->em->persist($sport);
        $this->em->persist($published);
        $this->em->flush();

        $this->assertSame('myuser', $sport->getPublishedBy());
    }
}
