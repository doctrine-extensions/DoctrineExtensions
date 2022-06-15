<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Translatable;

use Doctrine\Common\EventManager;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Tests\Tool\BaseTestCaseMongoODM;
use Gedmo\Tests\Translatable\Fixture\Document\Article;
use Gedmo\Translatable\Document\Repository\TranslationRepository;
use Gedmo\Translatable\Document\Translation;
use Gedmo\Translatable\TranslatableListener;

/**
 * These are tests for Translatable behavior ODM implementation
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class TranslatableDocumentTest extends BaseTestCaseMongoODM
{
    public const ARTICLE = Article::class;
    public const TRANSLATION = Translation::class;

    /**
     * @var TranslatableListener
     */
    private $translatableListener;

    /**
     * @var string|null
     */
    private $articleId;

    protected function setUp(): void
    {
        parent::setUp();
        $evm = new EventManager();
        $this->translatableListener = new TranslatableListener();
        $this->translatableListener->setDefaultLocale('en_us');
        $this->translatableListener->setTranslatableLocale('en_us');
        $evm->addEventSubscriber(new SluggableListener());
        $evm->addEventSubscriber($this->translatableListener);

        $this->getDefaultDocumentManager($evm);
        $this->populate();
    }

    public function testTranslation(): void
    {
        // test inserted translations
        $repo = $this->dm->getRepository(self::ARTICLE);
        $article = $repo->findOneBy(['title' => 'Title EN']);

        $transRepo = $this->dm->getRepository(self::TRANSLATION);
        static::assertInstanceOf(TranslationRepository::class, $transRepo);

        $translations = $transRepo->findTranslations($article);
        static::assertCount(0, $translations);

        // test second translations
        $this->translatableListener->setTranslatableLocale('de_de');
        $article->setTitle('Title DE');
        $article->setCode('Code DE');

        $this->dm->persist($article);
        $this->dm->flush();
        $this->dm->clear();

        $article = $repo->find($this->articleId);
        $translations = $transRepo->findTranslations($article);
        static::assertCount(1, $translations);

        static::assertArrayHasKey('de_de', $translations);
        static::assertArrayHasKey('title', $translations['de_de']);
        static::assertSame('Title DE', $translations['de_de']['title']);

        static::assertArrayHasKey('code', $translations['de_de']);
        static::assertSame('Code DE', $translations['de_de']['code']);

        static::assertArrayHasKey('slug', $translations['de_de']);
        static::assertSame('title-de-code-de', $translations['de_de']['slug']);

        // test value update
        $this->dm->clear();
        $this->translatableListener->setTranslatableLocale('en_us');
        $article = $repo->find($this->articleId);

        static::assertSame('Title EN', $article->getTitle());
        static::assertSame('Code EN', $article->getCode());
        static::assertSame('title-en-code-en', $article->getSlug());

        // test translation update
        $article->setTitle('Title EN Updated');
        $article->setCode('Code EN Updated');
        $this->dm->persist($article);
        $this->dm->flush();
        $this->dm->clear();

        $article = $repo->find($this->articleId);
        static::assertSame('Title EN Updated', $article->getTitle());
        static::assertSame('Code EN Updated', $article->getCode());

        // test removal of translations
        $this->dm->remove($article);
        $this->dm->flush();
        $this->dm->clear();

        $article = $repo->find($this->articleId);
        static::assertNull($article);

        $translations = $transRepo->findTranslationsByObjectId($this->articleId);
        static::assertCount(0, $translations);
    }

    public function testFindObjectByTranslatedField(): void
    {
        $repo = $this->dm->getRepository(self::ARTICLE);
        $article = $repo->findOneBy(['title' => 'Title EN']);
        static::assertInstanceOf(self::ARTICLE, $article);

        $this->translatableListener->setTranslatableLocale('de_de');
        $article->setTitle('Title DE');
        $article->setCode('Code DE');

        $this->dm->persist($article);
        $this->dm->flush();
        $this->dm->clear();

        $transRepo = $this->dm->getRepository(self::TRANSLATION);
        static::assertInstanceOf(TranslationRepository::class, $transRepo);

        $articleFound = $transRepo->findObjectByTranslatedField(
            'title',
            'Title DE',
            self::ARTICLE
        );
        static::assertInstanceOf(self::ARTICLE, $articleFound);

        static::assertSame($article->getId(), $articleFound->getId());
    }

    private function populate(): void
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
