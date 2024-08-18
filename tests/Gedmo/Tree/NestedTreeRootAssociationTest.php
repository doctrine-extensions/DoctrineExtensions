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
use Gedmo\Tests\Tree\Fixture\RootAssociationCategory;
use Gedmo\Tree\TreeListener;

/**
 * These are tests for Tree behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class NestedTreeRootAssociationTest extends BaseTestCaseORM
{
    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new TreeListener());

        $this->getDefaultMockSqliteEntityManager($evm);
        $this->populate();
    }

    public function testRootEntity(): void
    {
        $repo = $this->em->getRepository(RootAssociationCategory::class);

        // Foods
        $food = $repo->findOneBy(['title' => 'Food']);
        static::assertSame($food->getId(), $food->getRoot()->getId());

        $fruits = $repo->findOneBy(['title' => 'Fruits']);
        static::assertSame($food->getId(), $fruits->getRoot()->getId());

        $vegetables = $repo->findOneBy(['title' => 'Vegetables']);
        static::assertSame($food->getId(), $vegetables->getRoot()->getId());

        $carrots = $repo->findOneBy(['title' => 'Carrots']);
        static::assertSame($food->getId(), $carrots->getRoot()->getId());

        $potatoes = $repo->findOneBy(['title' => 'Potatoes']);
        static::assertSame($food->getId(), $potatoes->getRoot()->getId());

        // Sports
        $sports = $repo->findOneBy(['title' => 'Sports']);
        static::assertSame($sports->getId(), $sports->getRoot()->getId());
    }

    public function testRemoveParentForNode(): void
    {
        $repo = $this->em->getRepository(RootAssociationCategory::class);

        /** @var RootAssociationCategory $food */
        $food = $repo->findOneBy(['title' => 'Food']);
        static::assertSame($food->getId(), $food->getRoot()->getId());
        static::assertSame(0, $food->getLevel());
        static::assertSame(1, $food->getLeft());
        static::assertSame(10, $food->getRight());

        /** @var RootAssociationCategory $fruits */
        $fruits = $repo->findOneBy(['title' => 'Fruits']);
        static::assertSame($food->getId(), $fruits->getRoot()->getId());
        static::assertSame(1, $fruits->getLevel());
        static::assertSame(2, $fruits->getLeft());
        static::assertSame(3, $fruits->getRight());

        // Remove node's parent, which should move out the node into a new tree
        $fruits->setParent(null);
        $this->em->flush();

        $food = $repo->findOneBy(['title' => 'Food']);
        static::assertSame($food->getId(), $food->getRoot()->getId());
        static::assertSame(0, $food->getLevel());
        static::assertSame(1, $food->getLeft());
        static::assertSame(8, $food->getRight());

        $fruits = $repo->findOneBy(['title' => 'Fruits']);
        static::assertSame($fruits->getId(), $fruits->getRoot()->getId());
        static::assertSame(0, $fruits->getLevel());
        static::assertSame(1, $fruits->getLeft());
        static::assertSame(2, $fruits->getRight());
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            RootAssociationCategory::class,
        ];
    }

    private function populate(): void
    {
        $root = new RootAssociationCategory();
        $root->setTitle('Food');

        $root2 = new RootAssociationCategory();
        $root2->setTitle('Sports');

        $child = new RootAssociationCategory();
        $child->setTitle('Fruits');
        $child->setParent($root);

        $child2 = new RootAssociationCategory();
        $child2->setTitle('Vegetables');
        $child2->setParent($root);

        $childsChild = new RootAssociationCategory();
        $childsChild->setTitle('Carrots');
        $childsChild->setParent($child2);

        $potatoes = new RootAssociationCategory();
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
