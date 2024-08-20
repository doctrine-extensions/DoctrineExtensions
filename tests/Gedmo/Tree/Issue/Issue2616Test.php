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
use Gedmo\Tests\Tree\Fixture\Issue2616\Category;
use Gedmo\Tests\Tree\Fixture\Issue2616\Page;
use Gedmo\Tree\TreeListener;

class Issue2616Test extends BaseTestCaseORM
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

    public function testGetNextSiblingsWithoutIdentifierMethod(): void
    {
        $food = new Category();
        $food->setTitle('Food');

        $fruits = new Category();
        $fruits->setTitle('Fruits');
        $fruits->setParent($food);

        $page1 = new Page();
        $page1->setTitle('Page 1');
        $fruits->setPage($page1);

        $vegetables = new Category();
        $vegetables->setTitle('Vegetables');
        $vegetables->setParent($food);

        $page2 = new Page();
        $page2->setTitle('Page 2');
        $vegetables->setPage($page2);

        $this->em->persist($food);
        $this->em->persist($fruits);
        $this->em->persist($vegetables);
        $this->em->persist($page1);
        $this->em->persist($page2);
        $this->em->flush();

        $this->em->clear();

        $categoryRepo = $this->em->getRepository(Category::class);
        $food = $categoryRepo->findOneBy(['title' => 'Food']);

        $this->em->remove($food);
        $this->em->flush();

        static::assertNull($categoryRepo->findOneBy(['title' => 'Fruits']));
        static::assertNull($categoryRepo->findOneBy(['title' => 'Vegetables']));

        // Page should be removed as well, because children Fruits/Vegetables are removed and they have Page with cascade remove.
        static::assertEmpty($this->em->getRepository(Page::class)->findAll());
    }

    protected function getUsedEntityFixtures(): array
    {
        return [Category::class, Page::class];
    }
}
