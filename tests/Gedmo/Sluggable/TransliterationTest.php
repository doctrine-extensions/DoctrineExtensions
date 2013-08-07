<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventManager;
use Gedmo\TestTool\ObjectManagerTestCase;
use Gedmo\Fixture\Sluggable\Article;
use Gedmo\Sluggable\SluggableListener;

class TransliterationTest extends ObjectManagerTestCase
{
    const ARTICLE = 'Gedmo\Fixture\Sluggable\Article';

    private $em, $sluggable;

    protected function setUp()
    {
        $evm = new EventManager;
        $evm->addEventSubscriber($this->sluggable = new SluggableListener);

        $this->em = $this->createEntityManager($evm);
        $this->createSchema($this->em, array(
            self::ARTICLE,
        ));
    }

    protected function tearDown()
    {
        $this->releaseEntityManager($this->em);
    }

    /**
     * @test
     */
    function shouldInsertedNewSlug()
    {
        $this->populate();
        $repo = $this->em->getRepository(self::ARTICLE);

        $lithuanian = $repo->findOneByCode('lt');
        $bulgarian = $repo->findOneByCode('bg');
        $russian = $repo->findOneByCode('ru');
        $german = $repo->findOneByCode('de');

        $this->assertSame('transliteration-test-usage-uz-lt', $lithuanian->getSlug());
        $this->assertSame('tova-ie-tiestovo-zaghlaviie-bg', $bulgarian->getSlug());
        $this->assertSame('eto-tiestovyi-zagholovok-ru', $russian->getSlug());
        $this->assertSame('fuhren-aktivitaten-haglofs-de', $german->getSlug());
    }

    /**
     * @test
     */
    function shouldHandleCustomTransliteratorAndUrlizer()
    {
        $this->sluggable->setTransliterator(function($text) {
            return iconv('UTF-8', 'ASCII//TRANSLIT', $text);
        });
        $this->sluggable->setUrlizer(function($text, $separator) {
            $urlized = strtolower(trim(preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $text), $separator));
            return preg_replace("/[\/_|+ -]+/", $separator, $urlized);
        });

        $this->populate();
        $repo = $this->em->getRepository(self::ARTICLE);

        $lithuanian = $repo->findOneByCode('lt');
        $bulgarian = $repo->findOneByCode('bg');
        $russian = $repo->findOneByCode('ru');
        $german = $repo->findOneByCode('de');

        $this->assertSame('transliteration-test-usage-uz-lt-', $lithuanian->getSlug());
        $this->assertSame('-bg-', $bulgarian->getSlug());
        $this->assertSame('-ru-', $russian->getSlug());
        $this->assertSame('fuhren-aktivitaten-haglofs-de-', $german->getSlug());
    }

    private function populate()
    {
        $lithuanian = new Article;
        $lithuanian->setTitle('trąnslįteration tėst ųsąge ūž');
        $lithuanian->setCode('lt');

        $bulgarian = new Article;
        $bulgarian->setTitle('това е тестово заглавие');
        $bulgarian->setCode('bg');

        $russian = new Article;
        $russian->setTitle('это тестовый заголовок');
        $russian->setCode('ru');

        $german = new Article;
        $german->setTitle('führen Aktivitäten Haglöfs');
        $german->setCode('de');

        $this->em->persist($lithuanian);
        $this->em->persist($bulgarian);
        $this->em->persist($russian);
        $this->em->persist($german);
        $this->em->flush();
    }
}
