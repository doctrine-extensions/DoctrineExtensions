<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Sluggable\Fixture\Inheritance2\SportCar;
use Sluggable\Fixture\Inheritance2\Car;
use Sluggable\Fixture\Inheritance2\Vehicle;

/**
 * Tests for Sluggable behavior
 *
 */
class Inheritance2Test extends BaseTestCaseORM
{
    const VEHICLE = 'Sluggable\\Fixture\\Inheritance2\\Vehicle';
    const CAR = 'Sluggable\\Fixture\\Inheritance2\\Car';
    const SPORTCAR = 'Sluggable\\Fixture\\Inheritance2\\SportCar';

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());

        $this->getMockSqliteEntityManager($evm);
    }

    public function testSlugGeneration()
    {
        $audi = new Car();
        $audi->setDescription('audi car');
        $audi->setTitle('Audi');

        $this->em->persist($audi);

        $audi2 = new Car();
        $audi2->setDescription('audi2 car');
        $audi2->setTitle('Audi');

        $this->em->persist($audi2);

        $audi3 = new SportCar();
        $audi3->setDescription('audi3 car');
        $audi3->setTitle('Audi');

        $this->em->persist($audi3);
        $this->em->flush();
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::VEHICLE,
            self::CAR,
            self::SPORTCAR,
        );
    }
}
