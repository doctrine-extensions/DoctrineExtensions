<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Sluggable\Fixture\Handler\User;
use Sluggable\Fixture\Handler\Company;

/**
 * These are tests for Sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Sluggable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class UserRelativeSlugHandlerTest extends BaseTestCaseORM
{
    const USER = "Sluggable\\Fixture\\Handler\\User";
    const COMPANY = "Sluggable\\Fixture\\Handler\\Company";

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $evm->addEventSubscriber(new SluggableListener);

        $this->getMockSqliteEntityManager($evm);
    }

    public function testRelativeSlug()
    {
        $company = new Company;
        $company->setTitle('KnpLabs');
        $this->em->persist($company);

        $gedi = new User;
        $gedi->setUsername('Gedi');
        $gedi->setCompany($company);
        $this->em->persist($gedi);

        $this->em->flush();

        $this->assertEquals('knplabs/gedi', $gedi->getSlug(), 'relative slug is invalid');

        $company->setTitle('KnpLabs Nantes');
        $this->em->persist($company);
        $this->em->flush();

        $this->assertEquals('knplabs-nantes/gedi', $gedi->getSlug(), 'relative slug is invalid');
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::USER,
            self::COMPANY
        );
    }
}
