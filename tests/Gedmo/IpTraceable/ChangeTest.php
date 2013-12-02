<?php

namespace Gedmo\IpTraceable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Doctrine\Common\Util\Debug,
    IpTraceable\Fixture\TitledArticle,
    Gedmo\Mapping\Event\Adapter\ORM as BaseAdapterORM,
    Doctrine\Common\EventArgs;

/**
 * These are tests for IpTraceable behavior
 * 
 * @author Pierre-Charles Bertineau <pc.bertineau@alterphp.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class ChangeTest extends BaseTestCaseORM
{
    const TEST_IP = '34.234.1.10';
    const FIXTURE = "IpTraceable\\Fixture\\TitledArticle";

    protected $listener;

    protected function setUp()
    {
        parent::setUp();

        $this->listener = new IpTraceableListener;
        $this->listener->setIpValue(self::TEST_IP);

        $evm = new EventManager;
        $evm->addEventSubscriber($this->listener);

        $this->getMockSqliteEntityManager($evm);
    }

    public function testChange()
    {
        $test = new TitledArticle();
        $test->setTitle('Test');
        $test->setText('Test');

        $this->em->persist($test);
        $this->em->flush();
        $this->em->clear();

        $test = $this->em->getRepository(self::FIXTURE)->findOneByTitle('Test');
        $test->setTitle('New Title');
        $this->em->persist($test);
        $this->em->flush();
        $this->em->clear();
        //Changed
        $this->assertEquals(self::TEST_IP, $test->getChtitle());

        $this->listener->setIpValue('127.0.0.1');

        $test = $this->em->getRepository(self::FIXTURE)->findOneByTitle('New Title');
        $test->setText('New Text');
        $this->em->persist($test);
        $this->em->flush();
        $this->em->clear();
        //Not Changed
        $this->assertEquals(self::TEST_IP, $test->getChtitle());
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::FIXTURE,
        );
    }
}
