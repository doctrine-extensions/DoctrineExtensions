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
use Gedmo\Tests\Tree\Fixture\ForeignRootCategory;
use Gedmo\Tests\Tree\Fixture\RootCategory;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use Gedmo\Tree\TreeListener;

/**
 * These are tests for Tree behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class NestedTreeRootTest extends BaseTestCaseORM
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

    public function testShouldRemoveAndSynchronize(): void
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $vegies = $repo->findOneBy(['title' => 'Vegitables']);

        $this->em->remove($vegies);
        $this->em->flush();

        $food = $repo->findOneBy(['title' => 'Food']);

        static::assertSame(1, $food->getLeft());
        static::assertSame(4, $food->getRight());

        $vegies = new RootCategory();
        $vegies->setTitle('Vegies');
        $repo->persistAsFirstChildOf($vegies, $food);

        $this->em->flush();
        static::assertSame(1, $food->getLeft());
        static::assertSame(6, $food->getRight());

        static::assertSame(2, $vegies->getLeft());
        static::assertSame(3, $vegies->getRight());
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
            $cat = new RootCategory;
            $cat->setParent($parent);
            $cat->setTitle('cat'.$i);
            $this->em->persist($cat);
            // siblings
            $rnd = rand(0, 3);
            for ($j = 0; $j < $rnd; $j++) {
                $siblingCat = new RootCategory;
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

    public function testTheTree(): void
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $node = $repo->findOneBy(['title' => 'Food']);

        static::assertSame(1, $node->getRoot());
        static::assertSame(1, $node->getLeft());
        static::assertSame(0, $node->getLevel());
        static::assertSame(10, $node->getRight());

        $node = $repo->findOneBy(['title' => 'Sports']);

        static::assertSame(2, $node->getRoot());
        static::assertSame(1, $node->getLeft());
        static::assertSame(0, $node->getLevel());
        static::assertSame(2, $node->getRight());

        $node = $repo->findOneBy(['title' => 'Fruits']);

        static::assertSame(1, $node->getRoot());
        static::assertSame(2, $node->getLeft());
        static::assertSame(1, $node->getLevel());
        static::assertSame(3, $node->getRight());

        $node = $repo->findOneBy(['title' => 'Vegitables']);

        static::assertSame(1, $node->getRoot());
        static::assertSame(4, $node->getLeft());
        static::assertSame(1, $node->getLevel());
        static::assertSame(9, $node->getRight());

        $node = $repo->findOneBy(['title' => 'Carrots']);

        static::assertSame(1, $node->getRoot());
        static::assertSame(5, $node->getLeft());
        static::assertSame(2, $node->getLevel());
        static::assertSame(6, $node->getRight());

        $node = $repo->findOneBy(['title' => 'Potatoes']);

        static::assertSame(1, $node->getRoot());
        static::assertSame(7, $node->getLeft());
        static::assertSame(2, $node->getLevel());
        static::assertSame(8, $node->getRight());
    }

    public function testSetParentToNull(): void
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $node = $repo->findOneBy(['title' => 'Vegitables']);
        $node->setParent(null);

        $this->em->persist($node);
        $this->em->flush();
        $this->em->clear();

        $node = $repo->findOneBy(['title' => 'Vegitables']);
        static::assertSame(4, $node->getRoot());
        static::assertSame(1, $node->getLeft());
        static::assertSame(6, $node->getRight());
        static::assertSame(0, $node->getLevel());
    }

    public function testTreeUpdateShiftToNextBranch(): void
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $sport = $repo->findOneBy(['title' => 'Sports']);
        $food = $repo->findOneBy(['title' => 'Food']);

        $sport->setParent($food);
        $this->em->persist($sport);
        $this->em->flush();
        $this->em->clear();

        $node = $repo->findOneBy(['title' => 'Food']);

        static::assertSame(1, $node->getLeft());
        static::assertSame(12, $node->getRight());

        $node = $repo->findOneBy(['title' => 'Sports']);

        static::assertSame(1, $node->getRoot());
        static::assertSame(2, $node->getLeft());
        static::assertSame(1, $node->getLevel());
        static::assertSame(3, $node->getRight());

        $node = $repo->findOneBy(['title' => 'Vegitables']);

        static::assertSame(6, $node->getLeft());
        static::assertSame(11, $node->getRight());
    }

    public function testTreeUpdateShiftToRoot(): void
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $vegies = $repo->findOneBy(['title' => 'Vegitables']);

        $vegies->setParent(null);
        $this->em->persist($vegies);
        $this->em->flush();
        $this->em->clear();

        $node = $repo->findOneBy(['title' => 'Food']);

        static::assertSame(1, $node->getLeft());
        static::assertSame(4, $node->getRight());

        $node = $repo->findOneBy(['title' => 'Vegitables']);

        static::assertSame(4, $node->getRoot());
        static::assertSame(1, $node->getLeft());
        static::assertSame(0, $node->getLevel());
        static::assertSame(6, $node->getRight());

        $node = $repo->findOneBy(['title' => 'Potatoes']);

        static::assertSame(4, $node->getRoot());
        static::assertSame(4, $node->getLeft());
        static::assertSame(1, $node->getLevel());
        static::assertSame(5, $node->getRight());
    }

    public function testTreeUpdateShiftToOtherParent(): void
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $carrots = $repo->findOneBy(['title' => 'Carrots']);
        $food = $repo->findOneBy(['title' => 'Food']);

        $carrots->setParent($food);
        $this->em->persist($carrots);
        $this->em->flush();
        $this->em->clear();

        $node = $repo->findOneBy(['title' => 'Food']);

        static::assertSame(1, $node->getLeft());
        static::assertSame(10, $node->getRight());

        $node = $repo->findOneBy(['title' => 'Carrots']);

        static::assertSame(1, $node->getRoot());
        static::assertSame(2, $node->getLeft());
        static::assertSame(1, $node->getLevel());
        static::assertSame(3, $node->getRight());

        $node = $repo->findOneBy(['title' => 'Potatoes']);

        static::assertSame(1, $node->getRoot());
        static::assertSame(7, $node->getLeft());
        static::assertSame(2, $node->getLevel());
        static::assertSame(8, $node->getRight());
    }

    public function testTreeUpdateShiftToChildParent(): void
    {
        $this->expectException('UnexpectedValueException');
        $repo = $this->em->getRepository(self::CATEGORY);
        $vegies = $repo->findOneBy(['title' => 'Vegitables']);
        $food = $repo->findOneBy(['title' => 'Food']);

        $food->setParent($vegies);
        $this->em->persist($food);
        $this->em->flush();
        $this->em->clear();
    }

    public function testTwoUpdateOperations(): void
    {
        $repo = $this->em->getRepository(self::CATEGORY);

        $sport = $repo->findOneBy(['title' => 'Sports']);
        $food = $repo->findOneBy(['title' => 'Food']);
        $sport->setParent($food);

        $vegies = $repo->findOneBy(['title' => 'Vegitables']);
        $vegies->setParent(null);

        $this->em->persist($vegies);
        $this->em->persist($sport);
        $this->em->flush();
        $this->em->clear();

        $node = $repo->findOneBy(['title' => 'Carrots']);

        static::assertSame(4, $node->getRoot());
        static::assertSame(2, $node->getLeft());
        static::assertSame(1, $node->getLevel());
        static::assertSame(3, $node->getRight());

        $node = $repo->findOneBy(['title' => 'Vegitables']);

        static::assertSame(4, $node->getRoot());
        static::assertSame(1, $node->getLeft());
        static::assertSame(0, $node->getLevel());
        static::assertSame(6, $node->getRight());

        $node = $repo->findOneBy(['title' => 'Sports']);

        static::assertSame(1, $node->getRoot());
        static::assertSame(2, $node->getLeft());
        static::assertSame(1, $node->getLevel());
        static::assertSame(3, $node->getRight());
    }

    public function testRemoval(): void
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $vegies = $repo->findOneBy(['title' => 'Vegitables']);

        $this->em->remove($vegies);
        $this->em->flush();
        $this->em->clear();

        $node = $repo->findOneBy(['title' => 'Food']);

        static::assertSame(1, $node->getLeft());
        static::assertSame(4, $node->getRight());
    }

    /**
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function testTreeWithRootPointingAtAnotherTable(): void
    {
        // depopulate, i don't want the other stuff in db
        /** @var NestedTreeRepository $repo */
        $repo = $this->em->getRepository(ForeignRootCategory::class);
        $all = $repo->findAll();
        foreach ($all as $one) {
            $this->em->remove($one);
        }
        $this->em->flush();

        $fiction = new ForeignRootCategory();
        $fiction->setTitle('Fiction Books');
        $fiction->setRoot(1);  // Lets pretend this points to another table, and root id 1 is "Books"

        $fact = new ForeignRootCategory();
        $fact->setTitle('Fact Books');
        $fact->setRoot(1);

        $action = new ForeignRootCategory();
        $action->setTitle('Action');
        $action->setRoot(2); // Lets pretend this points to another table, and root id 2 is "Movies"

        $comedy = new ForeignRootCategory();
        $comedy->setTitle('Comedy');
        $comedy->setRoot(2);

        $horror = new ForeignRootCategory();
        $horror->setTitle('Horror');
        $horror->setRoot(2);

        // Child categories now
        $lotr = new ForeignRootCategory();
        $lotr->setTitle('Lord of the Rings');
        $lotr->setParent($fiction);
        $lotr->setRoot(1);

        $warlock = new ForeignRootCategory();
        $warlock->setTitle('The Warlock of Firetop Mountain');
        $warlock->setParent($fiction);
        $warlock->setRoot(1);

        $php = new ForeignRootCategory();
        $php->setTitle('PHP open source development');
        $php->setParent($fact);
        $php->setRoot(1);

        $dracula = new ForeignRootCategory();
        $dracula->setTitle('Hammer Horror Dracula');
        $dracula->setParent($horror);
        $dracula->setRoot(2);

        $frankenstein = new ForeignRootCategory();
        $frankenstein->setTitle('Hammer Horror Frankenstein');
        $frankenstein->setParent($horror);
        $frankenstein->setRoot(2);

        $this->em->persist($fact);
        $this->em->persist($fiction);
        $this->em->persist($comedy);
        $this->em->persist($horror);
        $this->em->persist($action);
        $this->em->persist($lotr);
        $this->em->persist($warlock);
        $this->em->persist($php);
        $this->em->persist($dracula);
        $this->em->persist($frankenstein);
        $this->em->flush();

        static::assertSame(1, $fact->getLeft());
        static::assertSame(4, $fact->getRight());
        static::assertSame(0, $fact->getLevel());
        static::assertSame(1, $fact->getRoot());
        static::assertNull($fact->getParent());

        static::assertSame(5, $fiction->getLeft());
        static::assertSame(10, $fiction->getRight());
        static::assertSame(0, $fiction->getLevel());
        static::assertSame(1, $fiction->getRoot());
        static::assertNull($fiction->getParent());

        static::assertSame(6, $lotr->getLeft());
        static::assertSame(7, $lotr->getRight());
        static::assertSame(1, $lotr->getLevel());
        static::assertSame(1, $lotr->getRoot());
        static::assertSame($fiction, $lotr->getParent());

        static::assertSame(8, $warlock->getLeft());
        static::assertSame(9, $warlock->getRight());
        static::assertSame(1, $warlock->getLevel());
        static::assertSame(1, $warlock->getRoot());
        static::assertSame($fiction, $warlock->getParent());

        static::assertSame(2, $php->getLeft());
        static::assertSame(3, $php->getRight());
        static::assertSame(1, $php->getLevel());
        static::assertSame(1, $php->getRoot());
        static::assertSame($fact, $php->getParent());

        static::assertSame(1, $comedy->getLeft());
        static::assertSame(2, $comedy->getRight());
        static::assertSame(0, $comedy->getLevel());
        static::assertSame(2, $comedy->getRoot());
        static::assertNull($comedy->getParent());

        static::assertSame(3, $horror->getLeft());
        static::assertSame(8, $horror->getRight());
        static::assertSame(0, $horror->getLevel());
        static::assertSame(2, $horror->getRoot());
        static::assertNull($horror->getParent());

        static::assertSame(9, $action->getLeft());
        static::assertSame(10, $action->getRight());
        static::assertSame(0, $action->getLevel());
        static::assertSame(2, $action->getRoot());
        static::assertNull($action->getParent());

        static::assertSame(4, $dracula->getLeft());
        static::assertSame(5, $dracula->getRight());
        static::assertSame(1, $dracula->getLevel());
        static::assertSame(2, $dracula->getRoot());
        static::assertSame($horror, $dracula->getParent());

        static::assertSame(6, $frankenstein->getLeft());
        static::assertSame(7, $frankenstein->getRight());
        static::assertSame(1, $frankenstein->getLevel());
        static::assertSame(2, $frankenstein->getRoot());
        static::assertSame($horror, $frankenstein->getParent());

        // Now move the action movie category up
        $repo->moveUp($action);

        static::assertSame(1, $comedy->getLeft());
        static::assertSame(2, $comedy->getRight());
        static::assertSame(0, $comedy->getLevel());
        static::assertSame(2, $comedy->getRoot());
        static::assertNull($comedy->getParent());

        static::assertSame(3, $action->getLeft());
        static::assertSame(4, $action->getRight());
        static::assertSame(0, $action->getLevel());
        static::assertSame(2, $action->getRoot());
        static::assertNull($action->getParent());

        static::assertSame(5, $horror->getLeft());
        static::assertSame(10, $horror->getRight());
        static::assertSame(0, $horror->getLevel());
        static::assertSame(2, $horror->getRoot());
        static::assertNull($horror->getParent());

        static::assertSame(6, $dracula->getLeft());
        static::assertSame(7, $dracula->getRight());
        static::assertSame(1, $dracula->getLevel());
        static::assertSame(2, $dracula->getRoot());
        static::assertSame($horror, $dracula->getParent());

        static::assertSame(8, $frankenstein->getLeft());
        static::assertSame(9, $frankenstein->getRight());
        static::assertSame(1, $frankenstein->getLevel());
        static::assertSame(2, $frankenstein->getRoot());
        static::assertSame($horror, $frankenstein->getParent());

        $this->em->clear();
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::CATEGORY,
            ForeignRootCategory::class,
        ];
    }

    /**
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function populate(): void
    {
        $root = new RootCategory();
        $root->setTitle('Food');

        $root2 = new RootCategory();
        $root2->setTitle('Sports');

        $child = new RootCategory();
        $child->setTitle('Fruits');
        $child->setParent($root);

        $child2 = new RootCategory();
        $child2->setTitle('Vegitables');
        $child2->setParent($root);

        $childsChild = new RootCategory();
        $childsChild->setTitle('Carrots');
        $childsChild->setParent($child2);

        $potatoes = new RootCategory();
        $potatoes->setTitle('Potatoes');
        $potatoes->setParent($child2);

        $this->em->persist($root);
        $this->em->persist($root2);
        $this->em->persist($child);
        $this->em->persist($child2);
        $this->em->persist($childsChild);
        $this->em->persist($potatoes);
        $this->em->flush();
        $this->em->clear();
    }
}
