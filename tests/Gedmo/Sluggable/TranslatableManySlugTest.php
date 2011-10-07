<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Doctrine\Common\Util\Debug,
    Gedmo\Translatable\Translatable,
    Gedmo\Translatable\Entity\Translation,
    Gedmo\Translatable\TranslationListener,
    Sluggable\Fixture\TransArticleManySlug;

/**
 * These are tests for Sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Sluggable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TranslatableManySlugTest extends BaseTestCaseORM
{
    private $articleId;
    private $translationListener;

    const ARTICLE = 'Sluggable\\Fixture\\TransArticleManySlug';
    const TRANSLATION = 'Gedmo\\Translatable\\Entity\\Translation';

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $this->translationListener = new TranslationListener();
        $this->translationListener->setTranslatableLocale('en_us');
        $evm->addEventSubscriber(new SluggableListener);
        $evm->addEventSubscriber($this->translationListener);

        $this->getMockSqliteEntityManager($evm);
        $this->populate();
    }

    public function testSlugAndTranslation()
    {
        $article = $this->em->find(self::ARTICLE, $this->articleId);
        $this->assertTrue($article instanceof Translatable && $article instanceof Sluggable);
        $this->assertEquals($article->getSlug(), 'the-title-my-code');
        $this->assertEquals($article->getUniqueSlug(), 'the-unique-title');
        $repo = $this->em->getRepository(self::TRANSLATION);

        $translations = $repo->findTranslations($article);
        $this->assertEquals(count($translations), 0);

        $article = $this->em->find(self::ARTICLE, $this->articleId);
        $article->setTranslatableLocale('de_de');
        $article->setCode('code in de');
        $article->setTitle('title in de');

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();

        $repo = $this->em->getRepository(self::TRANSLATION);
        $translations = $repo->findTranslations($article);
        $this->assertEquals(count($translations), 1);
        $this->assertArrayHasKey('de_de', $translations);
        $this->assertEquals(3, count($translations['de_de']));

        $this->assertEquals('title in de', $translations['de_de']['title']);

        $this->assertArrayHasKey('slug', $translations['de_de']);
        $this->assertEquals('title-in-de-code-in-de', $translations['de_de']['slug']);
    }
    
    public function testUniqueness()
    {
       $a0 = new TransArticleManySlug;
       $a0->setTitle('the title');
       $a0->setCode('my code');
       $a0->setUniqueTitle('title');

       $this->em->persist($a0);

       $a1 = new TransArticleManySlug;
       $a1->setTitle('the title');
       $a1->setCode('my code');
       $a1->setUniqueTitle('title');

       $this->em->persist($a1);
       $this->em->flush();

       $this->assertEquals('title', $a0->getUniqueSlug());
       $this->assertEquals('title-1', $a1->getUniqueSlug());
       // if its translated maybe should be different
       $this->assertEquals('the-title-my-code-1', $a0->getSlug());
       $this->assertEquals('the-title-my-code-2', $a1->getSlug());
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::ARTICLE,
            self::TRANSLATION
        );
    }

    private function populate()
    {
        $article = new TransArticleManySlug();
        $article->setTitle('the title');
        $article->setCode('my code');
        $article->setUniqueTitle('the unique title');
        

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();
        $this->articleId = $article->getId();
    }
}
