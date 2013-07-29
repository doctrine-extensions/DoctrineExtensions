<?php

namespace Sluggable;

use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManager;
use Fixture\Sluggable\MappedSuperclass\Car;
use Gedmo\Sluggable\SluggableListener;
use TestTool\ObjectManagerTestCase;

/**
 * These are tests for sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class MappedSuperclassTest extends ObjectManagerTestCase
{
    const CAR = 'Fixture\Sluggable\MappedSuperclass\Car';

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
     * If the MappedSuperclass doesn't have an identifier, SluggableListener generates a notice
     * Undefined offset: 0 in Doctrine/ORM/Mapping/ClassMetadataInfo.php:986
     * @test
     */
    public function shouldntGenerateNotice()
    {
        $audi = new Car();
        $audi->setDescription('audi car');
        $audi->setTitle('Audi');

        $this->em->persist($audi);
        $this->em->flush();
    }
}
