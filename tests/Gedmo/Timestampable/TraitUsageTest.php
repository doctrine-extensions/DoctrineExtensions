<?php

namespace Gedmo\Timestampable;

use Doctrine\Common\EventManager;
use Gedmo\TestTool\ObjectManagerTestCase;
use Gedmo\Fixture\Timestampable\UsingTrait;
use Gedmo\Timestampable\TimestampableListener;

class TraitUsageTest extends ObjectManagerTestCase
{
    const TARGET = "Gedmo\Fixture\Timestampable\UsingTrait";

    private $em;

    protected function setUp()
    {
        if (version_compare(PHP_VERSION, '5.4.0') < 0) {
            $this->markTestSkipped('PHP >= 5.4 version required for this test.');
        }
        $evm = new EventManager;
        $evm->addEventSubscriber(new TimestampableListener);

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
    function shouldTimestampUsingTrait()
    {
        $sport = new UsingTrait;
        $sport->setTitle('Sport');

        $this->em->persist($sport);
        $this->em->flush();

        $this->assertNotNull($sport->getCreatedAt());
        $this->assertNotNull($sport->getUpdatedAt());
    }

    /**
     * @test
     */
    function traitMethodthShouldReturnObject()
    {
        $sport = new UsingTrait;
        $this->assertInstanceOf(self::TARGET, $sport->setCreatedAt(new \DateTime()));
        $this->assertInstanceOf(self::TARGET, $sport->setUpdatedAt(new \DateTime()));
    }
}
