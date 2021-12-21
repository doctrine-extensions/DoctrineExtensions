<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Blameable;

use Doctrine\Common\EventManager;
use Gedmo\Blameable\BlameableListener;
use Gedmo\Tests\Blameable\Fixture\Entity\UsingTrait;
use Gedmo\Tests\Tool\BaseTestCaseORM;

/**
 * These are tests for Blameable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
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

        $this->getDefaultMockSqliteEntityManager($evm);
    }

    public function testShouldTimestampUsingTrait(): void
    {
        $sport = new UsingTrait();
        $sport->setTitle('Sport');

        $this->em->persist($sport);
        $this->em->flush();

        static::assertNotNull($sport->getCreatedBy());
        static::assertNotNull($sport->getUpdatedBy());
    }

    public function testTraitMethodthShouldReturnObject(): void
    {
        $sport = new UsingTrait();
        static::assertInstanceOf(self::TARGET, $sport->setCreatedBy('myuser'));
        static::assertInstanceOf(self::TARGET, $sport->setUpdatedBy('myuser'));
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::TARGET,
        ];
    }
}
