<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Tree\Issue;

use Doctrine\Common\EventManager;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Tests\Tree\Fixture\Issue2517\Category;
use Gedmo\Tree\TreeListener;

final class Issue2517Test extends BaseTestCaseORM
{
    /**
     * @var TreeListener
     */
    private $listener;

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

        $vegetables = new Category();
        $vegetables->setTitle('Vegetables');
        $vegetables->setParent($food);

        $this->em->persist($food);
        $this->em->persist($fruits);
        $this->em->persist($vegetables);
        $this->em->flush();

        $categoryRepository = $this->em->getRepository(Category::class);

        static::assertTrue($categoryRepository->verify());
        static::assertCount(0, $categoryRepository->getNextSiblings($food));
        static::assertCount(1, $categoryRepository->getNextSiblings($fruits));
        static::assertCount(0, $categoryRepository->getNextSiblings($vegetables));
    }

    protected function getUsedEntityFixtures(): array
    {
        return [Category::class];
    }
}
