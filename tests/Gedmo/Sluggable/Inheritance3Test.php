<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Sluggable\Fixture\Inheritance3\Car;
use Sluggable\Fixture\Inheritance3\Vehicle;
use Sluggable\Fixture\Inheritance3\VehicleWithSlug;
use Sluggable\Fixture\Inheritance3\Bike;

/**
 * These are tests for Sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Inheritance3Test extends BaseTestCaseORM
{
    const VEHICLE = 'Sluggable\\Fixture\\Inheritance3\\Vehicle';
    const VEHICLE_WITH_SLUG = 'Sluggable\\Fixture\\Inheritance3\\VehicleWithSlug';
    const CAR = 'Sluggable\\Fixture\\Inheritance3\\Car';
    const BIKE = 'Sluggable\\Fixture\\Inheritance3\\Bike';

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
        $this->em->flush();
        
        $bike = new Bike;
        $bike->setTitle('Audi');

        $this->em->persist($bike);
        $this->em->flush();

    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::VEHICLE,
            self::VEHICLE_WITH_SLUG,
            self::CAR,
            self::BIKE,
        );
    }
}
