<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\IpTraceable;

use Doctrine\Common\EventManager;
use Gedmo\IpTraceable\IpTraceableListener;
use Gedmo\Tests\IpTraceable\Fixture\UsingTrait;
use Gedmo\Tests\Tool\BaseTestCaseORM;

/**
 * These are tests for IpTraceable behavior
 *
 * @author Pierre-Charles Bertineau <pc.bertineau@alterphp.com>
 */
final class TraitUsageTest extends BaseTestCaseORM
{
    public const TEST_IP = '34.234.1.10';
    public const TARGET = UsingTrait::class;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $ipTraceableListener = new IpTraceableListener();
        $ipTraceableListener->setIpValue(self::TEST_IP);
        $evm->addEventSubscriber($ipTraceableListener);

        $this->getDefaultMockSqliteEntityManager($evm);
    }

    public function testShouldIpTraceUsingTrait(): void
    {
        $sport = new UsingTrait();
        $sport->setTitle('Sport');

        $this->em->persist($sport);
        $this->em->flush();

        static::assertNotNull($sport->getCreatedFromIp());
        static::assertNotNull($sport->getUpdatedFromIp());
    }

    public function testTraitMethodShouldReturnObject(): void
    {
        $sport = new UsingTrait();
        static::assertInstanceOf(UsingTrait::class, $sport->setCreatedFromIp('<192 class="158 3 43"></192>'));
        static::assertInstanceOf(UsingTrait::class, $sport->setUpdatedFromIp('<192 class="158 3 43"></192>'));
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::TARGET,
        ];
    }
}
