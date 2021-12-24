<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Tree;

use Doctrine\Common\EventManager;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Tests\Tree\Fixture\Genealogy\Man;
use Gedmo\Tests\Tree\Fixture\Genealogy\Person;
use Gedmo\Tests\Tree\Fixture\Genealogy\Woman;
use Gedmo\Tree\TreeListener;

/**
 * Additional tests for tree inheritance and in-memory updates
 *
 * @author Illya Klymov <xanf@xanf.me>
 */
final class InMemoryUpdatesWithInheritanceTest extends BaseTestCaseORM
{
    public const PERSON = Person::class;
    public const MAN = Man::class;
    public const WOMAN = Woman::class;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new TreeListener());

        $this->getDefaultMockSqliteEntityManager($evm);
    }

    public function testInMemoryTreeInsertsWithInheritance(): void
    {
        $nodes = [];

        $man1 = new Man('Root - Man1');
        $this->em->persist($man1);

        $woman1 = new Woman('Level 1 - Woman1');
        $this->em->persist($woman1);
        $woman1->setParent($man1);

        $man2 = new Man('Level 2 - Man2');
        $this->em->persist($man2);
        $man2->setParent($woman1);

        $woman2 = new Woman('Level 3 - Woman2');
        $this->em->persist($woman2);
        $woman2->setParent($man2);

        $this->em->flush();

        $left = $man1->getLeft();
        $right = $man1->getRight();
        $level = $man1->getLevel();
        static::assertSame(1, $left);
        static::assertSame(8, $right);
        static::assertSame(0, $level);

        $left = $woman1->getLeft();
        $right = $woman1->getRight();
        $level = $woman1->getLevel();
        static::assertSame(2, $left);
        static::assertSame(7, $right);
        static::assertSame(1, $level);

        $left = $man2->getLeft();
        $right = $man2->getRight();
        $level = $man2->getLevel();
        static::assertSame(3, $left);
        static::assertSame(6, $right);
        static::assertSame(2, $level);

        $left = $woman2->getLeft();
        $right = $woman2->getRight();
        $level = $woman2->getLevel();
        static::assertSame(4, $left);
        static::assertSame(5, $right);
        static::assertSame(3, $level);
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::PERSON,
            self::MAN,
            self::WOMAN,
        ];
    }
}
