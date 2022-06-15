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
use Gedmo\Tests\Tree\Fixture\Issue2408\Category;
use Gedmo\Tree\TreeListener;

final class Issue2408Test extends BaseTestCaseORM
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

    public function testSettingParentNull(): void
    {
        $food = new Category();
        $food->setTitle('Food');

        $fruits = new Category();
        $fruits->setTitle('Fruits');
        $fruits->setParent($food);

        $vegetables = new Category();
        $vegetables->setTitle('Vegetables');
        $vegetables->setParent($food);

        $carrots = new Category();
        $carrots->setTitle('Carrots');
        $carrots->setParent($vegetables);

        $this->em->persist($food);
        $this->em->persist($fruits);
        $this->em->persist($vegetables);
        $this->em->persist($carrots);
        $this->em->flush();

        $this->em->refresh($carrots);

        $carrots->setParent(null);
        $this->em->flush();

        $categoryRepository = $this->em->getRepository(Category::class);
        $verify = $categoryRepository->verify();

        static::assertTrue($verify);
        static::assertSame($carrots, $carrots->getRoot());
    }

    protected function getUsedEntityFixtures(): array
    {
        return [Category::class];
    }
}
