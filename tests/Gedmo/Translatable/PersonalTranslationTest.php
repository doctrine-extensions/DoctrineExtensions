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
    private const ARTICLE = Article::class;
    private const TRANSLATION = PersonalArticleTranslation::class;
    private const TREE_WALKER_TRANSLATION = TranslationWalker::class;

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
        $article = $this->em->find(self::ARTICLE, ['id' => 1]);
        $translations = $article->getTranslations();
        static::assertCount(3, $translations);
    }

    public function testShouldCreateTranslations(): void
    {
        $this->populate();
        $article = $this->em->find(self::ARTICLE, ['id' => 1]);
        $translations = $article->getTranslations();
        static::assertCount(2, $translations);
    }

    public function testShouldTranslateTheRecord(): void
    {
        $this->populate();
        $this->translatableListener->setTranslatableLocale('lt');

        $this->queryLogger->reset();

        $article = $this->em->find(self::ARTICLE, ['id' => 1]);

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
        if ('sqlite' === $this->em->getConnection()->getDatabasePlatform()->getName()) {
            static::markTestSkipped('Foreign key constraints does not map in sqlite.');
        }
        $this->populate();
        $this->em->createQuery('DELETE FROM '.self::ARTICLE.' a')->getSingleScalarResult();
        $trans = $this->em->getRepository(self::TRANSLATION)->findAll();

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

        $trans = $this->em->createQuery('SELECT t FROM '.self::TRANSLATION.' t')->getArrayResult();
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
        $articles = $this->em->createQuery('SELECT t FROM '.self::ARTICLE.' t')->getArrayResult();
        static::assertSame('en', $articles[0]['title']);
        $trans = $this->em->createQuery('SELECT t FROM '.self::TRANSLATION.' t')->getArrayResult();
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
        $dql = 'SELECT a.title FROM '.self::ARTICLE.' a';
        $query = $this
            ->em->createQuery($dql)
            ->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION)
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

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::ARTICLE,
            self::TRANSLATION,
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
