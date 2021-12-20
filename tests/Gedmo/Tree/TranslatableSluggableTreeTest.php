<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Tree;

use Doctrine\Common\EventManager;
use Doctrine\Persistence\Proxy;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Tests\Tree\Fixture\Article;
use Gedmo\Tests\Tree\Fixture\BehavioralCategory;
use Gedmo\Tests\Tree\Fixture\Comment;
use Gedmo\Translatable\Entity\Translation;
use Gedmo\Translatable\TranslatableListener;
use Gedmo\Tree\TreeListener;

/**
 * These are tests for Tree behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class TranslatableSluggableTreeTest extends BaseTestCaseORM
{
    public const CATEGORY = BehavioralCategory::class;
    public const ARTICLE = Article::class;
    public const COMMENT = Comment::class;
    public const TRANSLATION = Translation::class;

    /**
     * @var TranslatableListener
     */
    private $translatableListener;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new TreeListener());
        $this->translatableListener = new TranslatableListener();
        $this->translatableListener->setTranslatableLocale('en_US');
        $evm->addEventSubscriber(new SluggableListener());
        $evm->addEventSubscriber($this->translatableListener);

        $this->getDefaultMockSqliteEntityManager($evm);
        $this->populate();
    }

    public function testNestedBehaviors(): void
    {
        $vegies = $this->em->getRepository(self::CATEGORY)
            ->findOneBy(['title' => 'Vegitables']);

        $childCount = $this->em->getRepository(self::CATEGORY)
            ->childCount($vegies);
        static::assertSame(2, $childCount);

        // test slug

        static::assertSame('vegitables', $vegies->getSlug());

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

        static::assertCount(1, $translations);
        static::assertArrayHasKey('de_DE', $translations);

        static::assertArrayHasKey('title', $translations['de_DE']);
        static::assertSame('Deutschebles', $translations['de_DE']['title']);

        static::assertArrayHasKey('slug', $translations['de_DE']);
        static::assertSame('deutschebles', $translations['de_DE']['slug']);
    }

    public function testTranslations(): void
    {
        $this->populateDeTranslations();
        $repo = $this->em->getRepository(self::CATEGORY);
        $vegies = $repo->find(4);

        static::assertSame('Vegitables', $vegies->getTitle());
        $food = $vegies->getParent();
        // test if proxy triggers postLoad event
        static::assertInstanceOf(Proxy::class, $food);
        static::assertInstanceOf(BehavioralCategory::class, $food);
        static::assertSame('Food', $food->getTitle());

        $this->em->clear();
        $this->translatableListener->setTranslatableLocale('de_DE');

        $vegies = $repo->find(4);
        static::assertSame('Gemüse', $vegies->getTitle());
        $food = $vegies->getParent();
        static::assertInstanceOf(Proxy::class, $food);
        static::assertInstanceOf(BehavioralCategory::class, $food);
        static::assertSame('Lebensmittel', $food->getTitle());
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::CATEGORY,
            self::ARTICLE,
            self::COMMENT,
            self::TRANSLATION,
        ];
    }

    private function populateDeTranslations(): void
    {
        $this->translatableListener->setTranslatableLocale('de_DE');
        $repo = $this->em->getRepository(self::CATEGORY);
        $food = $repo->findOneBy(['title' => 'Food']);
        $food->setTitle('Lebensmittel');

        $vegies = $repo->findOneBy(['title' => 'Vegitables']);
        $vegies->setTitle('Gemüse');

        $this->em->persist($food);
        $this->em->persist($vegies);
        $this->em->flush();
        $this->em->clear();
        $this->translatableListener->setTranslatableLocale('en_US');
    }

    private function populate(): void
    {
        $root = new BehavioralCategory();
        $root->setTitle('Food');

        $root2 = new BehavioralCategory();
        $root2->setTitle('Sports');

        $child = new BehavioralCategory();
        $child->setTitle('Fruits');
        $child->setParent($root);

        $child2 = new BehavioralCategory();
        $child2->setTitle('Vegitables');
        $child2->setParent($root);

        $childsChild = new BehavioralCategory();
        $childsChild->setTitle('Carrots');
        $childsChild->setParent($child2);

        $potatoes = new BehavioralCategory();
        $potatoes->setTitle('Potatoes');
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
