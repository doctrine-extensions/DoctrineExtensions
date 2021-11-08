<?php

namespace Gedmo\Tests\Timestampable;

use Doctrine\Common\EventManager;
use Gedmo\Tests\Timestampable\Fixture\UsingTrait;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Timestampable\TimestampableListener;

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
    public const TARGET = 'Gedmo\\Tests\\Timestampable\\Fixture\\UsingTrait';

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

        static::assertNotNull($sport->getCreatedAt());
        static::assertNotNull($sport->getUpdatedAt());
    }

    /**
     * @test
     */
    public function traitMethodthShouldReturnObject()
    {
        $sport = new UsingTrait();
        static::assertInstanceOf('Gedmo\Tests\Timestampable\Fixture\UsingTrait', $sport->setCreatedAt(new \DateTime()));
        static::assertInstanceOf('Gedmo\Tests\Timestampable\Fixture\UsingTrait', $sport->setUpdatedAt(new \DateTime()));
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::TARGET,
        ];
    }
}
