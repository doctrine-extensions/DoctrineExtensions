<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sluggable\Handlers;

use Doctrine\Common\EventManager;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Tests\Sluggable\Fixture\Handler\TreeSlug;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Tree\TreeListener;

/**
 * These are tests for Sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class TreeSlugHandlerTest extends BaseTestCaseORM
{
    public const TARGET = TreeSlug::class;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());
        $evm->addEventSubscriber(new TreeListener());

        $this->getDefaultMockSqliteEntityManager($evm);
    }

    public function testSlugGeneration(): void
    {
        $this->populate();
        $repo = $this->em->getRepository(self::TARGET);

        $food = $repo->findOneBy(['title' => 'Food']);
        static::assertSame('food', $food->getSlug());

        $fruits = $repo->findOneBy(['title' => 'Fruits']);
        static::assertSame('food/fruits', $fruits->getSlug());

        $oranges = $repo->findOneBy(['title' => 'Oranges']);
        static::assertSame('food/fruits/oranges', $oranges->getSlug());

        $citrons = $repo->findOneBy(['title' => 'Citrons']);
        static::assertSame('food/fruits/citrons', $citrons->getSlug());

        $apple = $repo->findOneBy(['title' => 'Apple']);
        static::assertSame('food/fruits/apple', $apple->getSlug());

        $kiwi = $repo->findOneBy(['title' => 'Kiwi']);
        static::assertSame('food/fruits/kiwi', $kiwi->getSlug());

        $banana = $repo->findOneBy(['title' => 'Banana']);
        static::assertSame('food/fruits/banana', $banana->getSlug());
    }

    public function testSlugUpdates(): void
    {
        $this->populate();
        $repo = $this->em->getRepository(self::TARGET);

        $fruits = $repo->findOneBy(['title' => 'Fruits']);
        $fruits->setTitle('Fructis');

        $this->em->persist($fruits);
        $this->em->flush();

        static::assertSame('food/fructis', $fruits->getSlug());

        $oranges = $repo->findOneBy(['title' => 'Oranges']);
        static::assertSame('food/fructis/oranges', $oranges->getSlug());

        $citrons = $repo->findOneBy(['title' => 'Citrons']);
        static::assertSame('food/fructis/citrons', $citrons->getSlug());

        $food = $repo->findOneBy(['title' => 'Food']);
        $food->setTitle('Foodissimo');

        $this->em->persist($food);
        $this->em->flush();

        static::assertSame('foodissimo', $food->getSlug());
        static::assertSame('foodissimo/fructis/oranges', $oranges->getSlug());
        static::assertSame('foodissimo/fructis/citrons', $citrons->getSlug());
    }

    public function testMoreSlugUpdates(): void
    {
        $this->populate();
        $repo = $this->em->getRepository(self::TARGET);

        $fruits = $repo->findOneBy(['title' => 'Fruits']);
        $fruits->setTitle('Fructis');
        $milk = $repo->findOneBy(['title' => 'Milk']);
        $repo->persistAsFirstChildOf($fruits, $milk);
        $this->em->flush();

        static::assertSame('food/milk/fructis', $fruits->getSlug());

        $oranges = $repo->findOneBy(['title' => 'Oranges']);
        static::assertSame('food/milk/fructis/oranges', $oranges->getSlug());

        $citrons = $repo->findOneBy(['title' => 'Citrons']);
        static::assertSame('food/milk/fructis/citrons', $citrons->getSlug());

        $food = $repo->findOneBy(['title' => 'Food']);
        $food->setTitle('Foodissimo');

        $this->em->persist($food);
        $this->em->flush();

        static::assertSame('foodissimo', $food->getSlug());
        static::assertSame('foodissimo/milk/fructis/oranges', $oranges->getSlug());
        static::assertSame('foodissimo/milk/fructis/citrons', $citrons->getSlug());

        $repo->persistAsFirstChildOf($fruits, $food);
        $this->em->flush();

        static::assertSame('foodissimo/fructis/oranges', $oranges->getSlug());
        static::assertSame('foodissimo/fructis/citrons', $citrons->getSlug());
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::TARGET,
        ];
    }

    private function populate(): void
    {
        $repo = $this->em->getRepository(self::TARGET);

        $food = new TreeSlug();
        $food->setTitle('Food');

        $fruits = new TreeSlug();
        $fruits->setTitle('Fruits');

        $vegitables = new TreeSlug();
        $vegitables->setTitle('Vegitables');

        $milk = new TreeSlug();
        $milk->setTitle('Milk');

        $meat = new TreeSlug();
        $meat->setTitle('Meat');

        $oranges = new TreeSlug();
        $oranges->setTitle('Oranges');

        $citrons = new TreeSlug();
        $citrons->setTitle('Citrons');

        $apple = new TreeSlug();
        $apple->setTitle('Apple');

        $kiwi = new TreeSlug();
        $kiwi->setTitle('Kiwi');

        $banana = new TreeSlug();
        $banana->setTitle('Banana');

        $repo
            ->persistAsFirstChild($food)
            ->persistAsFirstChildOf($fruits, $food)
            ->persistAsFirstChildOf($vegitables, $food)
            ->persistAsLastChildOf($milk, $food)
            ->persistAsLastChildOf($meat, $food)
            ->persistAsFirstChildOf($oranges, $fruits)
            ->persistAsFirstChildOf($citrons, $fruits)
            ->persistAsFirstChildOf($apple, $fruits)
            ->persistAsPrevSiblingOf($kiwi, $apple)
            ->persistAsNextSiblingOf($banana, $apple);

        $this->em->flush();
    }
}
