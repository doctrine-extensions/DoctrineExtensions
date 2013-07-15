<?php

namespace Translatable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Fixture\Translatable\Transport\Vehicle;
use Fixture\Translatable\Transport\VehicleTranslation;
use Fixture\Translatable\Transport\Car;
use Fixture\Translatable\Transport\Engine;
use Fixture\Translatable\Transport\Motorcycle;
use Gedmo\Translatable\TranslatableListener;

class InheritanceTest extends BaseTestCaseORM
{
    private $translatable;

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $evm->addEventSubscriber($this->translatable = new TranslatableListener);
        $this->getMockSqliteEntityManager($evm);
    }

    /**
     * @test
     */
    function shouldHandleInheritance()
    {
        $this->fixtureDataCreate();
        $translations = $this->em->getRepository('Fixture\Translatable\Transport\VehicleTranslation')->findAll();

        $this->assertCount(5, $translations, "There should be 5 translations");

        $this->translatable->setTranslatableLocale('de');
        $this->em->clear();

        $hayabusa = $this->em->getRepository('Fixture\Translatable\Transport\Vehicle')->findOneBySpeed(290);
        $this->assertSame('Hayabusa de', $hayabusa->getTitle(), 'should be translated in de');

        $audi80 = $this->em->getRepository('Fixture\Translatable\Transport\Vehicle')->findOneBySpeed(200);
        $this->assertSame(null, $audi80->getTitle(), 'should be not translated as NULL, because there is no fallback locale set');
    }

    private function fixtureDataCreate()
    {
        // engines
        $v8 = new Engine;
        $v8->setType('V8');
        $v8->setValves(8);
        $this->em->persist($v8);

        $v5 = new Engine;
        $v5->setType('V5');
        $v5->setValves(5);
        $this->em->persist($v5);

        $v8s = new Engine;
        $v8s->setType('V8s');
        $v8s->setValves(8);
        $this->em->persist($v8s);

        $jet = new Engine;
        $jet->setType('Jet');
        $jet->setValves(16);
        $this->em->persist($jet);

        // cars

        $audi80 = new Car;
        $audi80->setEngine($v8);
        $audi80->setTitle('Audi-80');
        $audi80->setSpeed(200);
        $audi80->setDoors(4);
        $this->em->persist($audi80);

        $audi80s = new Car;
        $audi80s->setTitle('Audi-80s');
        $audi80s->setEngine($v8s);
        $audi80s->setSpeed(240);
        $audi80s->setDoors(4);
        $this->em->persist($audi80s);

        $hayabusa = new Motorcycle;
        $hayabusa->setEngine($v5);
        $hayabusa->setTitle('Hayabusa');
        $hayabusa->setSpeed(290);
        $this->em->persist($hayabusa);

        $audiJet = new Car;
        $audiJet->setTitle('Audi-jet');
        $audiJet->setEngine($jet);
        $audiJet->setSpeed(280);
        $audiJet->setDoors(2);
        $this->em->persist($audiJet);

        $this->em->flush();

        $this->translatable->setTranslatableLocale('de');
        $hayabusa->setTitle('Hayabusa de');
        $this->em->persist($hayabusa);
        $this->em->flush();
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            'Fixture\Translatable\Transport\Vehicle',
            'Fixture\Translatable\Transport\VehicleTranslation',
            'Fixture\Translatable\Transport\Car',
            'Fixture\Translatable\Transport\Engine',
            'Fixture\Translatable\Transport\Motorcycle',
        );
    }
}

