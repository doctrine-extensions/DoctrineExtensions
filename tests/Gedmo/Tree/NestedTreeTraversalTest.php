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
use Doctrine\ORM\OptimisticLockException;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Tests\Tree\Fixture\RootCategory;
use Gedmo\Tree\TreeListener;

/**
 * These are tests for Tree traversal
 */
final class NestedTreeTraversalTest extends BaseTestCaseORM
{
    public const CATEGORY = RootCategory::class;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new TreeListener());

        $this->getDefaultMockSqliteEntityManager($evm);
        $this->populate();
    }

    /**
     * @dataProvider provideNextNodes
     */
    public function testNextNode(array $expected, string $strategy): void
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $lvl1 = $repo->findOneBy(['title' => 'Part. 1']);

        $result = [];

        $current = null;
        while (null !== ($current = $repo->getNextNode($lvl1, $current, $strategy))) {
            $result[] = $current->getTitle();
        }
        static::assertSame($expected, $result);
    }

    /**
     * @dataProvider provideNextNodes
     */
    public function testNextNodes(array $expected, string $strategy): void
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $lvl1 = $repo->findOneBy(['title' => 'Part. 1']);

        $nextNodes = $repo->getNextNodes($lvl1, null, 10, $strategy);

        static::assertSame($expected, array_column($nextNodes, 'title'));
    }

    public function provideNextNodes(): iterable
    {
        yield 'Pre-order traversal' => [
            ['Part. 1', 'Part. 1.1', 'Part. 1.2', 'Part. 1.2.1', 'Part. 1.2.2', 'Part. 1.3'],
            'pre_order',
        ];
        yield 'Level-order traversal' => [
            ['Part. 1.2.1', 'Part. 1.2.2', 'Part. 1.1', 'Part. 1.2', 'Part. 1.3', 'Part. 1'],
            'level_order',
        ];
    }

    protected function getUsedEntityFixtures(): array
    {
        return [self::CATEGORY];
    }

    /**
     * @throws OptimisticLockException
     */
    private function populate(): void
    {
        $lvl1 = new RootCategory();
        $lvl1->setTitle('Part. 1');

        $lvl2 = new RootCategory();
        $lvl2->setTitle('Part. 2');

        $lvl11 = new RootCategory();
        $lvl11->setTitle('Part. 1.1');
        $lvl11->setParent($lvl1);

        $lvl12 = new RootCategory();
        $lvl12->setTitle('Part. 1.2');
        $lvl12->setParent($lvl1);

        $lvl121 = new RootCategory();
        $lvl121->setTitle('Part. 1.2.1');
        $lvl121->setParent($lvl12);

        $lvl122 = new RootCategory();
        $lvl122->setTitle('Part. 1.2.2');
        $lvl122->setParent($lvl12);

        $lvl13 = new RootCategory();
        $lvl13->setTitle('Part. 1.3');
        $lvl13->setParent($lvl1);

        $lvl21 = new RootCategory();
        $lvl21->setTitle('Part. 2.1');
        $lvl21->setParent($lvl2);

        $lvl22 = new RootCategory();
        $lvl22->setTitle('Part. 2.2');
        $lvl22->setParent($lvl2);

        $this->em->persist($lvl1);
        $this->em->persist($lvl2);
        $this->em->persist($lvl11);
        $this->em->persist($lvl12);
        $this->em->persist($lvl121);
        $this->em->persist($lvl122);
        $this->em->persist($lvl13);
        $this->em->persist($lvl21);
        $this->em->persist($lvl22);
        $this->em->flush();
        $this->em->clear();
    }
}
