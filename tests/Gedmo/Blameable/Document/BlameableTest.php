<?php

namespace Gedmo\Blameable\Document;

use Gedmo\TestTool\ObjectManagerTestCase;
use Doctrine\Common\EventManager;
use Gedmo\Fixture\Blameable\Document\Article;
use Gedmo\Fixture\Blameable\Document\Type;
use Gedmo\Fixture\Blameable\Document\User;
use Gedmo\Blameable\BlameableListener;

class BlameableTest extends ObjectManagerTestCase
{
    const TEST_USERNAME = 'testuser';

    const TYPE = 'Gedmo\Fixture\Blameable\Document\Type';
    const USER = 'Gedmo\Fixture\Blameable\Document\User';
    const ARTICLE = 'Gedmo\Fixture\Blameable\Document\Article';

    private $dm, $user;

    protected function setUp()
    {
        $this->user = new User;
        $this->user->setUsername(self::TEST_USERNAME);

        $listener = new BlameableListener;
        $listener->setUserValue($this->user);

        $evm = new EventManager();
        $evm->addEventSubscriber($listener);

        $this->dm = $this->createDocumentManager($evm);
        $this->dm->persist($this->user);
        $this->dm->flush();
    }

    protected function tearDown()
    {
        $this->releaseDocumentManager($this->dm);
    }

    /**
     * @test
     */
    function shouldHandleBlameableDocument()
    {
        $sport = new Article;
        $sport->setTitle('Sport');

        $published = new Type;
        $published->setIdentifier('published');
        $published->setTitle('Published');

        $sport->setType($published);
        $this->dm->persist($sport);
        $this->dm->persist($published);
        $this->dm->flush();

        $this->assertSame(self::TEST_USERNAME, $sport->getUpdatedBy());
        $this->assertSame(self::TEST_USERNAME, $sport->getPublishedBy());
        $this->assertSame(self::TEST_USERNAME, $sport->getCreatedBy()->getUsername());
        $this->assertSame($this->user, $sport->getCreatedBy());
    }

    /**
     * @test
     */
    public function shouldHandleManuallySetValues()
    {
        $sport = new Article;
        $sport->setTitle('sport forced');
        $sport->setUpdatedBy('cust');

        $published = new Type;
        $published->setIdentifier('published');
        $published->setTitle('Published');

        $sport->setType($published);
        $sport->setPublishedBy('cust');
        $this->dm->persist($sport);
        $this->dm->persist($published);
        $this->dm->flush();

        $this->assertSame($this->user, $sport->getCreatedBy());
        $this->assertSame('cust', $sport->getUpdatedBy());
        $this->assertSame('cust', $sport->getPublishedBy());
    }
}
