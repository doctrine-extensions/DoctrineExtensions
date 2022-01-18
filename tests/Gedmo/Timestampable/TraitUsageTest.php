<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Timestampable;

use Doctrine\Common\EventManager;
use Gedmo\Tests\Timestampable\Fixture\UsingTrait;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Timestampable\TimestampableListener;

/**
 * These are tests for Timestampable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class TraitUsageTest extends BaseTestCaseORM
{
    public const TARGET = UsingTrait::class;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new TimestampableListener());

        $this->getDefaultMockSqliteEntityManager($evm);
    }

    public function testShouldTimestampUsingTrait(): void
    {
        $sport = new UsingTrait();
        $sport->setTitle('Sport');

        $this->em->persist($sport);
        $this->em->flush();

        static::assertNotNull($sport->getCreatedAt());
        static::assertNotNull($sport->getUpdatedAt());
    }

    public function testTraitMethodthShouldReturnObject(): void
    {
        $sport = new UsingTrait();
        static::assertInstanceOf(UsingTrait::class, $sport->setCreatedAt(new \DateTime()));
        static::assertInstanceOf(UsingTrait::class, $sport->setUpdatedAt(new \DateTime()));
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::TARGET,
        ];
    }
}
