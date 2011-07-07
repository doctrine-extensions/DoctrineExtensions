<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Sluggable\Fixture\Inheritance\Car;
use Sluggable\Fixture\Inheritance\Vehicle;

/**
 * These are tests for Sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Sluggable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class InheritanceTest extends BaseTestCaseORM
{
    const VEHICLE = 'Sluggable\\Fixture\\Inheritance\\Vehicle';
    const CAR = 'Sluggable\\Fixture\\Inheritance\\Car';

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

        $audi3 = new Vehicle;
        $audi3->setTitle('Audi');

        $this->em->persist($audi3);
        $this->em->flush();
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::VEHICLE,
            self::CAR
        );
    }
}
