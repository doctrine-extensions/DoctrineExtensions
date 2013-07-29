<?php

namespace Sluggable;

use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManager;
use Fixture\Sluggable\Inheritance2\SportCar;
use Fixture\Sluggable\Inheritance2\Car;
use Gedmo\Sluggable\SluggableListener;
use TestTool\ObjectManagerTestCase;

/**
 * Tests for Sluggable behavior
 *
 */
class Inheritance2Test extends ObjectManagerTestCase
{
    const VEHICLE = 'Fixture\Sluggable\Inheritance2\Vehicle';
    const CAR = 'use Fixture\Sluggable\Inheritance2\Car';
    const SPORTCAR = 'Fixture\Sluggable\Inheritance2\SportCar';

    /**
     * @var EntityManager
     */
    private $em;

    protected function setUp()
    {
        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());

        $this->em = $this->createEntityManager($evm);
        $this->createSchema($this->em, array(
            self::VEHICLE,
            self::CAR,
            self::SPORTCAR,
        ));
    }

    protected function tearDown()
    {
        $this->releaseEntityManager($this->em);
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
}
