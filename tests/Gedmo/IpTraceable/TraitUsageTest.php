<?php

namespace Gedmo\Tests\IpTraceable;

use Doctrine\Common\EventManager;
use Gedmo\IpTraceable\IpTraceableListener;
use Gedmo\Tests\IpTraceable\Fixture\UsingTrait;
use Gedmo\Tests\Tool\BaseTestCaseORM;

/**
 * These are tests for IpTraceable behavior
 *
 * @author Pierre-Charles Bertineau <pc.bertineau@alterphp.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TraitUsageTest extends BaseTestCaseORM
{
    public const TEST_IP = '34.234.1.10';
    public const TARGET = 'Gedmo\\Tests\\IpTraceable\\Fixture\\UsingTrait';

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $ipTraceableListener = new IpTraceableListener();
        $ipTraceableListener->setIpValue(self::TEST_IP);
        $evm->addEventSubscriber($ipTraceableListener);

        $this->getMockSqliteEntityManager($evm);
    }

    /**
     * @test
     */
    public function shouldIpTraceUsingTrait()
    {
        $sport = new UsingTrait();
        $sport->setTitle('Sport');

        $this->em->persist($sport);
        $this->em->flush();

        static::assertNotNull($sport->getCreatedFromIp());
        static::assertNotNull($sport->getUpdatedFromIp());
    }

    /**
     * @test
     */
    public function traitMethodShouldReturnObject()
    {
        $sport = new UsingTrait();
        static::assertInstanceOf('Gedmo\Tests\IpTraceable\Fixture\UsingTrait', $sport->setCreatedFromIp('<192 class="158 3 43"></192>'));
        static::assertInstanceOf('Gedmo\Tests\IpTraceable\Fixture\UsingTrait', $sport->setUpdatedFromIp('<192 class="158 3 43"></192>'));
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::TARGET,
        ];
    }
}
