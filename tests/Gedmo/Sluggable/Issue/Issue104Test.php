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
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Gedmo\Exception\InvalidMappingException;
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

    public static function setUpBeforeClass(): void
    {
        if (!class_exists(AnnotationDriver::class)) {
            static::markTestSkipped('Test validates checks for invalid mapping configuration which have changed between ORM 2.x and 3.x causing the ORM to abort before reaching our checks.');
        }
    }

    public function testShouldThrowAnExceptionWhenMappedSuperclassProtectedProperty(): void
    {
        $this->expectException(InvalidMappingException::class);
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
