<?php

namespace Gedmo\Tests\Translatable;

use Doctrine\Common\EventManager;
use Gedmo\Tests\Tool\BaseTestCaseMongoODM;
use Gedmo\Tests\Translatable\Fixture\Document\SimpleArticle as Article;
use Gedmo\Translatable\Document\Translation;
use Gedmo\Translatable\TranslatableListener;

/**
 * These are tests for translatable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class TranslatableDocumentCollectionTest extends BaseTestCaseMongoODM
{
    public const ARTICLE = Article::class;
    public const TRANSLATION = Translation::class;

    private $translatableListener;
    private $id;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $this->translatableListener = new TranslatableListener();
        $this->translatableListener->setDefaultLocale('en_us');
        $this->translatableListener->setTranslatableLocale('en_us');
        $evm->addEventSubscriber($this->translatableListener);

        $this->getMockDocumentManager($evm);
        $this->populate();
    }

    /**
     * @test
     */
    public function shouldPersistMultipleTranslations()
    {
        $repo = $this->dm->getRepository(self::TRANSLATION);
        $sport = $this->dm->getRepository(self::ARTICLE)->find($this->id);
        $translations = $repo->findTranslations($sport);

        static::assertArrayHasKey('de_de', $translations);
        static::assertArrayHasKey('title', $translations['de_de']);
        static::assertArrayHasKey('content', $translations['de_de']);
        static::assertSame('sport de', $translations['de_de']['title']);
        static::assertSame('content de', $translations['de_de']['content']);

        static::assertArrayHasKey('ru_ru', $translations);
        static::assertArrayHasKey('title', $translations['ru_ru']);
        static::assertArrayHasKey('content', $translations['ru_ru']);
        static::assertSame('sport ru', $translations['ru_ru']['title']);
        static::assertSame('content ru', $translations['ru_ru']['content']);
    }

    /**
     * @test
     */
    public function shouldUpdateTranslation()
    {
        $repo = $this->dm->getRepository(self::TRANSLATION);
        $sport = $this->dm->getRepository(self::ARTICLE)->find($this->id);
        $repo
            ->translate($sport, 'title', 'ru_ru', 'sport ru change')
            ->translate($sport, 'content', 'ru_ru', 'content ru change')
        ;
        $this->dm->flush();

        $translations = $repo->findTranslations($sport);
        static::assertCount(2, $translations);

        static::assertArrayHasKey('ru_ru', $translations);
        static::assertArrayHasKey('title', $translations['ru_ru']);
        static::assertArrayHasKey('content', $translations['ru_ru']);
        static::assertSame('sport ru change', $translations['ru_ru']['title']);
        static::assertSame('content ru change', $translations['ru_ru']['content']);
    }

    /**
     * @test
     */
    public function shouldUpdateMultipleTranslations()
    {
        $repo = $this->dm->getRepository(self::TRANSLATION);
        $sport = $this->dm->getRepository(self::ARTICLE)->find($this->id);
        $sport->setTitle('Changed');
        $repo
            ->translate($sport, 'title', 'lt_lt', 'sport lt')
            ->translate($sport, 'content', 'lt_lt', 'content lt')
            ->translate($sport, 'title', 'ru_ru', 'sport ru change')
            ->translate($sport, 'content', 'ru_ru', 'content ru change')
            ->translate($sport, 'title', 'en_us', 'sport en update')
            ->translate($sport, 'content', 'en_us', 'content en update')
        ;

        $this->dm->flush();

        static::assertSame('sport en update', $sport->getTitle());
        static::assertSame('content en update', $sport->getContent());

        $translations = $repo->findTranslations($sport);

        static::assertArrayHasKey('de_de', $translations);
        static::assertArrayHasKey('title', $translations['de_de']);
        static::assertArrayHasKey('content', $translations['de_de']);
        static::assertSame('sport de', $translations['de_de']['title']);
        static::assertSame('content de', $translations['de_de']['content']);

        static::assertArrayHasKey('ru_ru', $translations);
        static::assertArrayHasKey('title', $translations['ru_ru']);
        static::assertArrayHasKey('content', $translations['ru_ru']);
        static::assertSame('sport ru change', $translations['ru_ru']['title']);
        static::assertSame('content ru change', $translations['ru_ru']['content']);

        static::assertArrayHasKey('lt_lt', $translations);
        static::assertArrayHasKey('title', $translations['lt_lt']);
        static::assertArrayHasKey('content', $translations['lt_lt']);
        static::assertSame('sport lt', $translations['lt_lt']['title']);
        static::assertSame('content lt', $translations['lt_lt']['content']);
    }

    private function populate()
    {
        $repo = $this->dm->getRepository(self::TRANSLATION);
        $sport = new Article();
        $sport->setTitle('Sport');
        $sport->setContent('about sport');

        $repo
            ->translate($sport, 'title', 'de_de', 'sport de')
            ->translate($sport, 'content', 'de_de', 'content de')
            ->translate($sport, 'title', 'ru_ru', 'sport ru')
            ->translate($sport, 'content', 'ru_ru', 'content ru')
        ;

        $this->dm->persist($sport);
        $this->dm->flush();
        $this->id = $sport->getId();
    }
}
