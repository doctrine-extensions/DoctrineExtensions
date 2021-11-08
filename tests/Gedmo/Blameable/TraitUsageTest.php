<?php

namespace Gedmo\Tests\Blameable;

use Doctrine\Common\EventManager;
use Gedmo\Blameable\BlameableListener;
use Gedmo\Tests\Blameable\Fixture\Entity\UsingTrait;
use Gedmo\Tests\Tool\BaseTestCaseORM;

/**
 * These are tests for Blameable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class TraitUsageTest extends BaseTestCaseORM
{
    public const TARGET = UsingTrait::class;

    protected function setUp(): void
    {
        parent::setUp();

        $listener = new BlameableListener();
        $listener->setUserValue('testuser');
        $evm = new EventManager();
        $evm->addEventSubscriber($listener);

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

        static::assertNotNull($sport->getCreatedBy());
        static::assertNotNull($sport->getUpdatedBy());
    }

    /**
     * @test
     */
    public function traitMethodthShouldReturnObject()
    {
        $sport = new UsingTrait();
        static::assertInstanceOf(self::TARGET, $sport->setCreatedBy('myuser'));
        static::assertInstanceOf(self::TARGET, $sport->setUpdatedBy('myuser'));
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::TARGET,
        ];
    }
}
