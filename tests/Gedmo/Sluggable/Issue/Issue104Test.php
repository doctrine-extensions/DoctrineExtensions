<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sluggable\Issue;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\MappingException;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Tests\Sluggable\Fixture\Issue104\Car;
use Gedmo\Tests\Tool\BaseTestCaseORM;

/**
 * These are tests for Sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class Issue104Test extends BaseTestCaseORM
{
    private const CAR = Car::class;

    public function testShouldThrowAnExceptionWhenMappedSuperclassProtectedProperty(): void
    {
        $this->expectException(MappingException::class);
        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());
        $this->getDefaultMockSqliteEntityManager($evm);

        $audi = new Car();
        $audi->setDescription('audi car');
        $audi->setTitle('Audi');

        $this->em->persist($audi);
        $this->em->flush();
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::CAR,
        ];
    }
}
