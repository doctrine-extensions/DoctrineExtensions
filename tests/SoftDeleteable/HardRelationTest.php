<?php

namespace SoftDeleteable;

use TestTool\ObjectManagerTestCase;
use Doctrine\Common\EventManager;
use Fixture\SoftDeleteable\Person;
use Fixture\SoftDeleteable\Address;
use Gedmo\SoftDeleteable\SoftDeleteableListener;

class HardRelationTest extends ObjectManagerTestCase
{
    private $softDeleteableListener, $em;

    protected function setUp()
    {
        $evm = new EventManager;
        $evm->addEventSubscriber($this->softDeleteableListener = new SoftDeleteableListener);

        $this->em = $this->createEntityManager($evm);
        $this->em->getConfiguration()->addFilter('softdelete', 'Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter');
        $this->em->getFilters()->enable('softdelete');
        $this->createSchema($this->em, array(
            'Fixture\SoftDeleteable\Person',
            'Fixture\SoftDeleteable\Address',
        ));
    }

    protected function tearDown()
    {
        $this->releaseEntityManager($this->em);
    }

    /**
     * @test
     */
    function shouldCascadeSoftdeleteForHardRelations()
    {
        $address = new Address;
        $address->setStreet('13 Boulangerie, 404');

        $person = new Person;
        $person->setName('Gedi');
        $person->setAddress($address);

        $this->em->persist($address);
        $this->em->persist($person);
        $this->em->flush();

        // softdelete a hard relation
        $this->em->remove($address);
        $this->em->flush();
        $this->em->clear();

        $person = $this->em->getRepository('Fixture\SoftDeleteable\Person')->findOneById($person->getId());
        $this->assertNull($person, "Softdelete should cascade to hard relation entity");
    }

    /**
     * @test
     */
    function shouldCascadeToInversedRelationAsWell()
    {
        $address = new Address;
        $address->setStreet('13 Boulangerie, 404');

        $person = new Person;
        $person->setName('Gedi');
        $person->setAddress($address);

        $this->em->persist($address);
        $this->em->persist($person);
        $this->em->flush();

        // softdelete a hard relation
        $this->em->remove($person);
        $this->em->flush();
        $this->em->clear();

        $address = $this->em->getRepository('Fixture\SoftDeleteable\Address')->findOneById($address->getId());
        $this->assertNull($address, "Softdelete should cascade to hard relation entity");
    }

    /**
     * @test
     */
    function shouldHandleTimeAwareSoftDeleteable()
    {
        $address = new Address;
        $address->setStreet('13 Boulangerie, 404');

        $person = new Person;
        $person->setName('Gedi');
        $person->setDeletedAt(new \DateTime(date('Y-m-d H:i:s', time() + 15 * 3600))); // in an hour
        $person->setAddress($address);

        $this->em->persist($address);
        $this->em->persist($person);
        $this->em->flush();
        $this->em->clear();

        $person = $this->em->getRepository('SoftDeleteable\Fixture\Entity\Person')->findOneById($person->getId());
        $this->assertNotNull($person, "Should not be softdeleted");

        $person->setDeletedAt(new \DateTime(date('Y-m-d H:i:s', time() - 15 * 3600))); // in an hour
        $this->em->persist($person);
        $this->em->flush();
        $this->em->clear();

        $person = $this->em->getRepository('SoftDeleteable\Fixture\Entity\Person')->findOneById($person->getId());
        $this->assertNull($person, "Should be softdeleted");
    }
}
