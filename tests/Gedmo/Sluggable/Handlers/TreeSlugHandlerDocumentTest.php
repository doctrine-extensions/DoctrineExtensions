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
use Gedmo\Tests\Sluggable\Fixture\Document\Handler\TreeSlug;
use Gedmo\Tests\Tool\BaseTestCaseMongoODM;

/**
 * These are tests for sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class TreeSlugHandlerDocumentTest extends BaseTestCaseMongoODM
{
    public const SLUG = TreeSlug::class;

    protected function setUp(): void
    {
        parent::setUp();
        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());

        $this->getMockDocumentManager($evm);
    }

    public function testSlugGeneration(): void
    {
        $this->populate();
        $repo = $this->dm->getRepository(self::SLUG);

        $food = $repo->findOneBy(['title' => 'Food']);
        static::assertSame('food', $food->getSlug());

        $fruits = $repo->findOneBy(['title' => 'Fruits']);
        static::assertSame('food/fruits', $fruits->getSlug());

        $oranges = $repo->findOneBy(['title' => 'Oranges']);
        static::assertSame('food/fruits/oranges', $oranges->getSlug());

        $citrons = $repo->findOneBy(['title' => 'Citrons']);
        static::assertSame('food/fruits/citrons', $citrons->getSlug());
    }

    public function testSlugUpdates(): void
    {
        $this->populate();
        $repo = $this->dm->getRepository(self::SLUG);

        $fruits = $repo->findOneBy(['title' => 'Fruits']);
        $fruits->setTitle('Fructis');

        $this->dm->persist($fruits);
        $this->dm->flush();

        static::assertSame('food/fructis', $fruits->getSlug());

        $oranges = $repo->findOneBy(['title' => 'Oranges']);
        static::assertSame('food/fructis/oranges', $oranges->getSlug());

        $citrons = $repo->findOneBy(['title' => 'Citrons']);
        static::assertSame('food/fructis/citrons', $citrons->getSlug());

        $food = $repo->findOneBy(['title' => 'Food']);
        $food->setTitle('Foodissimo');

        $this->dm->persist($food);
        $this->dm->flush();

        static::assertSame('foodissimo', $food->getSlug());
        static::assertSame('foodissimo/fructis/oranges', $oranges->getSlug());
        static::assertSame('foodissimo/fructis/citrons', $citrons->getSlug());
    }

    private function populate(): void
    {
        $food = new TreeSlug();
        $food->setTitle('Food');
        $this->dm->persist($food);

        $fruits = new TreeSlug();
        $fruits->setTitle('Fruits');
        $fruits->setParent($food);
        $this->dm->persist($fruits);

        $vegitables = new TreeSlug();
        $vegitables->setTitle('Vegitables');
        $vegitables->setParent($food);
        $this->dm->persist($vegitables);

        $milk = new TreeSlug();
        $milk->setTitle('Milk');
        $milk->setParent($food);
        $this->dm->persist($milk);

        $meat = new TreeSlug();
        $meat->setTitle('Meat');
        $meat->setParent($food);
        $this->dm->persist($meat);

        $oranges = new TreeSlug();
        $oranges->setTitle('Oranges');
        $oranges->setParent($fruits);
        $this->dm->persist($oranges);

        $citrons = new TreeSlug();
        $citrons->setTitle('Citrons');
        $citrons->setParent($fruits);
        $this->dm->persist($citrons);

        $this->dm->flush();
    }
}
