<?php

namespace Sluggable\Issue;

use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManager;
use Fixture\Sluggable\Issue104\Car;
use Gedmo\Sluggable\SluggableListener;
use TestTool\ObjectManagerTestCase;

/**
 * These are tests for Sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Issue104Test extends ObjectManagerTestCase
{
    const CAR = 'Fixture\Sluggable\Issue104\Car';

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
            self::CAR,
        ));
    }

    protected function tearDown()
    {
        $this->releaseEntityManager($this->em);
    }

    /**
     * @test
     * @expectedException \Gedmo\Exception\InvalidMappingException
     */
    public function shouldThrowAnExceptionWhenMappedSuperclassProtectedProperty()
    {
        $audi = new Car();
        $audi->setDescription('audi car');
        $audi->setTitle('Audi');

        $this->em->persist($audi);
        $this->em->flush();
    }
}
