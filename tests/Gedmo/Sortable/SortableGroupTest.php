<?php

namespace Gedmo\Tests\Sortable;

use Doctrine\Common\EventManager;
use Gedmo\Sortable\SortableListener;
use Gedmo\Tests\Sortable\Fixture\Category;
use Gedmo\Tests\Sortable\Fixture\Item;
use Gedmo\Tests\Sortable\Fixture\Transport\Bus;
use Gedmo\Tests\Sortable\Fixture\Transport\Car;
use Gedmo\Tests\Sortable\Fixture\Transport\Engine;
use Gedmo\Tests\Sortable\Fixture\Transport\Reservation;
use Gedmo\Tests\Tool\BaseTestCaseORM;

/**
 * These are tests for sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SortableGroupTest extends BaseTestCaseORM
{
    public const CAR = "Gedmo\Tests\Sortable\Fixture\Transport\Car";
    public const BUS = "Gedmo\Tests\Sortable\Fixture\Transport\Bus";
    public const VEHICLE = "Gedmo\Tests\Sortable\Fixture\Transport\Vehicle";
    public const ENGINE = "Gedmo\Tests\Sortable\Fixture\Transport\Engine";
    public const RESERVATION = "Gedmo\Tests\Sortable\Fixture\Transport\Reservation";
    public const ITEM = "Gedmo\Tests\Sortable\Fixture\Item";
    public const CATEGORY = "Gedmo\Tests\Sortable\Fixture\Category";

    public const SEATS = 3;

    public const TRAVEL_DATE_FORMAT = 'Y-m-d H:i';
    public const TODAY = '2013-10-24 12:50';
    public const TOMORROW = '2013-10-25 12:50';

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new SortableListener());

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

        $audi80 = $carRepo->findOneBy(['title' => 'Audi-80']);
        static::assertEquals(0, $audi80->getSortByEngine());

        $audi80s = $carRepo->findOneBy(['title' => 'Audi-80s']);
        static::assertEquals(1, $audi80s->getSortByEngine());

        $icarus = $this->em->getRepository(self::BUS)->findOneBy(['title' => 'Icarus']);
        static::assertEquals(2, $icarus->getSortByEngine());

        $this->em->remove($audi80);
        $this->em->flush();

        $audi80s = $carRepo->findOneBy(['title' => 'Audi-80s']);
        static::assertEquals(0, $audi80s->getSortByEngine());

        $icarus = $this->em->getRepository(self::BUS)->findOneBy(['title' => 'Icarus']);
        static::assertEquals(1, $icarus->getSortByEngine());
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
        $audi80 = $carRepo->findOneBy(['title' => 'Audi-80']);
        static::assertEquals(0, $audi80->getSortByEngine());

        //position 1
        $audi80s = $carRepo->findOneBy(['title' => 'Audi-80s']);
        static::assertEquals(1, $audi80s->getSortByEngine());

        //position 2
        $icarus = $this->em->getRepository(self::BUS)->findOneBy(['title' => 'Icarus']);
        static::assertEquals(2, $icarus->getSortByEngine());

        // theres only 1 v6 so this should be position:0
        $audiJet = $carRepo->findOneBy(['title' => 'Audi-jet']);
        static::assertEquals(0, $audiJet->getSortByEngine());

        // change engines
        $v6engine = $this->em->getRepository(self::ENGINE)->findOneBy(['type' => 'V6']);

        $audi80s->setEngine($v6engine);

        $this->em->flush();

        // v6
        static::assertEquals(0, $audiJet->getSortByEngine());
        static::assertEquals(1, $audi80s->getSortByEngine());

        // v8
        static::assertEquals(0, $audi80->getSortByEngine());
        static::assertEquals(1, $icarus->getSortByEngine());
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

        for ($i = 0; $i < self::SEATS; ++$i) {
            $reservation = $repo->findOneBy(['name' => 'Bratislava Today '.$i]);
            static::assertNotNull($reservation);
            static::assertEquals($i, $reservation->getSeat());

            $reservation = $repo->findOneBy(['name' => 'Bratislava Tomorrow '.$i]);
            static::assertNotNull($reservation);
            static::assertEquals($i, $reservation->getSeat());

            $reservation = $repo->findOneBy(['name' => 'Prague Today '.$i]);
            static::assertNotNull($reservation);
            static::assertEquals($i, $reservation->getSeat());
        }

        // Change date of the travel
        $reservation = $repo->findOneBy(['name' => 'Bratislava Today 1']);
        $reservation->setTravelDate($tomorrow);
        $this->em->persist($reservation);
        $this->em->flush();

        // Scan all bus lines
        // Bratislava Today should have 2 seats
        $bratislavaToday = $repo->findBy([
            'destination' => 'Bratislava',
            'travelDate' => $today,
        ], ['seat' => 'asc']);
        static::assertCount(self::SEATS - 1, $bratislavaToday);
        // Test seat numbers
        // Should be [ 0, 1 ]
        $seats = array_map(function ($r) { return $r->getSeat(); }, $bratislavaToday);
        static::assertEquals(range(0, self::SEATS - 2), $seats, 'Should be seats [ 0, 1 ] to Bratislava Today');

        // Bratislava Tomorrow should have 4 seats
        $bratislavaTomorrow = $repo->findBy([
            'destination' => 'Bratislava',
            'travelDate' => $tomorrow,
        ], ['seat' => 'asc']);
        static::assertCount(self::SEATS + 1, $bratislavaTomorrow);
        // Test seat numbers
        // Should be [ 0, 1, 2, 3 ]
        $seats = array_map(function ($r) { return $r->getSeat(); }, $bratislavaTomorrow);
        static::assertEquals(range(0, self::SEATS), $seats, 'Should be seats [ 0, 1, 2, 3 ] to Bratislava Tomorrow');

        // Prague Today should have 3 seats
        $pragueToday = $repo->findBy([
            'destination' => 'Prague',
            'travelDate' => $today,
        ], ['seat' => 'asc']);
        static::assertCount(self::SEATS, $pragueToday);
        // Test seat numbers
        $seats = array_map(function ($r) { return $r->getSeat(); }, $pragueToday);
        static::assertEquals(range(0, self::SEATS - 1), $seats, 'Should be seats [ 0, 1, 2 ] to Prague Today');
    }

    /**
     * @test
     * @group failing
     */
    public function shouldBeAbleToChangeGroupAndPosition()
    {
        $this->populate();

        $this->startQueryLog();
        $repo = $this->em->getRepository(self::ITEM);
        $repoCategory = $this->em->getRepository(self::CATEGORY);

        $vehicle = $repoCategory->findOneBy(['name' => 'Vehicle']);

        $vehicles = $repo->findBy(['category' => $vehicle], ['position' => 'asc']);
        $position = 1;
        foreach ($vehicles as $item) {
            static::assertEquals($position, $item->getPosition());
            ++$position;
        }
        static::assertEquals(31, $position);

        $accessory = $repoCategory->findOneBy(['name' => 'Accessory']);

        $accessories = $repo->findBy(['category' => $accessory], ['position' => 'asc']);
        $position = 1;
        foreach ($accessories as $item) {
            static::assertEquals($position, $item->getPosition());
            ++$position;
        }
        static::assertEquals(31, $position);

        $item = $repo->findOneBy(['category' => $accessory, 'position' => 7]);
        $item->setCategory($vehicle);
        $item->setPosition(4);
        $this->em->persist($item);
        $this->em->flush();
        $this->stopQueryLog(false, true);

        unset($vehicles, $accessories);

        $vehicles = $repo->findBy(['category' => $vehicle], ['position' => 'asc']);
        $position = 1;
        foreach ($vehicles as $item) {
            static::assertEquals($position, $item->getPosition());
            ++$position;
        }
        static::assertEquals(32, $position);

        $accessory = $repoCategory->findOneBy(['name' => 'Accessory']);

        $accessories = $repo->findBy(['category' => $accessory], ['position' => 'asc']);
        $position = 1;
        foreach ($accessories as $item) {
            static::assertEquals($position, $item->getPosition());
            ++$position;
        }
        static::assertEquals(30, $position);
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::VEHICLE,
            self::CAR,
            self::ENGINE,
            self::BUS,
            self::RESERVATION,
            self::ITEM,
            self::CATEGORY,
        ];
    }

    private function populate()
    {
        // engines
        $v8 = new Engine();
        $v8->setType('V8');
        $v8->setValves(8);
        $this->em->persist($v8);

        $v6 = new Engine();
        $v6->setType('V6');
        $v6->setValves(8);
        $this->em->persist($v6);
        $this->em->flush();

        // cars

        $audi80 = new Car();
        $audi80->setEngine($v8);
        $audi80->setTitle('Audi-80');
        $this->em->persist($audi80);

        $audi80s = new Car();
        $audi80s->setParent($audi80);
        $audi80s->setTitle('Audi-80s');
        $audi80s->setEngine($v8);
        $this->em->persist($audi80s);

        $icarus = new Bus();
        $icarus->setEngine($v8);
        $icarus->setTitle('Icarus');
        $this->em->persist($icarus);

        $audiJet = new Car();
        $audiJet->setParent($audi80);
        $audiJet->setTitle('Audi-jet');
        $audiJet->setEngine($v6);
        $this->em->persist($audiJet);

        $today = \DateTime::createFromFormat(self::TRAVEL_DATE_FORMAT, self::TODAY);
        $tomorrow = \DateTime::createFromFormat(self::TRAVEL_DATE_FORMAT, self::TOMORROW);

        for ($i = 0; $i < self::SEATS; ++$i) {
            $reservationBratislava = new Reservation();
            $reservationBratislava->setBus($icarus);
            $reservationBratislava->setDestination('Bratislava');
            $reservationBratislava->setTravelDate($today);
            $reservationBratislava->setName('Bratislava Today '.$i);
            $this->em->persist($reservationBratislava);

            $reservationBratislava = new Reservation();
            $reservationBratislava->setBus($icarus);
            $reservationBratislava->setDestination('Bratislava');
            $reservationBratislava->setTravelDate($tomorrow);
            $reservationBratislava->setName('Bratislava Tomorrow '.$i);
            $this->em->persist($reservationBratislava);

            $reservationPrague = new Reservation();
            $reservationPrague->setBus($icarus);
            $reservationPrague->setDestination('Prague');
            $reservationPrague->setTravelDate($today);
            $reservationPrague->setName('Prague Today '.$i);
            $this->em->persist($reservationPrague);
        }

        $categoryVehicle = new Category();
        $categoryVehicle->setName('Vehicle');
        $this->em->persist($categoryVehicle);

        $categoryAccessory = new Category();
        $categoryAccessory->setName('Accessory');
        $this->em->persist($categoryAccessory);

        for ($i = 1; $i <= 60; ++$i) {
            $item = new Item();
            $item->setName('Item '.$i);
            if ($i <= 30) {
                $item->setCategory($categoryVehicle);
                $item->setPosition($i);
            } else {
                $item->setCategory($categoryAccessory);
                $item->setPosition($i - 30);
            }
            $this->em->persist($item);
        }

        $this->em->flush();
    }
}
