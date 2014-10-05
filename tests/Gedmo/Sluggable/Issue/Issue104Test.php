<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Sluggable\Fixture\Issue104\Car;

/**
 * These are tests for Sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Issue104Test extends BaseTestCaseORM
{
    const CAR = 'Sluggable\\Fixture\\Issue104\\Car';

    protected function setUp()
    {
        parent::setUp();
    }

    /**
     * @test
     * @expectedException Gedmo\Exception\InvalidMappingException
     */
    public function shouldThrowAnExceptionWhenMappedSuperclassProtectedProperty()
    {
        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());
        $this->getMockSqliteEntityManager($evm);

        $audi = new Car();
        $audi->setDescription('audi car');
        $audi->setTitle('Audi');

        $this->em->persist($audi);
        $this->em->flush();
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::CAR,
        );
    }
}
