<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Tree;

use Doctrine\Common\EventManager;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Tests\Tree\Fixture\Transport\Bus;
use Gedmo\Tests\Tree\Fixture\Transport\Car;
use Gedmo\Tests\Tree\Fixture\Transport\Engine;
use Gedmo\Tests\Tree\Fixture\Transport\Vehicle;
use Gedmo\Tree\TreeListener;

/**
 * These are tests for Tree behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class MultiInheritanceWithSingleTableTest extends BaseTestCaseORM
{
    public const CAR = Car::class;
    public const BUS = Bus::class;
    public const VEHICLE = Vehicle::class;
    public const ENGINE = Engine::class;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new TreeListener());

        $this->getDefaultMockSqliteEntityManager($evm);
    }

    public function testConsistence(): void
    {
        $this->populate();
        $this->em->clear();

        $carRepo = $this->em->getRepository(self::CAR);
        $audi = $carRepo->findOneBy(['title' => 'Audi-80']);
        static::assertSame(2, $carRepo->childCount($audi));
        static::assertSame(1, $audi->getLeft());
        static::assertSame(6, $audi->getRight());

        $children = $carRepo->children($audi);
        static::assertCount(2, $children);

        $path = $carRepo->getPath($children[0]);
        static::assertCount(2, $path);

        $carRepo->moveDown($children[0]);
        static::assertSame(4, $children[0]->getLeft());
        static::assertSame(5, $children[0]->getRight());

        static::assertTrue($carRepo->verify());
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

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::VEHICLE,
            self::CAR,
            self::ENGINE,
            self::BUS,
        ];
    }

    private function populate(): void
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

        $vb8 = new Engine();
        $vb8->setType('VB8');
        $vb8->setValves(8);
        $this->em->persist($vb8);

        $jet = new Engine();
        $jet->setType('Jet');
        $jet->setValves(16);
        $this->em->persist($jet);

        // cars

        $audi80 = new Car();
        $audi80->setEngine($v6);
        $audi80->setTitle('Audi-80');
        $this->em->persist($audi80);

        $audi80s = new Car();
        $audi80s->setParent($audi80);
        $audi80s->setTitle('Audi-80s');
        $audi80s->setEngine($v8);
        $this->em->persist($audi80s);

        $icarus = new Bus();
        $icarus->setEngine($vb8);
        $icarus->setTitle('Icarus');
        $this->em->persist($icarus);

        $audiJet = new Car();
        $audiJet->setParent($audi80);
        $audiJet->setTitle('Audi-jet');
        $audiJet->setEngine($jet);
        $this->em->persist($audiJet);

        $this->em->flush();
        $this->em->flush();
    }
}
