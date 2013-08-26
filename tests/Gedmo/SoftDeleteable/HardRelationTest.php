<?php

namespace Gedmo\SoftDeleteable;

use Gedmo\TestTool\ObjectManagerTestCase;
use Doctrine\Common\EventManager;
use Gedmo\Fixture\SoftDeleteable\Person;
use Gedmo\Fixture\SoftDeleteable\Address;
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
            'Gedmo\Fixture\SoftDeleteable\Person',
            'Gedmo\Fixture\SoftDeleteable\Address',
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

        $person = $this->em->getRepository('Gedmo\Fixture\SoftDeleteable\Person')->findOneById($person->getId());
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

        $address = $this->em->getRepository('Gedmo\Fixture\SoftDeleteable\Address')->findOneById($address->getId());
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

        $person = $this->em->getRepository('Gedmo\Fixture\SoftDeleteable\Person')->findOneById($person->getId());
        $this->assertNotNull($person, "Should not be softdeleted");

        $person->setDeletedAt(new \DateTime(date('Y-m-d H:i:s', time() - 15 * 3600))); // in an hour
        $this->em->persist($person);
        $this->em->flush();
        $this->em->clear();

        $person = $this->em->getRepository('Gedmo\Fixture\SoftDeleteable\Person')->findOneById($person->getId());
        $this->assertNull($person, "Should be softdeleted");
    }
}
