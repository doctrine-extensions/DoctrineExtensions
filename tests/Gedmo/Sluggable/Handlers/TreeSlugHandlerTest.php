<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Sluggable\Fixture\Handler\TreeSlug;
use Gedmo\Tree\TreeListener;

/**
 * These are tests for Sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Sluggable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TreeSlugHandlerTest extends BaseTestCaseORM
{
    const TARGET = "Sluggable\\Fixture\\Handler\\TreeSlug";

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $evm->addEventSubscriber(new SluggableListener);
        $evm->addEventSubscriber(new TreeListener);

        $this->getMockSqliteEntityManager($evm);
    }

    public function testSlugGeneration()
    {
        $this->populate();
        $repo = $this->em->getRepository(self::TARGET);

        $food = $repo->findOneByTitle('Food');
        $this->assertEquals('food', $food->getSlug());

        $fruits = $repo->findOneByTitle('Fruits');
        $this->assertEquals('food/fruits', $fruits->getSlug());

        $oranges = $repo->findOneByTitle('Oranges');
        $this->assertEquals('food/fruits/oranges', $oranges->getSlug());

        $citrons = $repo->findOneByTitle('Citrons');
        $this->assertEquals('food/fruits/citrons', $citrons->getSlug());
    }

    public function testSlugUpdates()
    {
        $this->populate();
        $repo = $this->em->getRepository(self::TARGET);

        $fruits = $repo->findOneByTitle('Fruits');
        $fruits->setTitle('Fructis');

        $this->em->persist($fruits);
        $this->em->flush();

        $this->assertEquals('food/fructis', $fruits->getSlug());

        $oranges = $repo->findOneByTitle('Oranges');
        $this->assertEquals('food/fructis/oranges', $oranges->getSlug());

        $citrons = $repo->findOneByTitle('Citrons');
        $this->assertEquals('food/fructis/citrons', $citrons->getSlug());

        $food = $repo->findOneByTitle('Food');
        $food->setTitle('Foodissimo');

        $this->em->persist($food);
        $this->em->flush();

        $this->assertEquals('foodissimo', $food->getSlug());
        $this->assertEquals('foodissimo/fructis/oranges', $oranges->getSlug());
        $this->assertEquals('foodissimo/fructis/citrons', $citrons->getSlug());
    }

    public function testMoreSlugUpdates()
    {
        $this->populate();
        $repo = $this->em->getRepository(self::TARGET);

        $fruits = $repo->findOneByTitle('Fruits');
        $fruits->setTitle('Fructis');
        $milk = $repo->findOneByTitle('Milk');
        $repo->persistAsFirstChildOf($fruits, $milk);
        $this->em->flush();

        $this->assertEquals('food/milk/fructis', $fruits->getSlug());

        $oranges = $repo->findOneByTitle('Oranges');
        $this->assertEquals('food/milk/fructis/oranges', $oranges->getSlug());

        $citrons = $repo->findOneByTitle('Citrons');
        $this->assertEquals('food/milk/fructis/citrons', $citrons->getSlug());

        $food = $repo->findOneByTitle('Food');
        $food->setTitle('Foodissimo');

        $this->em->persist($food);
        $this->em->flush();

        $this->assertEquals('foodissimo', $food->getSlug());
        $this->assertEquals('foodissimo/milk/fructis/oranges', $oranges->getSlug());
        $this->assertEquals('foodissimo/milk/fructis/citrons', $citrons->getSlug());

        $repo->persistAsFirstChildOf($fruits, $food);
        $this->em->flush();

        $this->assertEquals('foodissimo/fructis/oranges', $oranges->getSlug());
        $this->assertEquals('foodissimo/fructis/citrons', $citrons->getSlug());
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::TARGET
        );
    }

    private function populate()
    {
        $repo = $this->em->getRepository(self::TARGET);

        $food = new TreeSlug;
        $food->setTitle('Food');

        $fruits = new TreeSlug;
        $fruits->setTitle('Fruits');

        $vegitables = new TreeSlug;
        $vegitables->setTitle('Vegitables');

        $milk = new TreeSlug;
        $milk->setTitle('Milk');

        $meat = new TreeSlug;
        $meat->setTitle('Meat');

        $oranges = new TreeSlug;
        $oranges->setTitle('Oranges');

        $citrons = new TreeSlug;
        $citrons->setTitle('Citrons');

        $repo
            ->persistAsFirstChild($food)
            ->persistAsFirstChildOf($fruits, $food)
            ->persistAsFirstChildOf($vegitables, $food)
            ->persistAsLastChildOf($milk, $food)
            ->persistAsLastChildOf($meat, $food)
            ->persistAsFirstChildOf($oranges, $fruits)
            ->persistAsFirstChildOf($citrons, $fruits);

        $this->em->flush();
    }
}
