<?php

namespace Gedmo\Translatable;

use Tool\BaseTestCaseMongoODM;
use Doctrine\Common\EventManager;
use Translatable\Fixture\Document\SimpleArticle as Article;

/**
 * These are tests for translatable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Translatable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TranslatableDocumentCollectionTest extends BaseTestCaseMongoODM
{
    const ARTICLE = 'Translatable\\Fixture\\Document\\SimpleArticle';
    const TRANSLATION = 'Gedmo\\Translatable\\Document\\Translation';

    private $translatableListener;
    private $id;

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $this->translatableListener = new TranslationListener();
        $this->translatableListener->setDefaultLocale('en_us');
        $this->translatableListener->setTranslatableLocale('en_us');
        $evm->addEventSubscriber($this->translatableListener);

        $this->getMockDocumentManager($evm);
        $this->populate();
    }

    public function testMultipleTranslationPersistence()
    {
        $repo = $this->dm->getRepository(self::TRANSLATION);
        $sport = $this->dm->getRepository(self::ARTICLE)->find($this->id);
        $translations = $repo->findTranslations($sport);

        $this->assertArrayHasKey('de_de', $translations);
        $this->assertArrayHasKey('title', $translations['de_de']);
        $this->assertArrayHasKey('content', $translations['de_de']);
        $this->assertEquals('sport de', $translations['de_de']['title']);
        $this->assertEquals('content de', $translations['de_de']['content']);

        $this->assertArrayHasKey('ru_ru', $translations);
        $this->assertArrayHasKey('title', $translations['ru_ru']);
        $this->assertArrayHasKey('content', $translations['ru_ru']);
        $this->assertEquals('sport ru', $translations['ru_ru']['title']);
        $this->assertEquals('content ru', $translations['ru_ru']['content']);
    }

    public function testMultipleTranslationUpdates()
    {
        $repo = $this->dm->getRepository(self::TRANSLATION);
        $sport = $this->dm->getRepository(self::ARTICLE)->find($this->id);
        $sport->setTitle('Changed');
        $repo
            ->translate($sport, 'title', 'lt_lt', 'sport lt')
            ->translate($sport, 'content', 'lt_lt', 'content lt')
            ->translate($sport, 'title', 'ru_ru', 'sport ru change')
            ->translate($sport, 'content', 'ru_ru', 'content ru change');

        $this->dm->persist($sport);
        $this->dm->flush();
        $translations = $repo->findTranslations($sport);

        $this->assertArrayHasKey('de_de', $translations);
        $this->assertArrayHasKey('title', $translations['de_de']);
        $this->assertArrayHasKey('content', $translations['de_de']);
        $this->assertEquals('sport de', $translations['de_de']['title']);
        $this->assertEquals('content de', $translations['de_de']['content']);

        $this->assertArrayHasKey('ru_ru', $translations);
        $this->assertArrayHasKey('title', $translations['ru_ru']);
        $this->assertArrayHasKey('content', $translations['ru_ru']);
        $this->assertEquals('sport ru change', $translations['ru_ru']['title']);
        $this->assertEquals('content ru change', $translations['ru_ru']['content']);

        $this->assertArrayHasKey('lt_lt', $translations);
        $this->assertArrayHasKey('title', $translations['lt_lt']);
        $this->assertArrayHasKey('content', $translations['lt_lt']);
        $this->assertEquals('sport lt', $translations['lt_lt']['title']);
        $this->assertEquals('content lt', $translations['lt_lt']['content']);
    }

    private function populate()
    {
        $repo = $this->dm->getRepository(self::TRANSLATION);
        $sport = new Article;
        $sport->setTitle('Sport');
        $sport->setContent('about sport');

        $repo
            ->translate($sport, 'title', 'de_de', 'sport de')
            ->translate($sport, 'content', 'de_de', 'content de')
            ->translate($sport, 'title', 'ru_ru', 'sport ru')
            ->translate($sport, 'content', 'ru_ru', 'content ru');

        $this->dm->persist($sport);
        $this->dm->flush();
        $this->id = $sport->getId();
    }
}