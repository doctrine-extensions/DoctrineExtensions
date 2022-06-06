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
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Tests\Translatable\Fixture\Article;
use Gedmo\Tests\Translatable\Fixture\Comment;
use Gedmo\Translatable\Entity\Translation;
use Gedmo\Translatable\TranslatableListener;

/**
 * These are tests for translatable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class TranslatableEntityCollectionTest extends BaseTestCaseORM
{
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
        $this->translatableListener = new TranslatableListener();
        $this->translatableListener->setTranslatableLocale('en_us');
        $this->translatableListener->setDefaultLocale('en_us');
        $evm->addEventSubscriber($this->translatableListener);

        $this->getDefaultMockSqliteEntityManager($evm);
    }

    public function testShouldEnsureSolvedIssue234(): void
    {
        $this->translatableListener->setTranslatableLocale('de');
        $this->translatableListener->setDefaultLocale('en');
        $this->translatableListener->setPersistDefaultLocaleTranslation(true);
        $repo = $this->em->getRepository(self::TRANSLATION);
        $entity = new Article();
        $entity->setTitle('he'); // is translated to de

        $repo
            ->translate($entity, 'title', 'en', 'my article en')
            ->translate($entity, 'title', 'es', 'my article es')
            ->translate($entity, 'title', 'fr', 'my article fr')
            ->translate($entity, 'title', 'de', 'my article de')
        ;

        $this->em->persist($entity);
        $this->em->flush();
        $this->em->clear();
        $trans = $repo->findTranslations($this->em->find(self::ARTICLE, $entity->getId()));
        static::assertCount(4, $trans);
        static::assertSame('my article de', $trans['de']['title']); // overrides "he" which would be used if translate for de not called
        static::assertSame('my article es', $trans['es']['title']);
        static::assertSame('my article fr', $trans['fr']['title']);
        static::assertSame('my article en', $trans['en']['title']);
    }

    public function testShouldPersistMultipleTranslations(): void
    {
        $this->populate();
        $repo = $this->em->getRepository(self::TRANSLATION);
        $sport = $this->em->getRepository(self::ARTICLE)->find(1);
        $translations = $repo->findTranslations($sport);

        static::assertCount(2, $translations);

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

    public function testShouldUpdateTranslation(): void
    {
        $this->populate();
        $repo = $this->em->getRepository(self::TRANSLATION);
        $sport = $this->em->getRepository(self::ARTICLE)->find(1);
        $repo
            ->translate($sport, 'title', 'ru_ru', 'sport ru change')
            ->translate($sport, 'content', 'ru_ru', 'content ru change')
        ;
        $this->em->flush();

        $translations = $repo->findTranslations($sport);
        static::assertCount(2, $translations);

        static::assertArrayHasKey('ru_ru', $translations);
        static::assertArrayHasKey('title', $translations['ru_ru']);
        static::assertArrayHasKey('content', $translations['ru_ru']);
        static::assertSame('sport ru change', $translations['ru_ru']['title']);
        static::assertSame('content ru change', $translations['ru_ru']['content']);
    }

    public function testShouldUpdateMultipleTranslations(): void
    {
        $this->populate();
        $repo = $this->em->getRepository(self::TRANSLATION);
        $sport = $this->em->getRepository(self::ARTICLE)->find(1);
        $repo
            ->translate($sport, 'title', 'lt_lt', 'sport lt')
            ->translate($sport, 'content', 'lt_lt', 'content lt')
            ->translate($sport, 'title', 'ru_ru', 'sport ru change')
            ->translate($sport, 'content', 'ru_ru', 'content ru change')
            ->translate($sport, 'title', 'en_us', 'sport en update')
            ->translate($sport, 'content', 'en_us', 'content en update')
        ;
        $this->em->flush();

        static::assertSame('sport en update', $sport->getTitle());
        static::assertSame('content en update', $sport->getContent());

        $translations = $repo->findTranslations($sport);
        static::assertCount(3, $translations);

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

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::ARTICLE,
            self::TRANSLATION,
            self::COMMENT,
        ];
    }

    private function populate(): void
    {
        $repo = $this->em->getRepository(self::TRANSLATION);
        $sport = new Article();
        $sport->setTitle('Sport');
        $sport->setContent('about sport');

        $repo
            ->translate($sport, 'title', 'de_de', 'sport de')
            ->translate($sport, 'content', 'de_de', 'content de')
            ->translate($sport, 'title', 'ru_ru', 'sport ru')
            ->translate($sport, 'content', 'ru_ru', 'content ru')
        ;

        $this->em->persist($sport);
        $this->em->flush();
    }
}
