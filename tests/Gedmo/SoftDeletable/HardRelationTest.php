<?php

namespace Gedmo\SoftDeletable;

use Tool\BaseTestCaseORM;
use Doctrine\Common\EventManager;
use SoftDeletable\Fixture\Entity\Person;
use SoftDeletable\Fixture\Entity\Address;
use Gedmo\SoftDeletable\SoftDeletableListener;

class HardRelationTest extends BaseTestCaseORM
{
    private $softDeletableListener;

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber($this->softDeletableListener = new SoftDeletableListener);
        $this->getMockSqliteEntityManager($evm);
        $this->em->getConfiguration()->addFilter('softdelete', 'Gedmo\SoftDeletable\Filter\SoftDeletableFilter');
        $this->em->getFilters()->enable('softdelete');
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

        $person = $this->em->getRepository('SoftDeletable\Fixture\Entity\Person')->findOneById($person->getId());
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

        $address = $this->em->getRepository('SoftDeletable\Fixture\Entity\Address')->findOneById($address->getId());
        $this->assertNull($address, "Softdelete should cascade to hard relation entity");
    }

    /**
     * @test
     */
    function shouldHandleTimeAwareSoftDeletable()
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

        $person = $this->em->getRepository('SoftDeletable\Fixture\Entity\Person')->findOneById($person->getId());
        $this->assertNotNull($person, "Should not be softdeleted");

        $person->setDeletedAt(new \DateTime(date('Y-m-d H:i:s', time() - 15 * 3600))); // in an hour
        $this->em->persist($person);
        $this->em->flush();
        $this->em->clear();

        $person = $this->em->getRepository('SoftDeletable\Fixture\Entity\Person')->findOneById($person->getId());
        $this->assertNull($person, "Should be softdeleted");
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            'SoftDeletable\Fixture\Entity\Person',
            'SoftDeletable\Fixture\Entity\Address',
        );
    }
}
