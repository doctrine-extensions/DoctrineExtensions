<?php

namespace Sluggable;

use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManager;
use Fixture\Sluggable\Inheritance\Car;
use Fixture\Sluggable\Inheritance\Vehicle;
use Gedmo\Sluggable\SluggableListener;
use TestTool\ObjectManagerTestCase;

/**
 * These are tests for Sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class InheritanceTest extends ObjectManagerTestCase
{
    const VEHICLE = 'Fixture\Sluggable\Inheritance\Vehicle';
    const CAR = 'Fixture\Sluggable\Inheritance\Car';

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

        $audi3 = new Vehicle();
        $audi3->setTitle('Audi');

        $this->em->persist($audi3);
        $this->em->flush();
    }
}
