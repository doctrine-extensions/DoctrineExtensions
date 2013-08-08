<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventManager;
use Gedmo\TestTool\ObjectManagerTestCase;
use Gedmo\Fixture\Sluggable\Genealogy\Person;
use Gedmo\Fixture\Sluggable\Genealogy\Man;
use Gedmo\Fixture\Sluggable\Genealogy\Woman;
use Gedmo\Fixture\Sluggable\Genealogy\Employee;
use Gedmo\Sluggable\SluggableListener;

class InheritedSlugFieldsTest extends ObjectManagerTestCase
{
    const PERSON = 'Gedmo\Fixture\Sluggable\Genealogy\Person';
    const MAN = 'Gedmo\Fixture\Sluggable\Genealogy\Man';
    const WOMAN = 'Gedmo\Fixture\Sluggable\Genealogy\Woman';
    const EMPLOYEE = 'Gedmo\Fixture\Sluggable\Genealogy\Employee';

    private $em;

    protected function setUp()
    {
        $evm = new EventManager;
        $evm->addEventSubscriber(new SluggableListener);

        $this->em = $this->createEntityManager($evm);
        $this->createSchema($this->em, array(
            self::PERSON,
            self::MAN,
            self::WOMAN,
            self::EMPLOYEE,
        ));
    }

    protected function tearDown()
    {
        $this->releaseEntityManager($this->em);
    }

    /**
     * @test
     */
    function shouldSupportInheritance()
    {
        $joe = new Man;
        $joe->setName('Joe');
        $joe->setSurname('Kaspersky');
        $joe->setRegion('Europe');
        $this->em->persist($joe);

        $tom = new Employee;
        $tom->setName('Tom');
        $tom->setSurname('Backer');
        $tom->setRegion('United States');
        $tom->setOccupation('Developer');
        $this->em->persist($tom);

        $tom2 = new Man;
        $tom2->setName('Tom');
        $tom2->setSurname('Backer');
        $tom2->setRegion('United States');
        $this->em->persist($tom2);

        $tom3 = new Woman;
        $tom3->setName('Tom');
        $tom3->setRegion('United States');
        $this->em->persist($tom3);

        $this->em->flush();

        $this->assertSame('joe-europe', $joe->getUri());
        $this->assertSame('joe-kaspersky-europe', $joe->getSlug());

        $this->assertSame('tom-united-states', $tom->getUri());
        $this->assertSame('tom-backer-united-states', $tom->getSlug());
        $this->assertSame('tom-backer-developer-united-states', $tom->getWorkerSlug());

        $this->assertSame('tom-united-states-1', $tom2->getUri());
        $this->assertSame('tom-backer-united-states-1', $tom2->getSlug());

        $this->assertSame('tom-united-states-2', $tom3->getUri());
    }

    /**
     * @test
     */
    function shouldSupportInheritanceAfterFlush()
    {
        $joe = new Man;
        $joe->setName('Joe');
        $joe->setSurname('Kaspersky');
        $joe->setRegion('Europe');
        $this->em->persist($joe);
        $this->em->flush();

        $tom = new Employee;
        $tom->setName('Tom');
        $tom->setSurname('Backer');
        $tom->setRegion('United States');
        $tom->setOccupation('Developer');
        $this->em->persist($tom);
        $this->em->flush();
        $this->em->clear();

        $tom2 = new Man;
        $tom2->setName('Tom');
        $tom2->setSurname('Backer');
        $tom2->setRegion('United States');
        $this->em->persist($tom2);
        $this->em->flush();

        $tom3 = new Woman;
        $tom3->setName('Tom');
        $tom3->setRegion('United States');
        $this->em->persist($tom3);
        $this->em->flush();

        $this->assertSame('joe-europe', $joe->getUri());
        $this->assertSame('joe-kaspersky-europe', $joe->getSlug());

        $this->assertSame('tom-united-states', $tom->getUri());
        $this->assertSame('tom-backer-united-states', $tom->getSlug());
        $this->assertSame('tom-backer-developer-united-states', $tom->getWorkerSlug());

        $this->assertSame('tom-united-states-1', $tom2->getUri());
        $this->assertSame('tom-backer-united-states-1', $tom2->getSlug());

        $this->assertSame('tom-united-states-2', $tom3->getUri());
    }
}
