<?php

namespace Gedmo\Tree;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Tree\Fixture\Transport\Car;
use Tree\Fixture\Transport\Bus;
use Tree\Fixture\Transport\Vehicle;
use Tree\Fixture\Transport\Engine;

/**
 * These are tests for Tree behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class MultiInheritanceWithSingleTableTest extends BaseTestCaseORM
{
    const CAR = "Tree\Fixture\Transport\Car";
    const BUS = "Tree\Fixture\Transport\Bus";
    const VEHICLE = "Tree\Fixture\Transport\Vehicle";
    const ENGINE = "Tree\Fixture\Transport\Engine";

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new TreeListener());

        $this->getMockSqliteEntityManager($evm);
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

        $carRepo->moveDown($children[1]);
        $this->assertEquals(4, $children[1]->getLeft());
        $this->assertEquals(5, $children[1]->getRight());

        $this->assertTrue($carRepo->verify());
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::VEHICLE,
            self::CAR,
            self::ENGINE,
            self::BUS,
        );
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
