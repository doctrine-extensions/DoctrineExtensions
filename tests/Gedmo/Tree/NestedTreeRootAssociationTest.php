<?php

namespace Gedmo\Tests\Tree;

use Doctrine\Common\EventManager;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Tests\Tree\Fixture\RootAssociationCategory;
use Gedmo\Tree\TreeListener;

/**
 * These are tests for Tree behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class NestedTreeRootAssociationTest extends BaseTestCaseORM
{
    public const CATEGORY = RootAssociationCategory::class;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new TreeListener());

        $this->getMockSqliteEntityManager($evm);
        $this->populate();
    }

    public function testRootEntity()
    {
        $repo = $this->em->getRepository(self::CATEGORY);

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

    protected function getUsedEntityFixtures()
    {
        return [
            self::CATEGORY,
        ];
    }

    private function populate()
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
