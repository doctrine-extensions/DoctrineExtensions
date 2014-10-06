<?php

namespace Gedmo\IpTraceable;

use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManager;
use Gedmo\Fixture\IpTraceable\TitledArticle;
use Gedmo\TestTool\ObjectManagerTestCase;

/**
 * These are tests for IpTraceable behavior
 *
 * @author Pierre-Charles Bertineau <pc.bertineau@alterphp.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class ChangeTest extends ObjectManagerTestCase
{
    const TEST_IP = '34.234.1.10';
    const FIXTURE = 'Gedmo\Fixture\IpTraceable\TitledArticle';

    /**
     * @var IpTraceableListener
     */
    private $listener;
    /**
     * @var EntityManager
     */
    private $em;

    protected function setUp()
    {
        $evm = new EventManager();
        $this->listener = new IpTraceableListener();
        $this->listener->setIpValue(self::TEST_IP);
        $evm->addEventSubscriber($this->listener);

        $this->em = $this->createEntityManager($evm);
        $this->createSchema($this->em, array(
            self::FIXTURE,
        ));
    }

    protected function tearDown()
    {
        $this->releaseEntityManager($this->em);
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
}
