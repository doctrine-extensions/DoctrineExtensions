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
use Gedmo\Tests\Tree\Fixture\Closure\Category;
use Gedmo\Tests\Tree\Fixture\Closure\CategoryClosure;
use Gedmo\Tests\Tree\Fixture\Issue2652\Category2;
use Gedmo\Tests\Tree\Fixture\Issue2652\Category2Closure;
use Gedmo\Tree\TreeListener;


/**
 * These are tests for Tree behavior
 *
 * @author Gustavo Adrian <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class Issue2652Test extends BaseTestCaseORM
{
    public const CATEGORY = Category::class;
    public const CLOSURE = CategoryClosure::class;
    public const CATEGORY2 = Category2::class;
    public const CLOSURE2 = Category2Closure::class;

    /**
     * @var TreeListener
     */
    protected $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new TreeListener();

        $evm = new EventManager();
        $evm->addEventSubscriber($this->listener);

        $this->getDefaultMockSqliteEntityManager($evm);
        $this->populate();
    }

    public function testAddMultipleEntityTypes(): void
    {
        $repo = $this->em->getRepository(self::CATEGORY);

        $food = $repo->findOneBy(['title' => 'Food']);
        $dql = 'SELECT c FROM '.self::CLOSURE.' c';
        $dql .= ' WHERE c.ancestor = :ancestor';
        $query = $this->em->createQuery($dql);
        $query->setParameter('ancestor', $food);

        $foodClosures = $query->getResult();
        static::assertCount(1, $foodClosures);

        $repo = $this->em->getRepository(self::CATEGORY2);
        $monkey = $repo->findOneBy(['title' => 'Monkey']);
        $dql = 'SELECT c FROM '.self::CLOSURE2.' c';
        $dql .= ' WHERE c.ancestor = :ancestor';
        $query = $this->em->createQuery($dql);
        $query->setParameter('ancestor', $monkey);

        $monkeyClosures = $query->getResult();
        static::assertCount(1, $monkeyClosures);
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::CATEGORY,
            self::CLOSURE,
            self::CATEGORY2,
            self::CLOSURE2,
        ];
    }

    private function populate(): void
    {
        $food = new Category();
        $food->setTitle('Food');
        $this->em->persist($food);

        $monkey = new Category2();
        $monkey->setTitle('Monkey');
        $this->em->persist($monkey);

        $this->em->flush();
    }
}
