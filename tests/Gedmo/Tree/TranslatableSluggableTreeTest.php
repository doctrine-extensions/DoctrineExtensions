<?php

namespace Gedmo\Tree;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Tree\Fixture\BehavioralCategory;
use Gedmo\Translatable\TranslatableListener;
use Gedmo\Sluggable\SluggableListener;
use Doctrine\ORM\Proxy\Proxy;

class TranslatableSluggableTreeTest extends BaseTestCaseORM
{
    const CATEGORY = "Tree\\Fixture\\BehavioralCategory";
    const TRANSLATION = "Tree\\Fixture\\BehavioralCategoryTranslation";

    private $translatableListener;

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $this->translatableListener = new TranslatableListener;
        $this->translatableListener->setTranslatableLocale('en');
        $evm->addEventSubscriber($this->translatableListener);
        $evm->addEventSubscriber(new TreeListener);
        $evm->addEventSubscriber(new SluggableListener);

        $this->getMockSqliteEntityManager($evm);
        $this->populate();
    }

    /**
     * @test
     */
    function shouldHandleNestedBehaviors()
    {
        $vegies = $this->em->getRepository(self::CATEGORY)
            ->findOneByTitle('Vegitables');

        $childCount = $this->em->getRepository(self::CATEGORY)
            ->childCount($vegies);
        $this->assertEquals(2, $childCount);

        // test slug

        $this->assertEquals('vegitables', $vegies->getSlug());

        // run second translation test

        $this->translatableListener->setTranslatableLocale('de');
        $vegies->setTitle('Deutschebles');
        $this->em->persist($vegies);
        $this->em->flush();
        $this->em->clear();

        $this->translatableListener->setTranslatableLocale('en');

        $vegies = $this->em->getRepository(self::CATEGORY)
            ->find($vegies->getId());

        $translations = $vegies->getTranslations()->toArray();

        $this->assertCount(2, $translations);
        foreach ($translations as $trans) {
            switch ($trans->getLocale()) {
                case "de":
                    $this->assertSame('Deutschebles', $trans->getTitle());
                    $this->assertSame('deutschebles', $trans->getSlug());
                    break;
                case "en":
                    $this->assertSame('Vegitables', $trans->getTitle());
                    $this->assertSame('vegitables', $trans->getSlug());
                    break;
            }
        }
    }

    /**
     * @test
     */
    function translationsShouldBeAvailable()
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
        $this->translatableListener->setTranslatableLocale('de');

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
            self::TRANSLATION
        );
    }

    private function populateDeTranslations()
    {
        $this->translatableListener->setTranslatableLocale('de');
        $repo = $this->em->getRepository(self::CATEGORY);
        $food = $repo->findOneByTitle('Food');
        $food->setTitle('Lebensmittel');

        $vegies = $repo->findOneByTitle('Vegitables');
        $vegies->setTitle('Gemüse');

        $this->em->persist($food);
        $this->em->persist($vegies);
        $this->em->flush();
        $this->em->clear();
        $this->translatableListener->setTranslatableLocale('en');
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
    }
}
