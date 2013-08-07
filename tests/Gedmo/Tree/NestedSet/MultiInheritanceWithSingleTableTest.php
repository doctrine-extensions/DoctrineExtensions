<?php

namespace Gedmo\Tree\NestedSet;

use Doctrine\Common\EventManager;
use Gedmo\TestTool\ObjectManagerTestCase;
use Gedmo\Fixture\Tree\NestedSet\Transport\Car;
use Gedmo\Fixture\Tree\NestedSet\Transport\Bus;
use Gedmo\Fixture\Tree\NestedSet\Transport\Vehicle;
use Gedmo\Fixture\Tree\NestedSet\Transport\Engine;
use Gedmo\Tree\TreeListener;

class MultiInheritanceWithSingleTableTest extends ObjectManagerTestCase
{
    const CAR = "Gedmo\Fixture\Tree\NestedSet\Transport\Car";
    const BUS = "Gedmo\Fixture\Tree\NestedSet\Transport\Bus";
    const VEHICLE = "Gedmo\Fixture\Tree\NestedSet\Transport\Vehicle";
    const ENGINE = "Gedmo\Fixture\Tree\NestedSet\Transport\Engine";

    private $em;

    protected function setUp()
    {
        $evm = new EventManager;
        $evm->addEventSubscriber(new TreeListener);

        $this->em = $this->createEntityManager($evm);
        $this->createSchema($this->em, array(
            self::VEHICLE,
            self::CAR,
            self::ENGINE,
            self::BUS
        ));
    }

    protected function tearDown()
    {
        $this->releaseEntityManager($this->em);
    }

    public function testConsistence()
    {
        $this->populate();
        $carRepo = $this->em->getRepository(self::CAR);
        $audi = $carRepo->findOneByTitle('Audi-80');
        $this->assertEquals(2, $carRepo->childCount($audi));
        $this->assertEquals(1, $audi->getLeft());
        $this->assertEquals(6, $audi->getRight());

        $children = $carRepo->children($audi);
        $this->assertCount(2, $children);

        $path = $carRepo->getPath($children[0]);
        $this->assertCount(2, $path);

        $carRepo->moveDown($children[0]);
        $this->assertEquals(4, $children[0]->getLeft());
        $this->assertEquals(5, $children[0]->getRight());

        $this->assertTrue($carRepo->verify());
    }

    /*public function testHeavyLoad()
    {
        $carRepo = $this->em->getRepository(self::CAR);
        $parent = null;
        $num = 100;
        for($i = 0; $i < 100; $i++) {
            $engine = new Engine;
            $engine->setType('e'.$i);
            $engine->setValves(8);
            $this->em->persist($engine);

            $car = new Car;
            $car->setParent($parent);
            $car->setTitle('car'.$i);
            $car->setEngine($engine);
            $this->em->persist($car);
            // siblings
            $rnd = rand(0, 3);
            for ($j = 0; $j < $rnd; $j++) {
                $siblingEngine = new Engine;
                $siblingEngine->setType('e'.$i.$j);
                $siblingEngine->setValves(8);
                $this->em->persist($siblingEngine);

                $siblingCar = new Car;
                $siblingCar->setTitle('car'.$i.$j);
                $siblingCar->setEngine($siblingEngine);
                $siblingCar->setParent($car);
                $this->em->persist($siblingCar);
            }
            $num += $rnd;
            $parent = $car;
        }
        $this->em->flush();
        $this->assertTrue($carRepo->verify());
        var_dump('processed: '.$num);
    }*/

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

        $vb8 = new Engine;
        $vb8->setType('VB8');
        $vb8->setValves(8);
        $this->em->persist($vb8);

        $jet = new Engine;
        $jet->setType('Jet');
        $jet->setValves(16);
        $this->em->persist($jet);

        // cars

        $audi80 = new Car;
        $audi80->setEngine($v6);
        $audi80->setTitle('Audi-80');
        $this->em->persist($audi80);

        $audi80s = new Car;
        $audi80s->setParent($audi80);
        $audi80s->setTitle('Audi-80s');
        $audi80s->setEngine($v8);
        $this->em->persist($audi80s);

        $icarus = new Bus;
        $icarus->setEngine($vb8);
        $icarus->setTitle('Icarus');
        $this->em->persist($icarus);

        $audiJet = new Car;
        $audiJet->setParent($audi80);
        $audiJet->setTitle('Audi-jet');
        $audiJet->setEngine($jet);
        $this->em->persist($audiJet);

        $this->em->flush();
        $this->em->flush();
    }
}
