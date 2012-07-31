<?php

namespace Gedmo\Timestampable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Doctrine\Common\Util\Debug,
    Timestampable\Fixture\TitledArticle;

/**
 * These are tests for Timestampable behavior
 *
 * @author Ivan Borzenkov <ivan.borzenkov@gmail.com>
 * @package Gedmo.Timestampable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class ChangeTest extends BaseTestCaseORM
{
    const FIXTURE = "Timestampable\\Fixture\\TitledArticle";

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $evm->addEventSubscriber(new TimestampableListener);

        $this->getMockSqliteEntityManager($evm);
    }

    public function testChange()
    {
        $test = new TitledArticle();
        $test->setTitle('Test');
        $test->setText('Test');

        $date = new \DateTime('now');
        $this->em->persist($test);
        $this->em->flush();
        $this->em->clear();

        $test = $this->em->getRepository(self::FIXTURE)->findOneByTitle('Test');
        $test->setTitle('New Title');
        $this->em->persist($test);
        $this->em->flush();
        $this->em->clear();
        //Changed
        $this->assertEquals(
            $date->format('Y-m-d H:i:s'),
            $test->getChtitle()->format('Y-m-d H:i:s')
        );
        sleep(1);
        $test = $this->em->getRepository(self::FIXTURE)->findOneByTitle('New Title');
        $test->setText('New Text');
        $this->em->persist($test);
        $this->em->flush();
        $this->em->clear();
        //Not Changed
        $this->assertEquals(
            $date->format('Y-m-d H:i:s'),
            $test->getChtitle()->format('Y-m-d H:i:s')
        );
        $date = new \DateTime('now');
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::FIXTURE,
        );
    }
}
