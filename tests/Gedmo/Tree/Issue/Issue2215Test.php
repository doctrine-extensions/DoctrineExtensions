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
use Gedmo\Tests\Tree\Fixture\Issue2215\Category;
use Gedmo\Tree\TreeListener;

final class Issue2215Test extends BaseTestCaseORM
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

    public function testSettingParentNullWithTreeRootIdentifierMethod(): void
    {
        $food = new Category();
        $food->setTitle('Food');

        $fruits = new Category();
        $fruits->setTitle('Fruits');
        $fruits->setParent($food);

        $this->em->persist($food);
        $this->em->persist($fruits);
        $this->em->flush();

        $categoryRepository = $this->em->getRepository(Category::class);
        $verify = $categoryRepository->verify();

        static::assertTrue($verify);

        static::assertNull($food->getParent());
        static::assertSame($food, $food->getRoot());

        static::assertSame($food, $fruits->getParent());
        static::assertSame($food, $fruits->getRoot());
    }

    protected function getUsedEntityFixtures(): array
    {
        return [Category::class];
    }
}
