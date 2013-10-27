<?php

namespace Gedmo\Sortable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Sortable\Fixture\Transport\Car;
use Sortable\Fixture\Transport\Bus;
use Sortable\Fixture\Transport\Vehicle;
use Sortable\Fixture\Transport\Engine;
use Sortable\Fixture\Transport\Reservation;

/**
 * These are tests for sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SortableGroupTest extends BaseTestCaseORM
{
    const CAR = "Sortable\Fixture\Transport\Car";
    const BUS = "Sortable\Fixture\Transport\Bus";
    const VEHICLE = "Sortable\Fixture\Transport\Vehicle";
    const ENGINE = "Sortable\Fixture\Transport\Engine";
    const RESERVATION = "Sortable\Fixture\Transport\Reservation";

    const SEATS = 3;

    const TRAVEL_DATE_FORMAT = 'Y-m-d H:i';
    const TODAY = '2013-10-24 12:50';
    const TOMORROW = '2013-10-25 12:50';

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $evm->addEventSubscriber(new SortableListener);

        $this->getMockSqliteEntityManager($evm);
        /*$this->getMockCustomEntityManager(array(
            'driver' => 'pdo_mysql',
            'dbname' => 'test',
            'host' => '127.0.0.1',
            'user' => 'root',
            'password' => 'nimda'
        ), $evm);*/
    }

    /**
     * @test
     */
    public function shouldBeAbleToRemove()
    {
        $this->populate();
        $carRepo = $this->em->getRepository(self::CAR);

        $audi80 = $carRepo->findOneByTitle('Audi-80');
        $this->assertEquals(0, $audi80->getSortByEngine());

        $audi80s = $carRepo->findOneByTitle('Audi-80s');
        $this->assertEquals(1, $audi80s->getSortByEngine());

        $icarus = $this->em->getRepository(self::BUS)->findOneByTitle('Icarus');
        $this->assertEquals(2, $icarus->getSortByEngine());

        $this->em->remove($audi80);
        $this->em->flush();

        $audi80s = $carRepo->findOneByTitle('Audi-80s');
        $this->assertEquals(0, $audi80s->getSortByEngine());

        $icarus = $this->em->getRepository(self::BUS)->findOneByTitle('Icarus');
        $this->assertEquals(1, $icarus->getSortByEngine());
    }

    /**
     * @test
     * fix issue #502
     */
    public function shouldBeAbleToChangeGroup()
    {
        $this->populate();
        $carRepo = $this->em->getRepository(self::CAR);

        // position 0
        $audi80 = $carRepo->findOneByTitle('Audi-80');
        $this->assertEquals(0, $audi80->getSortByEngine());

        //position 1
        $audi80s = $carRepo->findOneByTitle('Audi-80s');
        $this->assertEquals(1, $audi80s->getSortByEngine());

        //position 2
        $icarus = $this->em->getRepository(self::BUS)->findOneByTitle('Icarus');
        $this->assertEquals(2, $icarus->getSortByEngine());

        // theres only 1 v6 so this should be position:0
        $audiJet = $carRepo->findOneByTitle('Audi-jet');
        $this->assertEquals(0, $audiJet->getSortByEngine());

        // change engines
        $v6engine = $this->em->getRepository(self::ENGINE)->findOneByType('V6');

        $audi80s->setEngine($v6engine);

        $this->em->flush();

        // v6
        $this->assertEquals(0, $audiJet->getSortByEngine());
        $this->assertEquals(1, $audi80s->getSortByEngine());

        // v8
        $this->assertEquals(0, $audi80->getSortByEngine());
        $this->assertEquals(1, $icarus->getSortByEngine());
    }

    /**
     * @test
     * issue #873
     */
    public function shouldBeAbleToChangeGroupWhenMultiGroups()
    {
        $this->populate();

        $repo = $this->em->getRepository(self::RESERVATION);
        $today = \DateTime::createFromFormat(self::TRAVEL_DATE_FORMAT, self::TODAY);
        $tomorrow = \DateTime::createFromFormat(self::TRAVEL_DATE_FORMAT, self::TOMORROW);

        for ($i = 0; $i < self::SEATS; $i++) {
            $reservation = $repo->findOneByName('Bratislava Today ' . $i);
            $this->assertNotNull($reservation);
            $this->assertEquals($i, $reservation->getSeat());

            $reservation = $repo->findOneByName('Bratislava Tomorrow ' . $i);
            $this->assertNotNull($reservation);
            $this->assertEquals($i, $reservation->getSeat());

            $reservation = $repo->findOneByName('Prague Today ' . $i);
            $this->assertNotNull($reservation);
            $this->assertEquals($i, $reservation->getSeat());
        }

        // Change date of the travel
        $reservation = $repo->findOneByName('Bratislava Today 1');
        $reservation->setTravelDate($tomorrow);
        $this->em->persist($reservation);
        $this->em->flush();

        // Scan all bus lines
        // Bratislava Today should have 2 seats
        $bratislavaToday = $repo->findBy(array(
            'destination' => 'Bratislava',
            'travelDate' => $today
        ), array( 'seat' => 'asc' ));
        $this->assertCount(self::SEATS - 1, $bratislavaToday);
        // Test seat numbers
        // Should be [ 0, 1 ]
        $seats = array_map(function($r) { return $r->getSeat(); }, $bratislavaToday);
        $this->assertEquals(range(0, self::SEATS - 2), $seats, 'Should be seats [ 0, 1 ] to Bratislava Today');

        // Bratislava Tomorrow should have 4 seats
        $bratislavaTomorrow = $repo->findBy(array(
            'destination' => 'Bratislava',
            'travelDate' => $tomorrow
        ), array( 'seat' => 'asc' ));
        $this->assertCount(self::SEATS + 1, $bratislavaTomorrow);
        // Test seat numbers
        // Should be [ 0, 1, 2, 3 ]
        $seats = array_map(function($r) { return $r->getSeat(); }, $bratislavaTomorrow);
        $this->assertEquals(range(0, self::SEATS), $seats, 'Should be seats [ 0, 1, 2, 3 ] to Bratislava Tomorrow');

        // Prague Today should have 3 seats
        $pragueToday = $repo->findBy(array(
            'destination' => 'Prague',
            'travelDate' => $today
        ), array( 'seat' => 'asc' ));
        $this->assertCount(self::SEATS, $pragueToday);
        // Test seat numbers
        $seats = array_map(function($r) { return $r->getSeat(); }, $pragueToday);
        $this->assertEquals(range(0, self::SEATS - 1), $seats, 'Should be seats [ 0, 1, 2 ] to Prague Today');
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::VEHICLE,
            self::CAR,
            self::ENGINE,
            self::BUS,
            self::RESERVATION
        );
    }

    private function populate()
    {
        // engines
        $v8 = new Engine;
        $v8->setType('V8');
        $v8->setValves(8);
        $this->em->persist($v8);

        $v6 = new Engine;
        $v6->setType('V6');
        $v6->setValves(8);
        $this->em->persist($v6);
        $this->em->flush();

        // cars

        $audi80 = new Car;
        $audi80->setEngine($v8);
        $audi80->setTitle('Audi-80');
        $this->em->persist($audi80);

        $audi80s = new Car;
        $audi80s->setParent($audi80);
        $audi80s->setTitle('Audi-80s');
        $audi80s->setEngine($v8);
        $this->em->persist($audi80s);

        $icarus = new Bus;
        $icarus->setEngine($v8);
        $icarus->setTitle('Icarus');
        $this->em->persist($icarus);

        $audiJet = new Car;
        $audiJet->setParent($audi80);
        $audiJet->setTitle('Audi-jet');
        $audiJet->setEngine($v6);
        $this->em->persist($audiJet);

        $today = \DateTime::createFromFormat(self::TRAVEL_DATE_FORMAT, self::TODAY);
        $tomorrow = \DateTime::createFromFormat(self::TRAVEL_DATE_FORMAT, self::TOMORROW);

        for ($i = 0; $i < self::SEATS; $i++) {
            $reservationBratislava = new Reservation;
            $reservationBratislava->setBus($icarus);
            $reservationBratislava->setDestination('Bratislava');
            $reservationBratislava->setTravelDate($today);
            $reservationBratislava->setName('Bratislava Today ' . $i);
            $this->em->persist($reservationBratislava);

            $reservationBratislava = new Reservation;
            $reservationBratislava->setBus($icarus);
            $reservationBratislava->setDestination('Bratislava');
            $reservationBratislava->setTravelDate($tomorrow);
            $reservationBratislava->setName('Bratislava Tomorrow ' . $i);
            $this->em->persist($reservationBratislava);

            $reservationPrague = new Reservation;
            $reservationPrague->setBus($icarus);
            $reservationPrague->setDestination('Prague');
            $reservationPrague->setTravelDate($today);
            $reservationPrague->setName('Prague Today ' . $i);
            $this->em->persist($reservationPrague);
        }

        $this->em->flush();
    }
}
