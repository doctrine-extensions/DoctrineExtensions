<?php

namespace Gedmo\Translatable\Document;

use Gedmo\TestTool\ObjectManagerTestCase;
use Doctrine\Common\EventManager;
use Gedmo\Fixture\Translatable\Document\Post;
use Gedmo\Fixture\Translatable\Document\PostTranslation;
use Gedmo\Fixture\Translatable\Document\Comment;
use Gedmo\Fixture\Translatable\Document\CommentTranslation;
use Gedmo\Translatable\TranslatableListener;

class TranslationTest extends ObjectManagerTestCase
{
    private $translatable;
    private $dm;

    protected function setUp()
    {
        $evm = new EventManager;
        $evm->addEventSubscriber($this->translatable = new TranslatableListener);
        $this->dm = $this->createDocumentManager($evm);
    }

    protected function tearDown()
    {
        $this->releaseDocumentManager($this->dm);
    }

    /**
     * @test
     */
    function shouldCreateTranslations()
    {
        // test new
        $food = new Post;
        $food->setTitle('Food');

        $this->dm->persist($food);
        $this->dm->flush();

        $translations = $food->getTranslations();
        $this->assertCount(1, $translations, "There should be one english translation available");

        $translations = $this->dm->getRepository('Gedmo\Fixture\Translatable\Document\PostTranslation')->findAll();
        $this->assertCount(1, $translations = iterator_to_array($translations), "There should be one english translation available");

        // test update
        $food->setTitle('Food Updated');
        $this->dm->persist($food);
        $this->dm->flush();

        $this->assertSame('Food Updated', current($translations)->getTitle());

        // create a new translation
        $this->translatable->setTranslatableLocale('lt');
        $food->setTitle('Maistas');

        $this->dm->persist($food);
        $this->dm->flush();

        $translations = $food->getTranslations();
        $this->assertCount(2, $translations, "There should be two translations available");

        $translations = $this->dm->getRepository('Gedmo\Fixture\Translatable\Document\PostTranslation')->findAll();
        $this->assertCount(2, $translations = iterator_to_array($translations), "There should be two translations available");

        // try post load
        $this->dm->clear();
        $this->translatable->setTranslatableLocale('en');
        $food = $this->dm->getRepository('Gedmo\Fixture\Translatable\Document\Post')->findOneById($food->getId());
        $this->assertSame('Food Updated', $food->getTitle(), "Should be translated in english on load");

        $this->dm->clear();
        $this->translatable->setTranslatableLocale('lt');
        $food = $this->dm->getRepository('Gedmo\Fixture\Translatable\Document\Post')->findOneById($food->getId());
        $this->assertSame('Maistas', $food->getTitle(), "Should be translated in lithuanian on load");
    }

    /**
     * @test
     */
    function shouldBeAbleToFallbackToSpecifiedTranslationIfAvailable()
    {
        $post = new Post;
        foreach (array('en', 'de', 'fr', 'ru') as $locale) {
            $this->translatable->setTranslatableLocale($locale);
            $post->setTitle('Title '.$locale);
            $this->dm->persist($post);
            $this->dm->flush();
        }

        $this->translatable
            ->setFallbackLocales(array('undef', 'de'))
            ->setTranslatableLocale('new')
        ;
        $this->dm->clear();

        $post = $this->dm->getRepository('Gedmo\Fixture\Translatable\Document\Post')->findOneById($post->getId());
        $this->assertSame('Title de', $post->getTitle(), "Should be translated in german on load, based on fallback locale");
    }

    /**
     * @test
     */
    function shouldBeAbleToManuallyPushTranslationsIntoCollection()
    {
        $post = new Post;
        $post->addTranslation(new PostTranslation('de', 'de title'));
        $post->addTranslation(new PostTranslation('en', 'en title'));
        $post->addTranslation(new PostTranslation('ru', 'ru title'));

        $this->dm->persist($post);
        $this->dm->flush();
        $this->dm->clear();

        $post = $this->dm->getRepository('Gedmo\Fixture\Translatable\Document\Post')->findOneById($post->getId());
        $this->assertSame('en title', $post->getTitle(), "Should be translated in english on load");

        $translations = $this->dm->getRepository('Gedmo\Fixture\Translatable\Document\PostTranslation')->findAll();
        $this->assertCount(3, iterator_to_array($translations), "There should be three translations available");
    }
}
