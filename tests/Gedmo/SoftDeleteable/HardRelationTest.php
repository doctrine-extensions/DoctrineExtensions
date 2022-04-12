<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\SoftDeleteable;

use Doctrine\Common\EventManager;
use Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter;
use Gedmo\SoftDeleteable\SoftDeleteableListener;
use Gedmo\Tests\SoftDeleteable\Fixture\Entity\Address;
use Gedmo\Tests\SoftDeleteable\Fixture\Entity\Person;
use Gedmo\Tests\Tool\BaseTestCaseORM;

final class HardRelationTest extends BaseTestCaseORM
{
    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new SoftDeleteableListener());
        $this->getDefaultMockSqliteEntityManager($evm);
        $this->em->getConfiguration()->addFilter('softdelete', SoftDeleteableFilter::class);
        $this->em->getFilters()->enable('softdelete');
    }

    public function testShouldCascadeSoftdeleteForHardRelations(): void
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

    public function testShouldCascadeToInversedRelationAsWell(): void
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

    public function testShouldHandleTimeAwareSoftDeleteable(): void
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

    protected function getUsedEntityFixtures(): array
    {
        return [
            Person::class,
            Address::class,
        ];
    }
}
