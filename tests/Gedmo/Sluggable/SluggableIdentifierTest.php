<?php

namespace Gedmo\Tests\Sluggable;

use Doctrine\Common\EventManager;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Tests\Sluggable\Fixture\Identifier;
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
final class SluggableIdentifierTest extends BaseTestCaseORM
{
    public const TARGET = Identifier::class;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());

        $this->getMockSqliteEntityManager($evm);
    }

    /**
     * @test
     */
    public function shouldBePossibleToSlugIdentifiers()
    {
        $sport = new Identifier();
        $sport->setTitle('Sport');
        $this->em->persist($sport);
        $this->em->flush();

        static::assertSame('sport', $sport->getId());
    }

    /**
     * @test
     */
    public function shouldPersistMultipleNonConflictingIdentifierSlugs()
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

    protected function getUsedEntityFixtures()
    {
        return [
            self::TARGET,
        ];
    }
}
