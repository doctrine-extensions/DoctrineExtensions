<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManager;
use Gedmo\Fixture\Sluggable\Identifier;
use Gedmo\TestTool\ObjectManagerTestCase;

class UnrelatedIdentifierTest extends ObjectManagerTestCase
{
    const TARGET = 'Gedmo\Fixture\Sluggable\Identifier';

    /**
     * @var EntityManager
     */
    private $em;

    protected function setUp()
    {
        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());

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
    public function shouldBePossibleToSlugIdentifiers()
    {
        $sport = new Identifier();
        $sport->setTitle('Sport');
        $this->em->persist($sport);
        $this->em->flush();

        $this->assertSame('sport', $sport->getId());
    }

    /**
     * @test
     */
    public function shouldPersistMultipleNonConflictingIdentifierSlugs()
    {
        $sport = new Identifier();
        $sport->setTitle('Sport');
        $this->em->persist($sport);

        $sport2 = new Identifier();
        $sport2->setTitle('Sport');
        $this->em->persist($sport2);
        $this->em->flush();

        $this->assertSame('sport', $sport->getId());
        $this->assertSame('sport_1', $sport2->getId());
    }
}
