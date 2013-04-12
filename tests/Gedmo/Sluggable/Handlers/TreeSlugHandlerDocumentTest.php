<?php

namespace Gedmo\Sluggable;

use Tool\BaseTestCaseMongoODM;
use Doctrine\Common\EventManager;
use Sluggable\Fixture\Document\Handler\TreeSlug;

/**
 * These are tests for sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TreeSlugHandlerDocumentTest extends BaseTestCaseMongoODM
{
    const SLUG = 'Sluggable\\Fixture\\Document\\Handler\\TreeSlug';

    protected function setUp()
    {
        parent::setUp();
        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener);

        $this->getMockDocumentManager($evm);
    }

    public function testSlugGeneration()
    {
        $this->populate();
        $repo = $this->dm->getRepository(self::SLUG);

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
        $repo = $this->dm->getRepository(self::SLUG);

        $fruits = $repo->findOneByTitle('Fruits');
        $fruits->setTitle('Fructis');

        $this->dm->persist($fruits);
        $this->dm->flush();

        $this->assertEquals('food/fructis', $fruits->getSlug());

        $oranges = $repo->findOneByTitle('Oranges');
        $this->assertEquals('food/fructis/oranges', $oranges->getSlug());

        $citrons = $repo->findOneByTitle('Citrons');
        $this->assertEquals('food/fructis/citrons', $citrons->getSlug());

        $food = $repo->findOneByTitle('Food');
        $food->setTitle('Foodissimo');

        $this->dm->persist($food);
        $this->dm->flush();

        $this->assertEquals('foodissimo', $food->getSlug());
        $this->assertEquals('foodissimo/fructis/oranges', $oranges->getSlug());
        $this->assertEquals('foodissimo/fructis/citrons', $citrons->getSlug());
    }

    private function populate()
    {
        $food = new TreeSlug;
        $food->setTitle('Food');
        $this->dm->persist($food);

        $fruits = new TreeSlug;
        $fruits->setTitle('Fruits');
        $fruits->setParent($food);
        $this->dm->persist($fruits);

        $vegitables = new TreeSlug;
        $vegitables->setTitle('Vegitables');
        $vegitables->setParent($food);
        $this->dm->persist($vegitables);

        $milk = new TreeSlug;
        $milk->setTitle('Milk');
        $milk->setParent($food);
        $this->dm->persist($milk);

        $meat = new TreeSlug;
        $meat->setTitle('Meat');
        $meat->setParent($food);
        $this->dm->persist($meat);

        $oranges = new TreeSlug;
        $oranges->setTitle('Oranges');
        $oranges->setParent($fruits);
        $this->dm->persist($oranges);

        $citrons = new TreeSlug;
        $citrons->setTitle('Citrons');
        $citrons->setParent($fruits);
        $this->dm->persist($citrons);

        $this->dm->flush();
    }
}