<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Sluggable\Fixture\Issue104\Car;

/**
 * These are tests for Sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Sluggable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Issue104Test extends BaseTestCaseORM
{
    const CAR = 'Sluggable\\Fixture\\Issue104\\Car';

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $evm->addEventSubscriber(new SluggableListener);

        $this->getMockSqliteEntityManager($evm);
    }

    public function testSlugGeneration()
    {
        $audi = new Car;
        $audi->setDescription('audi car');
        $audi->setTitle('Audi');

        $this->em->persist($audi);

        $audi2 = new Car;
        $audi2->setDescription('audi2 car');
        $audi2->setTitle('Audi');

        $this->em->persist($audi2);
        $this->em->flush();
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::CAR
        );
    }
}
