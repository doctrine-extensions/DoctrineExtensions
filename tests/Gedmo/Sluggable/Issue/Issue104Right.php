<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Sluggable\Fixture\Issue104\Icarus;

/**
 * These are tests for Sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Sluggable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Issue104RightTest extends BaseTestCaseORM
{
    const ICARUS = 'Sluggable\\Fixture\\Issue104\\Icarus';

    protected function setUp()
    {
        parent::setUp();
    }

    /**
     * @test
     */
    public function shouldMapMappedSuperclassPrivateInheritedProperty()
    {
        $evm = new EventManager;
        $evm->addEventSubscriber(new SluggableListener);
        $this->getMockSqliteEntityManager($evm);

        $audi = new Icarus;
        $audi->setDescription('audi car');
        $audi->setTitle('Audi');

        $this->em->persist($audi);
        $this->em->flush();

        $this->assertEquals('audi', $audi->getSlug());
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::ICARUS
        );
    }
}
