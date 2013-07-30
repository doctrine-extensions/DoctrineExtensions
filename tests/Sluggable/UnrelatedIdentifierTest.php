<?php

namespace Sluggable;

use Doctrine\Common\EventManager;
use TestTool\ObjectManagerTestCase;
use Fixture\Sluggable\Identifier;
use Gedmo\Sluggable\SluggableListener;

class UnrelatedIdentifierTest extends ObjectManagerTestCase
{
    const TARGET = 'Fixture\Sluggable\Identifier';

    private $em;

    protected function setUp()
    {
        $evm = new EventManager;
        $evm->addEventSubscriber(new SluggableListener);

        $this->em = $this->createEntityManager($evm);
        $this->createSchema($this->em, array(
            self::TARGET,
        ));
    }

    protected function tearDown()
    {
        $this->releaseEntityManager($this->em);
    }

    /**
     * @test
     */
    function shouldBePossibleToSlugIdentifiers()
    {
        $sport = new Identifier;
        $sport->setTitle('Sport');
        $this->em->persist($sport);
        $this->em->flush();

        $this->assertSame('sport', $sport->getId());
    }

    /**
     * @test
     */
    function shouldPersistMultipleNonConflictingIdentifierSlugs()
    {
        $sport = new Identifier;
        $sport->setTitle('Sport');
        $this->em->persist($sport);

        $sport2 = new Identifier;
        $sport2->setTitle('Sport');
        $this->em->persist($sport2);
        $this->em->flush();

        $this->assertSame('sport', $sport->getId());
        $this->assertSame('sport_1', $sport2->getId());
    }
}
