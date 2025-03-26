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
use Gedmo\Tree\TreeListener;

class Issue2582Test extends BaseTestCaseORM
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
        foreach ($this->fetchAllOUs() as $i => $a) {
            static::assertSame(
                $expected[$i],
                [
                    $a->getId(),
                    $a->getParent() ? $a->getParent()->getId() : null,
                    $a->getLeft(),
                    $a->getLevel(),
                    $a->getRight(),
                ],
            );
        }
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
        foreach ($this->fetchAllOUs() as $i => $a) {
            static::assertSame(
                $expected[$i],
                [
                    $a->getId(),
                    $a->getParent() ? $a->getParent()->getId() : null,
                    $a->getLeft(),
                    $a->getLevel(),
                    $a->getRight(),
                ],
            );
        }
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
        foreach ($this->fetchAllOUs() as $i => $a) {
            static::assertSame(
                $expected[$i],
                [
                    $a->getId(),
                    $a->getParent() ? $a->getParent()->getId() : null,
                    $a->getLeft(),
                    $a->getLevel(),
                    $a->getRight(),
                ],
            );
        }
    }

    protected function getUsedEntityFixtures(): array
    {
        return [OU::class];
    }

    /**
     * @return list<OU>
     */
    private function fetchAllOUs(): array
    {
        $categoryRepo = $this->em->getRepository(OU::class);
        return $categoryRepo
            ->createQueryBuilder('ou')
            ->orderBy('ou.left', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
