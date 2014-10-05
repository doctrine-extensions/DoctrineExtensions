<?php

namespace Gedmo\Timestampable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Timestampable\Fixture\WithoutInterface;

/**
 * These are tests for Timestampable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class NoInterfaceTest extends BaseTestCaseORM
{
    const FIXTURE = "Timestampable\\Fixture\\WithoutInterface";

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new TimestampableListener());

        $this->getMockSqliteEntityManager($evm);
    }

    public function testTimestampableNoInterface()
    {
        $test = new WithoutInterface();
        $test->setTitle('Test');

        $date = new \DateTime('now');
        $this->em->persist($test);
        $this->em->flush();
        $this->em->clear();

        $test = $this->em->getRepository(self::FIXTURE)->findOneByTitle('Test');
        $this->assertEquals(
            $date->format('Y-m-d 00:00:00'),
            $test->getCreated()->format('Y-m-d H:i:s')
        );
        $this->assertEquals(
            $date->format('Y-m-d H:i'),
            $test->getUpdated()->format('Y-m-d H:i')
        );
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::FIXTURE,
        );
    }
}
