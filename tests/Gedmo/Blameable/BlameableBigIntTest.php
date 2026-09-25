<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Blameable;

use Doctrine\Common\EventManager;
use Gedmo\Tests\Blameable\Fixture\Entity\CompanyBigInt;
use Gedmo\Tests\Tool\BaseTestCaseORM;

final class BlameableBigIntTest extends BaseTestCaseORM
{
    private int $userId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userId = 42;

        $listener = new BlameableListener();
        $listener->setUserValue($this->userId);

        $evm = new EventManager();
        $evm->addEventSubscriber($listener);

        $this->getDefaultMockSqliteEntityManager($evm);
    }

    public function testBlameableBigInt(): void
    {
        $company = new CompanyBigInt();
        $company->setName('My Name');

        $this->em->persist($company);
        $this->em->flush();
        $this->em->clear();

        $foundCompany = $this->em->getRepository(CompanyBigInt::class)->findOneBy(['name' => 'My Name']);
        assert($foundCompany instanceof CompanyBigInt);
        $creator = $foundCompany->getCreator();

        static::assertSame($this->userId, $creator);
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            CompanyBigInt::class,
        ];
    }
}
