<?php

namespace Gedmo\Timestampable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Timestampable\Fixture\UsingTrait;

/**
 * These are tests for Timestampable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TraitUsageTest extends BaseTestCaseORM
{
    const TARGET = "Timestampable\\Fixture\\UsingTrait";

    protected function setUp()
    {
        parent::setUp();

        if (version_compare(PHP_VERSION, '5.4.0') < 0) {
            $this->markTestSkipped('PHP >= 5.4 version required for this test.');
        }

        $evm = new EventManager;
        $evm->addEventSubscriber(new TimestampableListener);

        $this->getMockSqliteEntityManager($evm);
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
        $this->assertInstanceOf('Timestampable\Fixture\UsingTrait', $sport->setCreatedAt(new \DateTime()));
        $this->assertInstanceOf('Timestampable\Fixture\UsingTrait', $sport->setUpdatedAt(new \DateTime()));
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::TARGET
        );
    }
}
