<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sluggable;

use Doctrine\Common\EventManager;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Tests\Sluggable\Fixture\Identifier;
use Gedmo\Tests\Tool\BaseTestCaseORM;

/**
 * These are tests for Sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class SluggableIdentifierTest extends BaseTestCaseORM
{
    public const TARGET = Identifier::class;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());

        $this->getDefaultMockSqliteEntityManager($evm);
    }

    public function testShouldBePossibleToSlugIdentifiers(): void
    {
        $sport = new Identifier();
        $sport->setTitle('Sport');
        $this->em->persist($sport);
        $this->em->flush();

        static::assertSame('sport', $sport->getId());
    }

    public function testShouldPersistMultipleNonConflictingIdentifierSlugs(): void
    {
        $sport = new Identifier();
        $sport->setTitle('Sport');
        $this->em->persist($sport);

        $sport2 = new Identifier();
        $sport2->setTitle('Sport');
        $this->em->persist($sport2);
        $this->em->flush();

        static::assertSame('sport', $sport->getId());
        static::assertSame('sport_1', $sport2->getId());
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::TARGET,
        ];
    }
}
