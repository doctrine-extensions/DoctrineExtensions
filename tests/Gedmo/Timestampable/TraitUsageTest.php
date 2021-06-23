<?php

namespace Gedmo\Timestampable;

use Doctrine\Common\EventManager;
use Timestampable\Fixture\UsingTrait;
use Tool\BaseTestCaseORM;

/**
 * These are tests for Timestampable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TraitUsageTest extends BaseTestCaseORM
{
    const TARGET = 'Timestampable\\Fixture\\UsingTrait';

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new TimestampableListener());

        $this->getMockSqliteEntityManager($evm);
    }

    /**
     * @test
     */
    public function shouldTimestampUsingTrait()
    {
        $sport = new UsingTrait();
        $sport->setTitle('Sport');

        $this->em->persist($sport);
        $this->em->flush();

        $this->assertNotNull($sport->getCreatedAt());
        $this->assertNotNull($sport->getUpdatedAt());
    }

    /**
     * @test
     */
    public function traitMethodthShouldReturnObject()
    {
        $sport = new UsingTrait();
        $this->assertInstanceOf('Timestampable\Fixture\UsingTrait', $sport->setCreatedAt(new \DateTime()));
        $this->assertInstanceOf('Timestampable\Fixture\UsingTrait', $sport->setUpdatedAt(new \DateTime()));
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::TARGET,
        ];
    }
}
