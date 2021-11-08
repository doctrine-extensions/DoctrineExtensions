<?php

namespace Gedmo\Tests\SoftDeleteable;

use Doctrine\Common\EventManager;
use Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter;
use Gedmo\SoftDeleteable\SoftDeleteableListener;
use Gedmo\Tests\SoftDeleteable\Fixture\Entity\Address;
use Gedmo\Tests\SoftDeleteable\Fixture\Entity\Person;
use Gedmo\Tests\Tool\BaseTestCaseORM;

final class HardRelationTest extends BaseTestCaseORM
{
    private $softDeleteableListener;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber($this->softDeleteableListener = new SoftDeleteableListener());
        $this->getMockSqliteEntityManager($evm);
        $this->em->getConfiguration()->addFilter('softdelete', SoftDeleteableFilter::class);
        $this->em->getFilters()->enable('softdelete');
    }

    /**
     * @test
     */
    public function shouldCascadeSoftdeleteForHardRelations()
    {
        $address = new Address();
        $address->setStreet('13 Boulangerie, 404');

        $person = new Person();
        $person->setName('Gedi');
        $person->setAddress($address);

        $this->em->persist($address);
        $this->em->persist($person);
        $this->em->flush();

        // softdelete a hard relation
        $this->em->remove($address);
        $this->em->flush();
        $this->em->clear();

        $person = $this->em->getRepository(Person::class)->findOneBy(['id' => $person->getId()]);
        static::assertNull($person, 'Softdelete should cascade to hard relation entity');
    }

    /**
     * @test
     */
    public function shouldCascadeToInversedRelationAsWell()
    {
        $address = new Address();
        $address->setStreet('13 Boulangerie, 404');

        $person = new Person();
        $person->setName('Gedi');
        $person->setAddress($address);

        $this->em->persist($address);
        $this->em->persist($person);
        $this->em->flush();

        // softdelete a hard relation
        $this->em->remove($person);
        $this->em->flush();
        $this->em->clear();

        $address = $this->em->getRepository(Address::class)->findOneBy(['id' => $address->getId()]);
        static::assertNull($address, 'Softdelete should cascade to hard relation entity');
    }

    /**
     * @test
     */
    public function shouldHandleTimeAwareSoftDeleteable()
    {
        $address = new Address();
        $address->setStreet('13 Boulangerie, 404');

        $person = new Person();
        $person->setName('Gedi');
        $person->setDeletedAt(new \DateTime(date('Y-m-d H:i:s', time() + 15 * 3600))); // in an hour
        $person->setAddress($address);

        $this->em->persist($address);
        $this->em->persist($person);
        $this->em->flush();
        $this->em->clear();

        $person = $this->em->getRepository(Person::class)->findOneBy(['id' => $person->getId()]);
        static::assertNotNull($person, 'Should not be softdeleted');

        $person->setDeletedAt(new \DateTime(date('Y-m-d H:i:s', time() - 15 * 3600))); // in an hour
        $this->em->persist($person);
        $this->em->flush();
        $this->em->clear();

        $person = $this->em->getRepository(Person::class)->findOneBy(['id' => $person->getId()]);
        static::assertNull($person, 'Should be softdeleted');
    }

    protected function getUsedEntityFixtures()
    {
        return [
            Person::class,
            Address::class,
        ];
    }
}
