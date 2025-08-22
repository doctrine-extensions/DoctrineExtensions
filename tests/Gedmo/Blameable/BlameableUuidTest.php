<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Blameable;

use Doctrine\Common\EventManager;
use Gedmo\Blameable\BlameableListener;
use Gedmo\Tests\Blameable\Fixture\Entity\Company;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV6;

final class BlameableUuidTest extends BaseTestCaseORM
{
    private UuidV6 $uuid;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uuid = Uuid::v6();

        $listener = new BlameableListener();
        $listener->setUserValue($this->uuid);

        $evm = new EventManager();
        $evm->addEventSubscriber($listener);

        $this->getDefaultMockSqliteEntityManager($evm);
    }

    public function testBlameableUuid(): void
    {
        $company = new Company();
        $company->setName('ACME');

        $this->em->persist($company);
        $this->em->flush();
        $this->em->clear();

        /**
         * @var Company $foundCompany
         */
        $foundCompany = $this->em->getRepository(Company::class)->findOneBy(['name' => 'ACME']);
        $created = $foundCompany->getCreated();
        $createdUuid = $created instanceof Uuid ? $created->toRfc4122() : null;

        static::assertSame($this->uuid->toRfc4122(), $createdUuid);
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            Company::class,
        ];
    }
}
