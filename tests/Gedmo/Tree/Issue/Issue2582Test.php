<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Tree\Issue;

use Doctrine\Common\EventManager;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Tests\Tree\Fixture\Issue2582\OU;
use Gedmo\Tests\Tree\Fixture\Issue2582\OUWithRoot;
use Gedmo\Tree\TreeListener;

final class Issue2582Test extends BaseTestCaseORM
{
    private TreeListener $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new TreeListener();

        $evm = new EventManager();
        $evm->addEventSubscriber($this->listener);

        $this->getDefaultMockSqliteEntityManager($evm);
    }

    public function testInsertTwoRootsInOneFlush(): void
    {
        $ou1 = new OU('00000000-0000-0000-0000-000000000001', null);
        $ou11 = new OU('00000000-0000-0000-0000-000000000011', $ou1);
        $ou2 = new OU('00000000-0000-0000-0000-000000000002', null);
        $ou21 = new OU('00000000-0000-0000-0000-000000000021', $ou2);

        $this->em->persist($ou1);
        $this->em->persist($ou11);
        $this->em->persist($ou2);
        $this->em->persist($ou21);
        $this->em->flush();

        $this->em->clear();

        $expected = [
            ['00000000-0000-0000-0000-000000000001', null, 1, 0, 4],
            ['00000000-0000-0000-0000-000000000011', '00000000-0000-0000-0000-000000000001', 2, 1, 3],
            ['00000000-0000-0000-0000-000000000002', null, 5, 0, 8],
            ['00000000-0000-0000-0000-000000000021', '00000000-0000-0000-0000-000000000002', 6, 1, 7],
        ];
        $this->assertSameOuTree($expected);
    }

    public function testInsertTwoRootsInOneFlushRootsFirst(): void
    {
        $ou1 = new OU('00000000-0000-0000-0000-000000000001', null);
        $ou11 = new OU('00000000-0000-0000-0000-000000000011', $ou1);
        $ou2 = new OU('00000000-0000-0000-0000-000000000002', null);
        $ou21 = new OU('00000000-0000-0000-0000-000000000021', $ou2);

        $this->em->persist($ou1);
        $this->em->persist($ou2);
        $this->em->persist($ou11);
        $this->em->persist($ou21);
        $this->em->flush();

        $this->em->clear();

        $expected = [
            ['00000000-0000-0000-0000-000000000001', null, 1, 0, 4],
            ['00000000-0000-0000-0000-000000000011', '00000000-0000-0000-0000-000000000001', 2, 1, 3],
            ['00000000-0000-0000-0000-000000000002', null, 5, 0, 8],
            ['00000000-0000-0000-0000-000000000021', '00000000-0000-0000-0000-000000000002', 6, 1, 7],
        ];
        $this->assertSameOuTree($expected);
    }

    public function testInsertTwoRootsInTwoFlushes(): void
    {
        $ou1 = new OU('00000000-0000-0000-0000-000000000001', null);
        $ou11 = new OU('00000000-0000-0000-0000-000000000011', $ou1);
        $ou2 = new OU('00000000-0000-0000-0000-000000000002', null);
        $ou21 = new OU('00000000-0000-0000-0000-000000000021', $ou2);

        $this->em->persist($ou1);
        $this->em->persist($ou11);
        $this->em->flush();
        $this->em->persist($ou2);
        $this->em->persist($ou21);
        $this->em->flush();

        $this->em->clear();

        $expected = [
            ['00000000-0000-0000-0000-000000000001', null, 1, 0, 4],
            ['00000000-0000-0000-0000-000000000011', '00000000-0000-0000-0000-000000000001', 2, 1, 3],
            ['00000000-0000-0000-0000-000000000002', null, 5, 0, 8],
            ['00000000-0000-0000-0000-000000000021', '00000000-0000-0000-0000-000000000002', 6, 1, 7],
        ];
        $this->assertSameOuTree($expected);
    }

    public function testInsertNonRootBeforeRootInOneFlush(): void
    {
        $ou1 = new OU('00000000-0000-0000-0000-000000000001', null);
        $this->em->persist($ou1);
        $this->em->flush();
        $this->em->clear();

        $expected = [
            ['00000000-0000-0000-0000-000000000001', null, 1, 0, 2],
        ];
        $this->assertSameOuTree($expected);

        $ou1 = $this->em->getRepository(OU::class)->find('00000000-0000-0000-0000-000000000001');
        $ou11 = new OU('00000000-0000-0000-0000-000000000011', $ou1);
        $ou2 = new OU('00000000-0000-0000-0000-000000000002', null);
        $ou21 = new OU('00000000-0000-0000-0000-000000000021', $ou2);

        $this->em->persist($ou11);
        $this->em->persist($ou2);
        $this->em->persist($ou21);
        $this->em->flush();

        $this->em->clear();

        $expected = [
            ['00000000-0000-0000-0000-000000000001', null, 1, 0, 4],
            ['00000000-0000-0000-0000-000000000011', '00000000-0000-0000-0000-000000000001', 2, 1, 3],
            ['00000000-0000-0000-0000-000000000002', null, 5, 0, 8],
            ['00000000-0000-0000-0000-000000000021', '00000000-0000-0000-0000-000000000002', 6, 1, 7],
        ];
        $this->assertSameOuTree($expected);
    }

    public function testInsertTwoWideRootsInOneFlush(): void
    {
        // First root: a child that itself has two children (treeSize 6).
        $ou1 = new OU('00000000-0000-0000-0000-000000000001', null);
        $ou11 = new OU('00000000-0000-0000-0000-000000000011', $ou1);
        $ou111 = new OU('00000000-0000-0000-0000-000000000111', $ou11);
        $ou112 = new OU('00000000-0000-0000-0000-000000000112', $ou11);

        // Second root: two children (treeSize 6).
        $ou2 = new OU('00000000-0000-0000-0000-000000000002', null);
        $ou21 = new OU('00000000-0000-0000-0000-000000000021', $ou2);
        $ou22 = new OU('00000000-0000-0000-0000-000000000022', $ou2);

        $this->em->persist($ou1);
        $this->em->persist($ou11);
        $this->em->persist($ou111);
        $this->em->persist($ou112);
        $this->em->persist($ou2);
        $this->em->persist($ou21);
        $this->em->persist($ou22);
        $this->em->flush();

        $this->em->clear();

        $expected = [
            ['00000000-0000-0000-0000-000000000001', null, 1, 0, 8],
            ['00000000-0000-0000-0000-000000000011', '00000000-0000-0000-0000-000000000001', 2, 1, 7],
            ['00000000-0000-0000-0000-000000000111', '00000000-0000-0000-0000-000000000011', 3, 2, 4],
            ['00000000-0000-0000-0000-000000000112', '00000000-0000-0000-0000-000000000011', 5, 2, 6],
            ['00000000-0000-0000-0000-000000000002', null, 9, 0, 14],
            ['00000000-0000-0000-0000-000000000021', '00000000-0000-0000-0000-000000000002', 10, 1, 11],
            ['00000000-0000-0000-0000-000000000022', '00000000-0000-0000-0000-000000000002', 12, 1, 13],
        ];
        $this->assertSameOuTree($expected);
    }

    public function testInsertFourRootsInOneFlush(): void
    {
        $ou1 = new OU('00000000-0000-0000-0000-000000000001', null);
        $ou11 = new OU('00000000-0000-0000-0000-000000000011', $ou1);
        $ou2 = new OU('00000000-0000-0000-0000-000000000002', null);
        $ou21 = new OU('00000000-0000-0000-0000-000000000021', $ou2);
        $ou3 = new OU('00000000-0000-0000-0000-000000000003', null);
        $ou31 = new OU('00000000-0000-0000-0000-000000000031', $ou3);
        $ou4 = new OU('00000000-0000-0000-0000-000000000004', null);
        $ou41 = new OU('00000000-0000-0000-0000-000000000041', $ou4);

        $this->em->persist($ou1);
        $this->em->persist($ou11);
        $this->em->persist($ou2);
        $this->em->persist($ou21);
        $this->em->persist($ou3);
        $this->em->persist($ou31);
        $this->em->persist($ou4);
        $this->em->persist($ou41);
        $this->em->flush();

        $this->em->clear();

        $expected = [
            ['00000000-0000-0000-0000-000000000001', null, 1, 0, 4],
            ['00000000-0000-0000-0000-000000000011', '00000000-0000-0000-0000-000000000001', 2, 1, 3],
            ['00000000-0000-0000-0000-000000000002', null, 5, 0, 8],
            ['00000000-0000-0000-0000-000000000021', '00000000-0000-0000-0000-000000000002', 6, 1, 7],
            ['00000000-0000-0000-0000-000000000003', null, 9, 0, 12],
            ['00000000-0000-0000-0000-000000000031', '00000000-0000-0000-0000-000000000003', 10, 1, 11],
            ['00000000-0000-0000-0000-000000000004', null, 13, 0, 16],
            ['00000000-0000-0000-0000-000000000041', '00000000-0000-0000-0000-000000000004', 14, 1, 15],
        ];
        $this->assertSameOuTree($expected);
    }

    public function testRelocateExistingNodeToRootAndInsertNewRootInOneFlush(): void
    {
        $ou1 = new OU('00000000-0000-0000-0000-000000000001', null);
        $ou11 = new OU('00000000-0000-0000-0000-000000000011', $ou1);
        $this->em->persist($ou1);
        $this->em->persist($ou11);
        $this->em->flush();
        $this->em->clear();

        $expected = [
            ['00000000-0000-0000-0000-000000000001', null, 1, 0, 4],
            ['00000000-0000-0000-0000-000000000011', '00000000-0000-0000-0000-000000000001', 2, 1, 3],
        ];
        $this->assertSameOuTree($expected);

        $ou11 = $this->em->getRepository(OU::class)->find('00000000-0000-0000-0000-000000000011');
        $ou11->setParent(null);
        $ou2 = new OU('00000000-0000-0000-0000-000000000002', null);
        $ou21 = new OU('00000000-0000-0000-0000-000000000021', $ou2);

        $this->em->persist($ou2);
        $this->em->persist($ou21);
        $this->em->flush();

        $this->em->clear();

        $expected = [
            ['00000000-0000-0000-0000-000000000011', null, 1, 0, 2],
            ['00000000-0000-0000-0000-000000000001', null, 3, 0, 4],
            ['00000000-0000-0000-0000-000000000002', null, 5, 0, 8],
            ['00000000-0000-0000-0000-000000000021', '00000000-0000-0000-0000-000000000002', 6, 1, 7],
        ];
        $this->assertSameOuTree($expected);
    }

    public function testSetParentKeepsBidirectionalAssociationConsistent(): void
    {
        $rootA = new OU('00000000-0000-0000-0000-0000000000a1', null);
        $rootB = new OU('00000000-0000-0000-0000-0000000000b1', null);
        $child = new OU('00000000-0000-0000-0000-0000000000c1', $rootA);

        static::assertTrue($rootA->getChildren()->contains($child));
        static::assertFalse($rootB->getChildren()->contains($child));

        $child->setParent($rootB);
        static::assertFalse($rootA->getChildren()->contains($child));
        static::assertTrue($rootB->getChildren()->contains($child));
        static::assertCount(1, $rootB->getChildren());

        $child->setParent($rootB);
        static::assertCount(1, $rootB->getChildren());

        $child->setParent(null);
        static::assertFalse($rootB->getChildren()->contains($child));
        static::assertNull($child->getParent());
    }

    public function testInsertTwoRootsInOneFlushWithTreeRoot(): void
    {
        $ou1 = new OUWithRoot('00000000-0000-0000-0000-000000000001', null);
        $ou11 = new OUWithRoot('00000000-0000-0000-0000-000000000011', $ou1);
        $ou2 = new OUWithRoot('00000000-0000-0000-0000-000000000002', null);
        $ou21 = new OUWithRoot('00000000-0000-0000-0000-000000000021', $ou2);

        $this->em->persist($ou1);
        $this->em->persist($ou11);
        $this->em->persist($ou2);
        $this->em->persist($ou21);
        $this->em->flush();

        $this->em->clear();

        $expected = [
            ['00000000-0000-0000-0000-000000000001', '00000000-0000-0000-0000-000000000001', null, 1, 0, 4],
            ['00000000-0000-0000-0000-000000000011', '00000000-0000-0000-0000-000000000001', '00000000-0000-0000-0000-000000000001', 2, 1, 3],
            ['00000000-0000-0000-0000-000000000002', '00000000-0000-0000-0000-000000000002', null, 1, 0, 4],
            ['00000000-0000-0000-0000-000000000021', '00000000-0000-0000-0000-000000000002', '00000000-0000-0000-0000-000000000002', 2, 1, 3],
        ];

        $actual = [];
        foreach ($this->fetchAllOUs(OUWithRoot::class, [['root', 'ASC'], ['left', 'ASC']]) as $i => $a) {
            $actual[$i] = [
                $a->getId(),
                $a->getRoot() ? $a->getRoot()->getId() : null,
                $a->getParent() ? $a->getParent()->getId() : null,
                $a->getLeft(),
                $a->getLevel(),
                $a->getRight(),
            ];
        }
        static::assertSame($expected, $actual);
    }

    protected function getUsedEntityFixtures(): array
    {
        return [OU::class, OUWithRoot::class];
    }

    /**
     * @param list<array{string, string|null, int, int, int}> $expected
     */
    private function assertSameOuTree(array $expected): void
    {
        $actual = [];
        foreach ($this->fetchAllOUs(OU::class, [['left', 'ASC']]) as $i => $a) {
            $actual[$i] = [
                $a->getId(),
                $a->getParent() ? $a->getParent()->getId() : null,
                $a->getLeft(),
                $a->getLevel(),
                $a->getRight(),
            ];
        }
        static::assertSame($expected, $actual);
    }

    /**
     * @template T of object
     *
     * @param class-string<T>             $entityClass
     * @param list<array{string, string}> $orderBy
     *
     * @return list<T>
     */
    private function fetchAllOUs(string $entityClass, array $orderBy): array
    {
        $categoryRepo = $this->em->getRepository($entityClass);
        $qb = $categoryRepo->createQueryBuilder('ou');
        foreach ($orderBy as $field) {
            $qb->addOrderBy('ou.'.$field[0], $field[1]);
        }

        return $qb
            ->getQuery()
            ->getResult();
    }
}
