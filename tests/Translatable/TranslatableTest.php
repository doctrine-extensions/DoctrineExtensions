<?php

namespace Translatable;

use Doctrine\Common\EventManager;
use TestTool\ObjectManagerTestCase;
use Fixture\Translatable\Post;
use Fixture\Translatable\PostTranslation;
use Gedmo\Translatable\TranslatableListener;

class TranslatableTest extends ObjectManagerTestCase
{
    private $translatable;
    private $em;

    protected function setUp()
    {
        $evm = new EventManager;
        $evm->addEventSubscriber($this->translatable = new TranslatableListener);
        $this->em = $this->createEntityManager($evm);
    }

    protected function tearDown()
    {
        $this->releaseEntityManager($this->em);
    }

    /**
     * @test
     */
    function shouldTranslateTranslatableEntityFields()
    {
        // test new
        $food = new Post;
        $food->setTitle('Food');
        $food->setContent('About food');

        $this->em->persist($food);
        $this->em->flush();

        $translations = $food->getTranslations();
        $this->assertCount(1, $translations, "There should be one english translation available");

        $translations = $this->em->getRepository('Fixture\Translatable\PostTranslation')->findAll();
        $this->assertCount(1, $translations, "There should be one english translation available");

        // test update
        $food->setTitle('Food Updated');
        $this->em->persist($food);
        $this->em->flush();

        $this->assertSame('Food Updated', $translations[0]->getTitle());

        // create a new translation
        $this->translatable->setTranslatableLocale('lt');
        $food->setTitle('Maistas');
        $food->setContent('Apie maista');

        $this->em->persist($food);
        $this->em->flush();

        $translations = $food->getTranslations();
        $this->assertCount(2, $translations, "There should be two translations available");

        $translations = $this->em->getRepository('Fixture\Translatable\PostTranslation')->findAll();
        $this->assertCount(2, $translations, "There should be two translations available");

        // try post load
        $this->em->clear();
        $this->translatable->setTranslatableLocale('en');
        $food = $this->em->getRepository('Fixture\Translatable\Post')->findOneById($food->getId());
        $this->assertSame('Food Updated', $food->getTitle(), "Should be translated in english on load");
        $this->assertSame('About food', $food->getContent(), "Should be translated in english on load");

        $this->em->clear();
        $this->translatable->setTranslatableLocale('lt');
        $food = $this->em->getRepository('Fixture\Translatable\Post')->findOneById($food->getId());
        $this->assertSame('Maistas', $food->getTitle(), "Should be translated in lithuanian on load");
        $this->assertSame('Apie maista', $food->getContent(), "Should be translated in lithuanian on load");
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
            $post->setContent('Content '.$locale);
            $this->em->persist($post);
            $this->em->flush();
        }

        $this->translatable
            ->setFallbackLocales(array('undef', 'de'))
            ->setTranslatableLocale('new')
        ;
        $this->em->clear();

        $post = $this->em->getRepository('Fixture\Translatable\Post')->findOneById($post->getId());
        $this->assertSame('Title de', $post->getTitle(), "Should be translated in german on load, based on fallback locale");
        $this->assertSame('Content de', $post->getContent(), "Should be translated in german on load, based on fallback locale");
    }

    /**
     * @test
     */
    function shouldBeAbleToManuallyPushTranslationsIntoCollection()
    {
        $post = new Post;
        $post->addTranslation(new PostTranslation('de', 'de title', 'de content'));
        $post->addTranslation(new PostTranslation('en', 'en title', 'en content'));
        $post->addTranslation(new PostTranslation('ru', 'ru title', 'ru content'));

        $this->em->persist($post);
        $this->em->flush();
        $this->em->clear();

        $post = $this->em->getRepository('Fixture\Translatable\Post')->findOneById($post->getId());
        $this->assertSame('en title', $post->getTitle(), "Should be translated in english on load");
        $this->assertSame('en content', $post->getContent(), "Should be translated in english on load");

        $translations = $this->em->getRepository('Fixture\Translatable\PostTranslation')->findAll();
        $this->assertCount(3, $translations, "There should be three translations available");
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            'Fixture\Translatable\Post',
            'Fixture\Translatable\PostTranslation',
        );
    }
}

