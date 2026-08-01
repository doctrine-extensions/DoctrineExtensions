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
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\Query;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Tests\Translatable\Fixture\Personal\Article;
use Gedmo\Tests\Translatable\Fixture\Personal\PersonalArticleTranslation;
use Gedmo\Translatable\Query\TreeWalker\TranslationWalker;
use Gedmo\Translatable\TranslatableListener;

/**
 * These are tests for translatable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class PersonalTranslationTest extends BaseTestCaseORM
{
    private TranslatableListener $translatableListener;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $this->translatableListener = new TranslatableListener();
        $this->translatableListener->setTranslatableLocale('en');
        $this->translatableListener->setDefaultLocale('en');
        $evm->addEventSubscriber($this->translatableListener);
        $this->getDefaultMockSqliteEntityManager($evm);
    }

    public function testShouldPersistDefaultLocaleTranslationIfRequired(): void
    {
        $this->translatableListener->setPersistDefaultLocaleTranslation(true);
        $this->populate();
        $article = $this->em->find(Article::class, ['id' => 1]);
        $translations = $article->getTranslations();
        static::assertCount(3, $translations);
    }

    public function testShouldCreateTranslations(): void
    {
        $this->populate();
        $article = $this->em->find(Article::class, ['id' => 1]);
        $translations = $article->getTranslations();
        static::assertCount(2, $translations);
    }

    public function testShouldTranslateTheRecord(): void
    {
        $this->populate();
        $this->translatableListener->setTranslatableLocale('lt');

        $this->queryLogger->reset();

        $article = $this->em->find(Article::class, ['id' => 1]);

        static::assertCount(2, $this->queryLogger->queries);

        static::assertSame([
            'message' => 'Executing statement: {sql} (parameters: {params}, types: {types})',
            'context' => [
                'sql' => 'SELECT t0.id AS id_1, t0.title AS title_2 FROM Article t0 WHERE t0.id = ?',
                'params' => [1 => 1],
                'types' => [1 => ParameterType::INTEGER],
            ],
        ], $this->queryLogger->queries[0]);

        static::assertSame([
            'message' => 'Executing statement: {sql} (parameters: {params}, types: {types})',
            'context' => [
                'sql' => 'SELECT t0.id AS id_1, t0.locale AS locale_2, t0.field AS field_3, t0.content AS content_4, t0.object_id AS object_id_5 FROM article_translations t0 WHERE t0.object_id = ?',
                'params' => [1 => 1],
                'types' => [1 => ParameterType::INTEGER],
            ],
        ], $this->queryLogger->queries[1]);

        static::assertSame('lt', $article->getTitle());
    }

    public function testShouldCascadeDeletionsByForeignKeyConstraints(): void
    {
        // Uses normalized comparison due to case differences between versions
        if ('doctrine\dbal\platforms\sqliteplatform' === strtolower(get_class($this->em->getConnection()->getDatabasePlatform()))) {
            static::markTestSkipped('Foreign key constraints do not map in SQLite.');
        }

        $this->populate();
        $this->em->createQuery('DELETE FROM '.Article::class.' a')->getSingleScalarResult();
        $trans = $this->em->getRepository(PersonalArticleTranslation::class)->findAll();

        static::assertCount(0, $trans);
    }

    public function testShouldOverrideTranslationInEntityBeingTranslated(): void
    {
        $this->translatableListener->setDefaultLocale('de');
        $article = new Article();
        $article->setTitle('override');

        $enTranslation = new PersonalArticleTranslation();
        $enTranslation
            ->setField('title')
            ->setContent('en')
            ->setObject($article)
            ->setLocale('en')
        ;
        $this->em->persist($enTranslation);
        $this->em->persist($article);
        $this->em->flush();

        $trans = $this->em->createQuery('SELECT t FROM '.PersonalArticleTranslation::class.' t')->getArrayResult();
        static::assertCount(1, $trans);
        static::assertSame('override', $trans[0]['content']);
    }

    /**
     * Covers issue #438
     */
    public function testShouldPersistDefaultLocaleValue(): void
    {
        $this->translatableListener->setTranslatableLocale('de');
        $article = new Article();
        $article->setTitle('de');

        $deTranslation = new PersonalArticleTranslation();
        $deTranslation
            ->setField('title')
            ->setContent('de')
            ->setObject($article)
            ->setLocale('de')
        ;
        $this->em->persist($deTranslation);

        $enTranslation = new PersonalArticleTranslation();
        $enTranslation
            ->setField('title')
            ->setContent('en')
            ->setObject($article)
            ->setLocale('en')
        ;
        $this->em->persist($enTranslation);

        $this->em->persist($article);
        $this->em->flush();

        $this->translatableListener->setTranslatableLocale('en');
        $articles = $this->em->createQuery('SELECT t FROM '.Article::class.' t')->getArrayResult();
        static::assertSame('en', $articles[0]['title']);
        $trans = $this->em->createQuery('SELECT t FROM '.PersonalArticleTranslation::class.' t')->getArrayResult();
        static::assertCount(2, $trans);
        foreach ($trans as $item) {
            static::assertSame($item['locale'], $item['content']);
        }
    }

    public function testShouldFindFromIdentityMap(): void
    {
        $article = new Article();
        $article->setTitle('en');

        $ltTranslation = new PersonalArticleTranslation();
        $ltTranslation
            ->setField('title')
            ->setContent('lt')
            ->setObject($article)
            ->setLocale('lt')
        ;
        $this->em->persist($ltTranslation);
        $this->em->persist($article);
        $this->em->flush();

        $this->queryLogger->reset();

        $this->translatableListener->setTranslatableLocale('lt');
        $article->setTitle('change lt');

        $this->em->persist($article);
        $this->em->flush();

        static::assertCount(3, $this->queryLogger->queries);

        static::assertSame([
            'message' => 'Beginning transaction',
            'context' => [],
        ], $this->queryLogger->queries[0]);

        static::assertSame([
            'message' => 'Executing statement: {sql} (parameters: {params}, types: {types})',
            'context' => [
                'sql' => 'UPDATE article_translations SET content = ? WHERE id = ?',
                'params' => [
                    1 => 'change lt',
                    2 => 1,
                ],
                'types' => [
                    1 => ParameterType::STRING,
                    2 => ParameterType::INTEGER,
                ],
            ],
        ], $this->queryLogger->queries[1]);

        static::assertSame([
            'message' => 'Committing transaction',
            'context' => [],
        ], $this->queryLogger->queries[2]);
    }

    public function testShouldBeAbleToUseTranslationQueryHint(): void
    {
        $this->populate();
        $dql = 'SELECT a.title FROM '.Article::class.' a';
        $query = $this
            ->em->createQuery($dql)
            ->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, TranslationWalker::class)
            ->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE, 'lt')
        ;

        $this->queryLogger->reset();

        $result = $query->getArrayResult();

        static::assertCount(1, $result);
        static::assertSame('lt', $result[0]['title']);

        static::assertCount(1, $this->queryLogger->queries);

        static::assertSame([
            'message' => 'Executing query: {sql}',
            'context' => [
                'sql' => "SELECT CAST(t1_.content AS VARCHAR(128)) AS title_0 FROM Article a0_ LEFT JOIN article_translations t1_ ON t1_.locale = 'lt' AND t1_.field = 'title' AND t1_.object_id = a0_.id",
            ],
        ], $this->queryLogger->queries[0]);
    }

    public function testShouldSyncDefaultLocalePersonalTranslationBackToEntityOnInsert(): void
    {
        $this->translatableListener->setPersistDefaultLocaleTranslation(true);
        $this->translatableListener->setPreferPersonalTranslationContent(true);

        $article = new Article();
        // intentionally do not set title; the default-locale personal translation should drive it
        $article->setTitle('');

        $enTranslation = new PersonalArticleTranslation();
        $enTranslation
            ->setField('title')
            ->setContent('Hello')
            ->setObject($article)
            ->setLocale('en')
        ;
        $this->em->persist($enTranslation);

        $deTranslation = new PersonalArticleTranslation();
        $deTranslation
            ->setField('title')
            ->setContent('Hallo')
            ->setObject($article)
            ->setLocale('de')
        ;
        $this->em->persist($deTranslation);

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();

        $reloaded = $this->em->find(Article::class, ['id' => $article->getId()]);
        static::assertSame('Hello', $reloaded->getTitle());

        $trans = $this->em->createQuery('SELECT t FROM '.PersonalArticleTranslation::class.' t')->getArrayResult();
        static::assertCount(2, $trans);
        $byLocale = [];
        foreach ($trans as $row) {
            $byLocale[$row['locale']] = $row['content'];
        }
        static::assertSame('Hello', $byLocale['en']);
        static::assertSame('Hallo', $byLocale['de']);
    }

    public function testShouldSyncDefaultLocalePersonalTranslationBackToEntityOnUpdate(): void
    {
        $this->translatableListener->setPersistDefaultLocaleTranslation(true);
        $this->translatableListener->setPreferPersonalTranslationContent(true);

        $article = new Article();
        $article->setTitle('original');

        $enTranslation = new PersonalArticleTranslation();
        $enTranslation
            ->setField('title')
            ->setContent('original')
            ->setObject($article)
            ->setLocale('en')
        ;
        $this->em->persist($enTranslation);

        $this->em->persist($article);
        $this->em->flush();
        $articleId = $article->getId();
        $this->em->clear();

        $reloaded = $this->em->find(Article::class, ['id' => $articleId]);
        $existingEnTranslation = null;
        foreach ($reloaded->getTranslations() as $t) {
            if ('en' === $t->getLocale() && 'title' === $t->getField()) {
                $existingEnTranslation = $t;

                break;
            }
        }
        static::assertNotNull($existingEnTranslation);

        // Modify only the personal translation content; leave the entity field untouched
        $existingEnTranslation->setContent('updated');
        $this->em->flush();
        $this->em->clear();

        $reloaded = $this->em->find(Article::class, ['id' => $articleId]);
        static::assertSame('updated', $reloaded->getTitle());

        $trans = $this->em->createQuery('SELECT t FROM '.PersonalArticleTranslation::class.' t')->getArrayResult();
        static::assertCount(1, $trans);
        static::assertSame('updated', $trans[0]['content']);
    }

    public function testShouldSyncOnInsertWhenWorkingInNonDefaultLocale(): void
    {
        // Admin UI is opened in a non-default locale and submits all personal
        // translations; the entity row's column must end up holding the default
        // locale's content, and each translation row must keep the content the
        // user authored (no clobbering from the entity field).
        $this->translatableListener->setPersistDefaultLocaleTranslation(true);
        $this->translatableListener->setPreferPersonalTranslationContent(true);
        $this->translatableListener->setTranslatableLocale('de');

        $article = new Article();
        $article->setTitle('');

        $en = new PersonalArticleTranslation();
        $en->setField('title')->setContent('Hello')->setObject($article)->setLocale('en');
        $this->em->persist($en);

        $de = new PersonalArticleTranslation();
        $de->setField('title')->setContent('Hallo')->setObject($article)->setLocale('de');
        $this->em->persist($de);

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();

        $rows = $this->em->createQuery('SELECT a.id, a.title FROM '.Article::class.' a')->getArrayResult();
        static::assertCount(1, $rows);
        static::assertSame('Hello', $rows[0]['title']);

        $trans = $this->em
            ->createQuery('SELECT t.locale, t.content FROM '.PersonalArticleTranslation::class.' t ORDER BY t.locale')
            ->getArrayResult();
        static::assertCount(2, $trans);
        static::assertSame(['locale' => 'de', 'content' => 'Hallo'], $trans[0]);
        static::assertSame(['locale' => 'en', 'content' => 'Hello'], $trans[1]);
    }

    public function testShouldSaveBothLocalesWhenUpdatingTranslationsInNonDefaultLocale(): void
    {
        // Default locale = 'en', listener (admin UI) locale = 'de'. User saves
        // edits for both the en and de personal translation rows in one go.
        // Both rows must be updated in the DB and the entity column must end
        // up with the default-locale content.
        $this->translatableListener->setPersistDefaultLocaleTranslation(true);
        $this->translatableListener->setPreferPersonalTranslationContent(true);

        $article = new Article();
        $article->setTitle('initial');

        $en = new PersonalArticleTranslation();
        $en->setField('title')->setContent('initial en')->setObject($article)->setLocale('en');
        $this->em->persist($en);

        $de = new PersonalArticleTranslation();
        $de->setField('title')->setContent('initial de')->setObject($article)->setLocale('de');
        $this->em->persist($de);

        $this->em->persist($article);
        $this->em->flush();
        $articleId = $article->getId();
        $this->em->clear();

        $this->translatableListener->setTranslatableLocale('de');
        $reloaded = $this->em->find(Article::class, ['id' => $articleId]);

        $enRow = null;
        $deRow = null;
        foreach ($reloaded->getTranslations() as $t) {
            if ('title' !== $t->getField()) {
                continue;
            }
            if ('en' === $t->getLocale()) {
                $enRow = $t;
            } elseif ('de' === $t->getLocale()) {
                $deRow = $t;
            }
        }
        static::assertNotNull($enRow);
        static::assertNotNull($deRow);
        $enRow->setContent('updated en');
        $deRow->setContent('updated de');

        $this->em->flush();
        $this->em->clear();

        $trans = $this->em
            ->createQuery('SELECT t.locale, t.content FROM '.PersonalArticleTranslation::class.' t ORDER BY t.locale')
            ->getArrayResult();
        static::assertSame([
            ['locale' => 'de', 'content' => 'updated de'],
            ['locale' => 'en', 'content' => 'updated en'],
        ], $trans);

        $rows = $this->em->createQuery('SELECT a.id, a.title FROM '.Article::class.' a')->getArrayResult();
        static::assertCount(1, $rows);
        static::assertSame('updated en', $rows[0]['title']);
    }

    public function testShouldPersistSyncedTitleToDatabaseOnUpdate(): void
    {
        // When only the personal translation in the default locale is
        // modified (entity itself untouched), the entity's column in the DB must
        // also be updated. Verified via getArrayResult to bypass postLoad, which
        // would otherwise mask a stale DB column by re-deriving the title from the
        // (already-updated) translation row.
        $this->translatableListener->setPersistDefaultLocaleTranslation(true);
        $this->translatableListener->setPreferPersonalTranslationContent(true);

        $article = new Article();
        $article->setTitle('original');

        $enTranslation = new PersonalArticleTranslation();
        $enTranslation
            ->setField('title')
            ->setContent('original')
            ->setObject($article)
            ->setLocale('en')
        ;
        $this->em->persist($enTranslation);
        $this->em->persist($article);
        $this->em->flush();
        $articleId = $article->getId();
        $this->em->clear();

        $reloaded = $this->em->find(Article::class, ['id' => $articleId]);
        $existingEnTranslation = null;
        foreach ($reloaded->getTranslations() as $t) {
            if ('en' === $t->getLocale() && 'title' === $t->getField()) {
                $existingEnTranslation = $t;

                break;
            }
        }
        static::assertNotNull($existingEnTranslation);

        $existingEnTranslation->setContent('updated');
        $this->em->flush();
        $this->em->clear();

        $rows = $this->em->createQuery('SELECT a.id, a.title FROM '.Article::class.' a')->getArrayResult();
        static::assertCount(1, $rows);
        static::assertSame('updated', $rows[0]['title']);
    }

    public function testShouldNotAffectBehaviorWhenFlagDisabled(): void
    {
        // Mirror of testShouldOverrideTranslationInEntityBeingTranslated, with the new flag explicitly off:
        // existing behavior must be preserved (entity field wins over the personal translation content).
        $this->translatableListener->setPreferPersonalTranslationContent(false);
        $this->translatableListener->setDefaultLocale('de');

        $article = new Article();
        $article->setTitle('override');

        $enTranslation = new PersonalArticleTranslation();
        $enTranslation
            ->setField('title')
            ->setContent('en')
            ->setObject($article)
            ->setLocale('en')
        ;
        $this->em->persist($enTranslation);
        $this->em->persist($article);
        $this->em->flush();

        $trans = $this->em->createQuery('SELECT t FROM '.PersonalArticleTranslation::class.' t')->getArrayResult();
        static::assertCount(1, $trans);
        static::assertSame('override', $trans[0]['content']);
    }

    public function testShouldNotApplyWithoutPersistDefaultLocaleTranslation(): void
    {
        // Flag on, but persistDefaultLocaleTranslation left off: behavior must be unchanged.
        $this->translatableListener->setPersistDefaultLocaleTranslation(false);
        $this->translatableListener->setPreferPersonalTranslationContent(true);

        $article = new Article();
        $article->setTitle('entity-wins');

        $enTranslation = new PersonalArticleTranslation();
        $enTranslation
            ->setField('title')
            ->setContent('translation-content')
            ->setObject($article)
            ->setLocale('en')
        ;
        $this->em->persist($enTranslation);
        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();

        $reloaded = $this->em->find(Article::class, ['id' => $article->getId()]);
        static::assertSame('entity-wins', $reloaded->getTitle());
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            Article::class,
            PersonalArticleTranslation::class,
        ];
    }

    private function populate(): void
    {
        $article = new Article();
        $article->setTitle('en');

        $this->em->persist($article);
        $this->em->flush();

        $this->translatableListener->setTranslatableLocale('de');
        $article->setTitle('de');

        $ltTranslation = new PersonalArticleTranslation();
        $ltTranslation
            ->setField('title')
            ->setContent('lt')
            ->setObject($article)
            ->setLocale('lt')
        ;
        $this->em->persist($ltTranslation);
        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();
    }
}
