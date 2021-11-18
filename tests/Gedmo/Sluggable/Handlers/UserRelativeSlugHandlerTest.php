<?php

namespace Gedmo\Tests\Sluggable;

use Doctrine\Common\EventManager;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Tests\Sluggable\Fixture\Handler\Company;
use Gedmo\Tests\Sluggable\Fixture\Handler\User;
use Gedmo\Tests\Tool\BaseTestCaseORM;

/**
 * These are tests for Sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
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

        $this->getMockSqliteEntityManager($evm);
    }

    public function testRelativeSlug()
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

    protected function getUsedEntityFixtures()
    {
        return [
            self::USER,
            self::COMPANY,
        ];
    }
}
