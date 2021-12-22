<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sluggable\Handlers;

use Doctrine\Common\EventManager;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Tests\Sluggable\Fixture\Handler\Company;
use Gedmo\Tests\Sluggable\Fixture\Handler\User;
use Gedmo\Tests\Tool\BaseTestCaseORM;

/**
 * These are tests for Sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class UserRelativeSlugHandlerTest extends BaseTestCaseORM
{
    public const USER = User::class;
    public const COMPANY = Company::class;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());

        $this->getDefaultMockSqliteEntityManager($evm);
    }

    public function testRelativeSlug(): void
    {
        $company = new Company();
        $company->setTitle('KnpLabs');
        $this->em->persist($company);

        $gedi = new User();
        $gedi->setUsername('Gedi');
        $gedi->setCompany($company);
        $this->em->persist($gedi);

        $this->em->flush();

        static::assertSame('knplabs/gedi', $gedi->getSlug(), 'relative slug is invalid');

        $company->setTitle('KnpLabs Nantes');
        $this->em->persist($company);
        $this->em->flush();

        static::assertSame('knplabs-nantes/gedi', $gedi->getSlug(), 'relative slug is invalid');
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::USER,
            self::COMPANY,
        ];
    }
}
