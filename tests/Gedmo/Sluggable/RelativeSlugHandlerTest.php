<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Sluggable\Fixture\RelativeSlug;
use Gedmo\Tree\TreeListener;

/**
 * These are tests for Sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Sluggable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class RelativeSlugHandlerTest extends BaseTestCaseORM
{
    const TARGET = "Sluggable\\Fixture\\RelativeSlug";

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $evm->addEventSubscriber(new SluggableListener);
        $evm->addEventSubscriber(new TreeListener);

        $conn = array(
            'driver' => 'pdo_mysql',
            'host' => '127.0.0.1',
            'dbname' => 'test',
            'user' => 'root',
            'password' => 'nimda'
        );
        //$this->getMockCustomEntityManager($conn, $evm);
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

        $food = new RelativeSlug;
        $food->setTitle('Food');

        $fruits = new RelativeSlug;
        $fruits->setTitle('Fruits');

        $vegitables = new RelativeSlug;
        $vegitables->setTitle('Vegitables');

        $milk = new RelativeSlug;
        $milk->setTitle('Milk');

        $meat = new RelativeSlug;
        $meat->setTitle('Meat');

        $oranges = new RelativeSlug;
        $oranges->setTitle('Oranges');

        $citrons = new RelativeSlug;
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
