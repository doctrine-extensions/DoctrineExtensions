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
use Gedmo\Exception\UnexpectedValueException;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Tests\Tree\Fixture\Closure\Category;
use Gedmo\Tests\Tree\Fixture\Closure\CategoryClosure;
use Gedmo\Tests\Tree\Fixture\Closure\CategoryWithoutLevel;
use Gedmo\Tests\Tree\Fixture\Closure\CategoryWithoutLevelClosure;
use Gedmo\Tests\Tree\Fixture\Closure\News;
use Gedmo\Tests\Tree\Fixture\Closure\Person;
use Gedmo\Tests\Tree\Fixture\Closure\PersonClosure;
use Gedmo\Tests\Tree\Fixture\Closure\User;
use Gedmo\Tree\Strategy\ORM\Closure;
use Gedmo\Tree\TreeListener;

/**
 * These are tests for Tree behavior
 *
 * @author Gustavo Adrian <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class ClosureTreeTest extends BaseTestCaseORM
{
    public const CATEGORY = Category::class;
    public const CLOSURE = CategoryClosure::class;
    public const PERSON = Person::class;
    public const USER = User::class;
    public const PERSON_CLOSURE = PersonClosure::class;
    public const NEWS = News::class;
    public const CATEGORY_WITHOUT_LEVEL = CategoryWithoutLevel::class;
    public const CATEGORY_WITHOUT_LEVEL_CLOSURE = CategoryWithoutLevelClosure::class;

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

    /*public function testHeavyLoad()
    {
        $start = microtime(true);
        $dumpTime = function($start, $msg) {
            $took = microtime(true) - $start;
            $minutes = intval($took / 60); $seconds = $took % 60;
            echo sprintf("%s --> %02d:%02d", $msg, $minutes, $seconds) . PHP_EOL;
        };
        $repo = $this->em->getRepository(self::CATEGORY);
        $parent = null;
        $num = 800;
        for($i = 0; $i < 500; $i++) {
            $cat = new Category;
            $cat->setParent($parent);
            $cat->setTitle('cat'.$i);
            $this->em->persist($cat);
            // siblings
            $rnd = rand(0, 3);
            for ($j = 0; $j < $rnd; $j++) {
                $siblingCat = new Category;
                $siblingCat->setTitle('cat'.$i.$j);
                $siblingCat->setParent($cat);
                $this->em->persist($siblingCat);
            }
            $num += $rnd;
            $parent = $cat;
        }
        $this->em->flush();
        $dumpTime($start, $num.' - inserts took:');
        $start = microtime(true);
        // test moving
        $target = $repo->findOneBy(['title' => 'cat300']);
        $dest = $repo->findOneBy(['title' => 'cat2000']);
        $target->setParent($dest);

        $target2 = $repo->findOneBy(['title' => 'cat450']);
        $dest2 = $repo->findOneBy(['title' => 'cat2500']);
        $target2->setParent($dest2);

        $this->em->flush();
        $dumpTime($start, 'moving took:');
    }*/

    public function testClosureTree(): void
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $closureRepo = $this->em->getRepository(self::CLOSURE);

        $food = $repo->findOneBy(['title' => 'Food']);
        $dql = 'SELECT c FROM '.self::CLOSURE.' c';
        $dql .= ' WHERE c.ancestor = :ancestor';
        $query = $this->em->createQuery($dql);
        $query->setParameter('ancestor', $food);

        $foodClosures = $query->getResult();
        static::assertCount(12, $foodClosures);
        foreach ($foodClosures as $closure) {
            $descendant = $closure->getDescendant();
            if ($descendant === $food) {
                static::assertSame(0, $closure->getDepth());

                continue;
            }
            $descendantTitle = $descendant->getTitle();
            $query->setParameter('ancestor', $descendant);
            $descendantClosures = $query->getResult();
            switch ($descendantTitle) {
                case 'Fruits':
                    static::assertCount(5, $descendantClosures);
                    static::assertSame(1, $closure->getDepth());

                    break;
                case 'Oranges':
                    static::assertCount(1, $descendantClosures);
                    static::assertSame(2, $closure->getDepth());

                    break;
                case 'Berries':
                    static::assertCount(2, $descendantClosures);
                    static::assertSame(2, $closure->getDepth());

                    break;
                case 'Vegitables':
                    static::assertCount(3, $descendantClosures);
                    static::assertSame(1, $closure->getDepth());

                    break;
                case 'Milk':
                    static::assertCount(3, $descendantClosures);
                    static::assertSame(1, $closure->getDepth());

                    break;
                case 'Cheese':
                    static::assertCount(2, $descendantClosures);
                    static::assertSame(2, $closure->getDepth());

                    break;
                case 'Strawberries':
                    static::assertCount(1, $descendantClosures);
                    static::assertSame(3, $closure->getDepth());

                    break;
            }
        }
    }

    public function testUpdateOfParent(): void
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $strawberries = $repo->findOneBy(['title' => 'Strawberries']);
        $cheese = $repo->findOneBy(['title' => 'Cheese']);

        $strawberries->setParent($cheese);
        $this->em->persist($strawberries);
        $this->em->flush();

        $dql = 'SELECT c FROM '.self::CLOSURE.' c';
        $dql .= ' WHERE c.descendant = :descendant';
        $query = $this->em->createQuery($dql);
        $query->setParameter('descendant', $strawberries);

        $closures = $query->getResult();
        static::assertTrue($this->hasAncestor($closures, 'Cheese'));
        static::assertTrue($this->hasAncestor($closures, 'Milk'));
        static::assertTrue($this->hasAncestor($closures, 'Food'));
        static::assertFalse($this->hasAncestor($closures, 'Berries'));
        static::assertFalse($this->hasAncestor($closures, 'Fruits'));
    }

    public function testAnotherUpdateOfParent(): void
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $strawberries = $repo->findOneBy(['title' => 'Strawberries']);

        $strawberries->setParent(null);
        $this->em->persist($strawberries);
        $this->em->flush();

        $dql = 'SELECT c FROM '.self::CLOSURE.' c';
        $dql .= ' WHERE c.descendant = :descendant';
        $query = $this->em->createQuery($dql);
        $query->setParameter('descendant', $strawberries);

        $closures = $query->getResult();
        static::assertCount(1, $closures);
        static::assertTrue($this->hasAncestor($closures, 'Strawberries'));
    }

    public function testBranchRemoval(): void
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $fruits = $repo->findOneBy(['title' => 'Fruits']);

        $id = $fruits->getId();
        $this->em->remove($fruits);
        $this->em->flush();

        $dql = 'SELECT COUNT(c) FROM '.self::CLOSURE.' c';
        $dql .= ' JOIN c.descendant d';
        $dql .= ' JOIN c.ancestor a';
        $dql .= ' WHERE (a.id = :id OR d.id = :id)';
        $query = $this->em->createQuery($dql);
        $query->setParameter('id', $id);

        static::assertSame(0, (int) $query->getSingleScalarResult());
        // pdo_sqlite will not cascade
    }

    public function testSettingParentToChild(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $repo = $this->em->getRepository(self::CATEGORY);
        $fruits = $repo->findOneBy(['title' => 'Fruits']);
        $strawberries = $repo->findOneBy(['title' => 'Strawberries']);

        $fruits->setParent($strawberries);
        $this->em->flush();
    }

    public function testIfEntityHasNotIncludedTreeLevelFieldThenDontProcessIt(): void
    {
        $listener = $this->getMockBuilder(TreeListener::class)->getMock();
        $strategy = $this->getMockBuilder(Closure::class)
            ->setMethods(['setLevelFieldOnPendingNodes'])
            ->setConstructorArgs([$listener])
            ->getMock();

        $listener
            ->method('getStrategy')
            ->willReturn($strategy);

        $strategy->expects(static::never())
            ->method('setLevelFieldOnPendingNodes');

        $evm = $this->em->getEventManager();

        $evm->removeEventListener($this->listener->getSubscribedEvents(), $this->listener);
        $evm->addEventListener($this->listener->getSubscribedEvents(), $this->listener);

        $cat = new CategoryWithoutLevel();
        $cat->setTitle('Test');

        $this->em->persist($cat);
        $this->em->flush();
    }

    public function testCascadePersistTree(): void
    {
        $politics = new Category();
        $politics->setTitle('Politics');

        $news = new News('Lorem ipsum', $politics);
        $this->em->persist($news);
        $this->em->flush();

        $closure = $this->em->createQueryBuilder()
                    ->select('c')
                    ->from(self::CLOSURE, 'c')
                    ->where('c.ancestor = :ancestor')
                    ->setParameter('ancestor', $politics->getId())
                    ->getQuery()
                    ->getResult();

        static::assertCount(1, $closure);
    }

    public function testPersistOnRightEmInstance(): void
    {
        $evm = new EventManager();
        $evm->addEventSubscriber(new TreeListener());

        $emOne = $this->getDefaultMockSqliteEntityManager($evm);
        $emTwo = $this->getDefaultMockSqliteEntityManager($evm);

        $categoryOne = new Category();
        $categoryOne->setTitle('Politics');

        $categoryTwo = new Category();
        $categoryTwo->setTitle('Politics');

        // Persist and Flush on different times !
        $emOne->persist($categoryOne);

        $emTwo->persist($categoryTwo);
        $emTwo->flush();

        $emOne->flush();

        static::assertNotNull($categoryOne->getId());
        static::assertNotNull($categoryTwo->getId());
    }

    /**
     * @dataProvider provideNodeOrders
     */
    public function testClosuresCreatedMustNotBeAffectedByPersistOrder(Category $firstToPersist, Category $secondToPersist, Category $thirdToPersist): void
    {
        $evm = new EventManager();
        $evm->addEventSubscriber($this->listener);

        $this->getDefaultMockSqliteEntityManager($evm);

        $this->em->persist($firstToPersist);
        $this->em->persist($secondToPersist);
        $this->em->persist($thirdToPersist);
        $this->em->flush();
        $this->em->clear();

        $closures = $this->em->getRepository(CategoryClosure::class)->findAll();

        static::assertCount(6, $closures);
    }

    public function provideNodeOrders(): array
    {
        $grandpa = new Category();
        $grandpa->setTitle('grandpa');

        $father = new Category();
        $father->setTitle('father');
        $father->setParent($grandpa);

        $son = new Category();
        $son->setTitle('son');
        $son->setParent($father);

        return [
            'order-123' => [$grandpa, $father, $son],
            'order-132' => [$grandpa, $son, $father],
            'order-213' => [$father, $grandpa, $son],
            'order-231' => [$father, $son, $grandpa],
            'order-312' => [$son, $grandpa, $father],
            'order-321' => [$son, $father, $grandpa],
        ];
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::CATEGORY,
            self::CLOSURE,
            self::PERSON,
            self::PERSON_CLOSURE,
            self::USER,
            self::NEWS,
            self::CATEGORY_WITHOUT_LEVEL,
            self::CATEGORY_WITHOUT_LEVEL_CLOSURE,
        ];
    }

    private function hasAncestor(iterable $closures, string $name): bool
    {
        foreach ($closures as $closure) {
            $ancestor = $closure->getAncestor();
            if ($ancestor->getTitle() === $name) {
                return true;
            }
        }

        return false;
    }

    private function populate(): void
    {
        $food = new Category();
        $food->setTitle('Food');
        $this->em->persist($food);

        $fruits = new Category();
        $fruits->setTitle('Fruits');
        $fruits->setParent($food);
        $this->em->persist($fruits);

        $oranges = new Category();
        $oranges->setTitle('Oranges');
        $oranges->setParent($fruits);
        $this->em->persist($oranges);

        $lemons = new Category();
        $lemons->setTitle('Lemons');
        $lemons->setParent($fruits);
        $this->em->persist($lemons);

        $berries = new Category();
        $berries->setTitle('Berries');
        $berries->setParent($fruits);
        $this->em->persist($berries);

        $strawberries = new Category();
        $strawberries->setTitle('Strawberries');
        $strawberries->setParent($berries);
        $this->em->persist($strawberries);

        $vegitables = new Category();
        $vegitables->setTitle('Vegitables');
        $vegitables->setParent($food);
        $this->em->persist($vegitables);

        $cabbages = new Category();
        $cabbages->setTitle('Cabbages');
        $cabbages->setParent($vegitables);
        $this->em->persist($cabbages);

        $carrots = new Category();
        $carrots->setTitle('Carrots');
        $carrots->setParent($vegitables);
        $this->em->persist($carrots);

        $milk = new Category();
        $milk->setTitle('Milk');
        $milk->setParent($food);
        $this->em->persist($milk);

        $cheese = new Category();
        $cheese->setTitle('Cheese');
        $cheese->setParent($milk);
        $this->em->persist($cheese);

        $mouldCheese = new Category();
        $mouldCheese->setTitle('Mould cheese');
        $mouldCheese->setParent($cheese);
        $this->em->persist($mouldCheese);

        $this->em->flush();
    }
}
