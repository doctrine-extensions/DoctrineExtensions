<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Doctrine\Common\EventManager;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Tests\Sluggable\Fixture\Embeddable\Address;
use Gedmo\Tests\Sluggable\Fixture\Embeddable\User;
use Gedmo\Tests\Tool\BaseTestCaseORM;

final class SluggableEmbeddableTest extends BaseTestCaseORM
{
    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());

        $this->getDefaultMockSqliteEntityManager($evm);
    }

    public function testShouldHandleSlugWithEmbeddable(): void
    {
        $address = new Address();
        $address->setStreet('street');
        $address->setCity('city');
        $address->setPostalCode('postal code');
        $address->setCountry('country');

        $user = new User();
        $user->setUsername('username');
        $user->setAddress($address);

        $this->em->persist($user);
        $this->em->flush();
        $this->em->clear();

        static::assertSame('username-city-country', $user->getSlug());
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            User::class,
        ];
    }
}
