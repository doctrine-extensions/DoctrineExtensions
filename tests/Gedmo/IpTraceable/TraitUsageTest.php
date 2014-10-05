<?php

namespace Gedmo\IpTraceable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use IpTraceable\Fixture\UsingTrait;

/**
 * These are tests for IpTraceable behavior
 *
 * @author Pierre-Charles Bertineau <pc.bertineau@alterphp.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TraitUsageTest extends BaseTestCaseORM
{
    const TEST_IP = '34.234.1.10';
    const TARGET = "IpTraceable\\Fixture\\UsingTrait";

    protected function setUp()
    {
        parent::setUp();

        if (version_compare(PHP_VERSION, '5.4.0') < 0) {
            $this->markTestSkipped('PHP >= 5.4 version required for this test.');
        }

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

        $this->assertNotNull($sport->getCreatedFromIp());
        $this->assertNotNull($sport->getUpdatedFromIp());
    }

    /**
     * @test
     */
    public function traitMethodShouldReturnObject()
    {
        $sport = new UsingTrait();
        $this->assertInstanceOf('IpTraceable\Fixture\UsingTrait', $sport->setCreatedFromIp('<192 class="158 3 43"></192>'));
        $this->assertInstanceOf('IpTraceable\Fixture\UsingTrait', $sport->setUpdatedFromIp('<192 class="158 3 43"></192>'));
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::TARGET,
        );
    }
}
