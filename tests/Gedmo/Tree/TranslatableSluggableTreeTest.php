<?php

namespace Gedmo\Tree;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Doctrine\Common\Util\Debug,
    Tree\Fixture\BehavioralCategory,
    Tree\Fixture\Article,
    Tree\Fixture\Comment,
    Gedmo\Translatable\TranslatableListener,
    Gedmo\Translatable\Entity\Translation,
    Gedmo\Sluggable\SluggableListener,
    Doctrine\ORM\Proxy\Proxy;

/**
 * These are tests for Tree behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TranslatableSluggableTreeTest extends BaseTestCaseORM
{
    const CATEGORY = "Tree\\Fixture\\BehavioralCategory";
    const ARTICLE = "Tree\\Fixture\\Article";
    const COMMENT = "Tree\\Fixture\\Comment";
    const TRANSLATION = "Gedmo\\Translatable\\Entity\\Translation";

    private $translatableListener;

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $evm->addEventSubscriber(new TreeListener);
        $this->translatableListener = new TranslatableListener;
        $this->translatableListener->setTranslatableLocale('en_US');
        $evm->addEventSubscriber(new SluggableListener);
        $evm->addEventSubscriber($this->translatableListener);

        $this->getMockSqliteEntityManager($evm);
        $this->populate();
    }

    public function testNestedBehaviors()
    {
        $vegies = $this->em->getRepository(self::CATEGORY)
            ->findOneByTitle('Vegitables');

        $childCount = $this->em->getRepository(self::CATEGORY)
            ->childCount($vegies);
        $this->assertEquals(2, $childCount);

        // test slug

        $this->assertEquals('vegitables', $vegies->getSlug());

        // run second translation test

        $this->translatableListener->setTranslatableLocale('de_DE');
        $vegies->setTitle('Deutschebles');
        $this->em->persist($vegies);
        $this->em->flush();
        $this->em->clear();

        $this->translatableListener->setTranslatableLocale('en_US');

        $vegies = $this->em->getRepository(self::CATEGORY)
            ->find($vegies->getId());

        $translations = $this->em->getRepository(self::TRANSLATION)
            ->findTranslations($vegies);

        $this->assertCount(1, $translations);
        $this->assertArrayHasKey('de_DE', $translations);

        $this->assertArrayHasKey('title', $translations['de_DE']);
        $this->assertEquals('Deutschebles', $translations['de_DE']['title']);

        $this->assertArrayHasKey('slug', $translations['de_DE']);
        $this->assertEquals('deutschebles', $translations['de_DE']['slug']);
    }

    public function testTranslations()
    {
        $this->populateDeTranslations();
        $repo = $this->em->getRepository(self::CATEGORY);
        $vegies = $repo->find(4);

        $this->assertEquals('Vegitables', $vegies->getTitle());
        $food = $vegies->getParent();
        // test if proxy triggers postLoad event
        $this->assertTrue($food instanceof Proxy);
        $this->assertEquals('Food', $food->getTitle());

        $this->em->clear();
        $this->translatableListener->setTranslatableLocale('de_DE');

        $vegies = $repo->find(4);
        $this->assertEquals('Gemüse', $vegies->getTitle());
        $food = $vegies->getParent();
        $this->assertTrue($food instanceof Proxy);
        $this->assertEquals('Lebensmittel', $food->getTitle());
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::CATEGORY,
            self::ARTICLE,
            self::COMMENT,
            self::TRANSLATION
        );
    }

    private function populateDeTranslations()
    {
        $this->translatableListener->setTranslatableLocale('de_DE');
        $repo = $this->em->getRepository(self::CATEGORY);
        $food = $repo->findOneByTitle('Food');
        $food->setTitle('Lebensmittel');

        $vegies = $repo->findOneByTitle('Vegitables');
        $vegies->setTitle('Gemüse');

        $this->em->persist($food);
        $this->em->persist($vegies);
        $this->em->flush();
        $this->em->clear();
        $this->translatableListener->setTranslatableLocale('en_US');
    }

    private function populate()
    {
        $root = new BehavioralCategory();
        $root->setTitle("Food");

        $root2 = new BehavioralCategory();
        $root2->setTitle("Sports");

        $child = new BehavioralCategory();
        $child->setTitle("Fruits");
        $child->setParent($root);

        $child2 = new BehavioralCategory();
        $child2->setTitle("Vegitables");
        $child2->setParent($root);

        $childsChild = new BehavioralCategory();
        $childsChild->setTitle("Carrots");
        $childsChild->setParent($child2);

        $potatoes = new BehavioralCategory();
        $potatoes->setTitle("Potatoes");
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
