<?php

namespace Gedmo\Translatable;

use Tool\BaseTestCaseMongoODM;
use Gedmo\Sluggable\SluggableListener;
use Doctrine\Common\EventManager;
use Translatable\Fixture\Document\Article;

/**
 * These are tests for Translatable behavior ODM implementation
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Translatable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TranslatableDocumentTest extends BaseTestCaseMongoODM
{
    const ARTICLE = 'Translatable\\Fixture\\Document\\Article';
    const TRANSLATION = 'Gedmo\\Translatable\\Document\\Translation';

    private $translationListener;
    private $articleId;

    protected function setUp()
    {
        parent::setUp();
        $evm = new EventManager();
        $this->translationListener = new TranslationListener;
        $this->translationListener->setDefaultLocale('en_us');
        $this->translationListener->setTranslatableLocale('en_us');
        $evm->addEventSubscriber(new SluggableListener);
        $evm->addEventSubscriber($this->translationListener);

        $this->getMockDocumentManager($evm);
        $this->populate();
    }

    public function testTranslation()
    {
        // test inserted translations
        $repo = $this->dm->getRepository(self::ARTICLE);
        $article = $repo->findOneByTitle('Title EN');

        $transRepo = $this->dm->getRepository(self::TRANSLATION);
        $this->assertTrue($transRepo instanceof Document\Repository\TranslationRepository);

        $translations = $transRepo->findTranslations($article);
        $this->assertEquals(0, count($translations));

        // test second translations
        $this->translationListener->setTranslatableLocale('de_de');
        $article->setTitle('Title DE');
        $article->setCode('Code DE');

        $this->dm->persist($article);
        $this->dm->flush();
        $this->dm->clear();

        $article = $repo->find($this->articleId);
        $translations = $transRepo->findTranslations($article);
        $this->assertEquals(1, count($translations));

        $this->assertArrayHasKey('de_de', $translations);
        $this->assertArrayHasKey('title', $translations['de_de']);
        $this->assertEquals('Title DE', $translations['de_de']['title']);

        $this->assertArrayHasKey('code', $translations['de_de']);
        $this->assertEquals('Code DE', $translations['de_de']['code']);

        $this->assertArrayHasKey('slug', $translations['de_de']);
        $this->assertEquals('title-de-code-de', $translations['de_de']['slug']);

        // test value update
        $this->dm->clear();
        $this->translationListener->setTranslatableLocale('en_us');
        $article = $repo->find($this->articleId);

        $this->assertEquals('Title EN', $article->getTitle());
        $this->assertEquals('Code EN', $article->getCode());
        $this->assertEquals('title-en-code-en', $article->getSlug());

        // test translation update
        $article->setTitle('Title EN Updated');
        $article->setCode('Code EN Updated');
        $this->dm->persist($article);
        $this->dm->flush();
        $this->dm->clear();

        $article = $repo->find($this->articleId);
        $this->assertEquals('Title EN Updated', $article->getTitle());
        $this->assertEquals('Code EN Updated', $article->getCode());

        // test removal of translations
        $this->dm->remove($article);
        $this->dm->flush();
        $this->dm->clear();

        $article = $repo->find($this->articleId);
        $this->assertTrue(is_null($article));

        $translations = $transRepo->findTranslationsByObjectId($this->articleId);
        $this->assertEquals(0, count($translations));
    }

    private function populate()
    {
        $art0 = new Article();
        $art0->setTitle('Title EN');
        $art0->setCode('Code EN');

        $this->dm->persist($art0);
        $this->dm->flush();
        $this->articleId = $art0->getId();
        $this->dm->clear();
    }
}