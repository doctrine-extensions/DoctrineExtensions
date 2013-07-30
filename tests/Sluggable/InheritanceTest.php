<?php

namespace Sluggable;

use Doctrine\Common\EventManager;
use TestTool\ObjectManagerTestCase;
use Fixture\Sluggable\Inheritance\SportCar;
use Fixture\Sluggable\Inheritance\Car;
use Fixture\Sluggable\Inheritance\Vehicle;
use Gedmo\Sluggable\SluggableListener;

class InheritanceTest extends ObjectManagerTestCase
{
    const VEHICLE = 'Fixture\Sluggable\Inheritance\Vehicle';
    const CAR = 'Fixture\Sluggable\Inheritance\Car';
    const SPORTCAR = 'Fixture\Sluggable\Inheritance\SportCar';

    private $em;

    protected function setUp()
    {
        $evm = new EventManager;
        $evm->addEventSubscriber(new SluggableListener);

        $this->em = $this->createEntityManager($evm);
        $this->createSchema($this->em, array(
            self::VEHICLE,
            self::CAR,
            self::SPORTCAR
        ));
    }

    protected function tearDown()
    {
        $this->releaseEntityManager($this->em);
    }

    /**
     * @test
     */
    function shouldSupportInheritance()
    {
        $audi80 = new Car;
        $audi80->setType('Audi');
        $audi80->setTitle('Audi 80');

        $this->em->persist($audi80);

        $bmw530 = new Car;
        $bmw530->setType('BMW');
        $bmw530->setTitle('BMW 530');

        $this->em->persist($bmw530);

        $bmwX5 = new SportCar;
        $bmwX5->setType('BMW');
        $bmwX5->setTitle('BMW X5');

        $this->em->persist($bmwX5);
        $this->em->flush();

        $this->assertSame('audi', $audi80->getTypeSlug());
        $this->assertSame('audi-80', $audi80->getSlug());
        $this->assertSame('bmw', $bmw530->getTypeSlug());
        $this->assertSame('bmw-530', $bmw530->getSlug());
        $this->assertSame('bmw-1', $bmwX5->getTypeSlug());
        $this->assertSame('bmw-x5', $bmwX5->getSlug());
    }
}
