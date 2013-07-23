<?php

namespace Timestampable;

use Doctrine\Common\EventManager;
use TestTool\ObjectManagerTestCase;
use Fixture\Timestampable\Superclassed\Person;
use Gedmo\Timestampable\TimestampableListener;

class SuperclassTest extends ObjectManagerTestCase
{
    const PERSON = "Fixture\Timestampable\Superclassed\Person";

    private $em;

    protected function setUp()
    {
        $evm = new EventManager;
        $evm->addEventSubscriber(new TimestampableListener);

        $this->em = $this->createEntityManager($evm);
        $this->createSchema($this->em, array(
            self::PERSON,
        ));
    }

    protected function tearDown()
    {
        $this->releaseEntityManager($this->em);
    }

    /**
     * @test
     */
    function shouldUpdateOnChangeToAnyValue()
    {
        $person = new Person;
        $person->setName('name');
        $person->setSurname('lastname');

        $this->em->persist($person);
        $this->em->flush();

        $this->assertNull($person->getNameChangedAt());

        $person->setName('changed name');

        $this->em->persist($person);
        $this->em->flush();

        $this->assertNotNull($person->getNameChangedAt());

        $nameChangedDateBefore = $person->getNameChangedAt();
        $person->setName('new name');

        $this->em->persist($person);
        $this->em->flush();

        $this->assertNotSame($nameChangedDateBefore, $person->getNameChangedAt());

        $nameChangedDateBefore = $person->getNameChangedAt();
        $person->setSurname('new lastname');

        $this->em->persist($person);
        $this->em->flush();

        $this->assertSame($nameChangedDateBefore, $person->getNameChangedAt());
    }
}
