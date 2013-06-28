<?php

namespace Translatable;

use Doctrine\Common\EventManager;
use Doctrine\ORM\PersistentCollection;
use Tool\BaseTestCaseORM;
use Fixture\Translatable\Sluggable\Post;
use Fixture\Translatable\Sluggable\PostTranslation;
use Gedmo\Translatable\TranslatableListener;
use Gedmo\Sluggable\SluggableListener;

class UniqueSlugTranslationTest extends BaseTestCaseORM
{
    private $translatable;

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $evm->addEventSubscriber($this->translatable = new TranslatableListener);
        $evm->addEventSubscriber(new SluggableListener);
        $this->getMockSqliteEntityManager($evm);
    }

    /**
     * @test
     */
    function shouldTranslateTranslatableEntityFields()
    {
        // test new
        $food = new Post;
        $food->setTitle('Food'); // en translation through default
        $food->addTranslation(new PostTranslation('lt', 'Food'));
        $food->addTranslation(new PostTranslation('de', 'Food'));
        $this->em->persist($food);

        $next = new Post;
        $next->addTranslation(new PostTranslation('en', 'Next Post'));
        $next->addTranslation(new PostTranslation('lt', 'Next Post'));
        $next->addTranslation(new PostTranslation('de', 'Food'));
        $this->em->persist($next);

        $this->em->flush();

        $this->assertTranslationSlugIs($food->getTranslations(), 'en', 'food-3');
        $this->assertTranslationSlugIs($food->getTranslations(), 'lt', 'food');
        $this->assertTranslationSlugIs($food->getTranslations(), 'de', 'food-1');
        $this->assertTranslationSlugIs($next->getTranslations(), 'de', 'food-2');
        $this->assertTranslationSlugIs($next->getTranslations(), 'en', 'next-post');
        $this->assertTranslationSlugIs($next->getTranslations(), 'lt', 'next-post-1');

        $this->em->clear();
        $this->translatable->setTranslatableLocale('en');

        $post = $this->em->getRepository('Fixture\Translatable\Sluggable\Post')->findOneById($food->getId());
        $this->assertSame('food-3', $post->getSlug());

        $post = $this->em->getRepository('Fixture\Translatable\Sluggable\Post')->findOneById($next->getId());
        $this->assertSame('next-post', $post->getSlug());
    }

    private function assertTranslationSlugIs(PersistentCollection $translations, $locale, $slug)
    {
        $transByLocale = null;
        foreach ($translations as $trans) {
            if ($trans->getLocale() === $locale) {
                $transByLocale = $trans;
                break;
            }
        }
        $this->assertNotNull($transByLocale, "Translation was not found by locale: $locale");
        $this->assertSame($slug, $transByLocale->getSlug(), "Slug was not same as expected");
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            'Fixture\Translatable\Sluggable\Post',
            'Fixture\Translatable\Sluggable\PostTranslation',
        );
    }
}

