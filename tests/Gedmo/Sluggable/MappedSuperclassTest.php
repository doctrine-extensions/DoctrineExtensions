<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Sluggable\Fixture\MappedSuperclass\Car;

/**
 * These are tests for sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class MappedSuperclassTest extends BaseTestCaseORM
{
    const CAR = 'Sluggable\\Fixture\\MappedSuperclass\\Car';
    
    protected function setUp()
    {
        parent::setUp();
    }

    /**
     * If the MappedSuperclass doesn't have an identifier, SluggableListener generates a notice
     * Undefined offset: 0 in Doctrine/ORM/Mapping/ClassMetadataInfo.php:986
     * @test
     */
    public function shouldntGenerateNotice()
    {
        $evm = new EventManager;
        $evm->addEventSubscriber(new SluggableListener);
        $this->getMockSqliteEntityManager($evm);

        $audi = new Car;
        $audi->setDescription('audi car');
        $audi->setTitle('Audi');

        $this->em->persist($audi);
        $this->em->flush();
        
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::CAR
        );
    }
}
