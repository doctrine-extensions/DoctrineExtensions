<?php

namespace Gedmo\Tests\Translatable;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Query;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Tests\Translatable\Fixture\Article;
use Gedmo\Tests\Translatable\Fixture\Comment;
use Gedmo\Translatable\Entity\Translation;
use Gedmo\Translatable\Hydrator\ORM\ObjectHydrator;
use Gedmo\Translatable\Hydrator\ORM\SimpleObjectHydrator;
use Gedmo\Translatable\Query\TreeWalker\TranslationWalker;
use Gedmo\Translatable\TranslatableListener;

/**
 * These are tests for translation query walker
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class TranslationQueryWalkerTest extends BaseTestCaseORM
{
    public const ARTICLE = Article::class;
    public const COMMENT = Comment::class;
    public const TRANSLATION = Translation::class;

    public const TREE_WALKER_TRANSLATION = TranslationWalker::class;

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
        $this->populate();
    }

    /**
     * @test
     */
    public function shouldHandleQueryCache()
    {
        $cache = new \Doctrine\Common\Cache\ArrayCache();
        $this->em->getConfiguration()->setQueryCacheImpl($cache);
        $dql = 'SELECT a FROM '.self::ARTICLE.' a';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        // array hydration
        $this->translatableListener->setTranslatableLocale('en_us');
        $result = $q->getArrayResult();
        static::assertCount(1, $result);

        $q2 = clone $q;
        $q2->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);
        $result = $q->getArrayResult();
        static::assertCount(1, $result);
    }

    /**
     * @test
     */
    public function subselectByTranslatedField()
    {
        $this->populateMore();
        $dql = 'SELECT a FROM '.self::ARTICLE.' a';
        $subSelect = 'SELECT a2.title FROM '.self::ARTICLE.' a2';
        $subSelect .= " WHERE a2.title LIKE '%ab%'";
        $dql .= " WHERE a.title IN ({$subSelect})";
        $dql .= ' ORDER BY a.title';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        // array hydration
        $this->translatableListener->setTranslatableLocale('en_us');
        $result = $q->getArrayResult();
        static::assertCount(2, $result);
        static::assertSame('Alfabet', $result[0]['title']);
        static::assertSame('Cabbages', $result[1]['title']);
    }

    /**
     * @test
     */
    public function subselectStatements()
    {
        $this->populateMore();
        $dql = 'SELECT a FROM '.self::ARTICLE.' a';
        $subSelect = 'SELECT a2.id FROM '.self::ARTICLE.' a2';
        $subSelect .= " WHERE a2.title LIKE '%ab%'";
        $dql .= " WHERE a.id IN ({$subSelect})";
        $dql .= ' ORDER BY a.title';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        // array hydration
        $this->translatableListener->setTranslatableLocale('en_us');
        $result = $q->getArrayResult();
        static::assertCount(2, $result);
        static::assertSame('Alfabet', $result[0]['title']);
        static::assertSame('Cabbages', $result[1]['title']);
    }

    /**
     * @test
     */
    public function joinedWithStatements()
    {
        $this->populateMore();
        $dql = 'SELECT a, c FROM '.self::ARTICLE.' a';
        $dql .= ' LEFT JOIN a.comments c WITH c.subject LIKE :lookup';
        $dql .= ' WHERE a.title LIKE :filter';
        $dql .= ' ORDER BY a.title';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        // array hydration
        $this->translatableListener->setTranslatableLocale('en_us');
        $q->setParameter('lookup', '%goo%');
        $q->setParameter('filter', 'Foo%');
        $result = $q->getArrayResult();

        static::assertCount(1, $result);
        static::assertSame('Food', $result[0]['title']);
        $comments = $result[0]['comments'];
        static::assertCount(1, $comments);
        static::assertSame('good', $comments[0]['subject']);
    }

    /**
     * @test
     */
    public function shouldSelectWithTranslationFallbackOnSimpleObjectHydration()
    {
        $this->em->getConfiguration()->addCustomHydrationMode(
            TranslationWalker::HYDRATE_SIMPLE_OBJECT_TRANSLATION,
            SimpleObjectHydrator::class
        );

        $dql = 'SELECT a FROM '.self::ARTICLE.' a';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        $this->translatableListener->setTranslatableLocale('ru_ru');
        $this->translatableListener->setTranslationFallback(false);

        // simple object hydration
        $this->startQueryLog();
        $result = $q->getResult(Query::HYDRATE_SIMPLEOBJECT);
        static::assertSame(1, $this->queryAnalyzer->getNumExecutedQueries());
        static::assertNull($result[0]->getTitle());
        static::assertNull($result[0]->getContent());

        $this->translatableListener->setTranslationFallback(true);
        $this->queryAnalyzer->cleanUp();
        $result = $q->getResult(Query::HYDRATE_SIMPLEOBJECT);
        static::assertSame(1, $this->queryAnalyzer->getNumExecutedQueries());
        //Default translation is en_us, so we expect the results in that locale
        static::assertSame('Food', $result[0]->getTitle());
        static::assertSame('about food', $result[0]->getContent());
    }

    /**
     * @test
     */
    public function selectWithTranslationFallbackOnArrayHydration()
    {
        $dql = 'SELECT a, c FROM '.self::ARTICLE.' a';
        $dql .= ' LEFT JOIN a.comments c';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        $this->translatableListener->setTranslatableLocale('ru_ru');
        $this->translatableListener->setTranslationFallback(false);

        // array hydration
        $this->startQueryLog();
        $result = $q->getArrayResult();
        static::assertSame(1, $this->queryAnalyzer->getNumExecutedQueries());
        static::assertNull($result[0]['title']);
        static::assertNull($result[0]['content']);

        $this->translatableListener->setTranslationFallback(true);
        $this->queryAnalyzer->cleanUp();
        $result = $q->getArrayResult();
        static::assertSame(1, $this->queryAnalyzer->getNumExecutedQueries());
        //Default translation is en_us, so we expect the results in that locale
        static::assertSame('Food', $result[0]['title']);
        static::assertSame('about food', $result[0]['content']);
    }

    /**
     * @test
     */
    public function selectWithOptionalFallbackOnSimpleObjectHydration()
    {
        $this->em->getConfiguration()->addCustomHydrationMode(
            TranslationWalker::HYDRATE_SIMPLE_OBJECT_TRANSLATION,
            SimpleObjectHydrator::class
        );

        $dql = 'SELECT a FROM '.self::ARTICLE.' a';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        $this->translatableListener->setTranslatableLocale('ru_ru');
        $this->translatableListener->setTranslationFallback(false);

        // simple object hydration
        $this->startQueryLog();
        $result = $q->getResult(Query::HYDRATE_SIMPLEOBJECT);
        static::assertSame(1, $this->queryAnalyzer->getNumExecutedQueries());
        static::assertNull($result[0]->getTitle());
        static::assertSame('John Doe', $result[0]->getAuthor()); // optional fallback is true,  force fallback
        static::assertNull($result[0]->getViews());

        $this->translatableListener->setTranslationFallback(true);
        $this->queryAnalyzer->cleanUp();
        $result = $q->getResult(Query::HYDRATE_SIMPLEOBJECT);
        static::assertSame(1, $this->queryAnalyzer->getNumExecutedQueries());
        //Default translation is en_us, so we expect the results in that locale
        static::assertSame('Food', $result[0]->getTitle());
        static::assertSame('John Doe', $result[0]->getAuthor());
        static::assertNull($result[0]->getViews()); // optional fallback is false,  thus no translation required
    }

    /**
     * @test
     */
    public function shouldBeAbleToUseInnerJoinStrategyForTranslations()
    {
        $dql = 'SELECT a FROM '.self::ARTICLE.' a';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);
        $q->setHint(TranslatableListener::HINT_INNER_JOIN, true);

        $this->translatableListener->setTranslatableLocale('ru_ru');
        $this->translatableListener->setTranslationFallback(false);

        // array hydration
        $result = $q->getArrayResult();
        static::assertCount(0, $result);
    }

    /**
     * referres to issue #755
     *
     * @test
     */
    public function shouldBeAbleToOverrideTranslationFallbackByHint()
    {
        $this->translatableListener->setTranslatableLocale('lt_lt');
        $this->translatableListener->setTranslationFallback(false);

        $dql = 'SELECT a FROM '.self::ARTICLE.' a';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);
        $q->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE, 'undefined');
        $q->setHint(TranslatableListener::HINT_FALLBACK, true);

        // array hydration
        $result = $q->getArrayResult();
        static::assertCount(1, $result);
        static::assertSame('Food', $result[0]['title']);

        // fallback false hint
        $q->setHint(TranslatableListener::HINT_FALLBACK, false);

        // array hydration
        $result = $q->getArrayResult();
        static::assertCount(1, $result);
        static::assertNull($result[0]['title']);
    }

    /**
     * @test
     */
    public function shouldBeAbleToOverrideTranslatableLocale()
    {
        $dql = 'SELECT a FROM '.self::ARTICLE.' a';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);
        $q->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE, 'lt_lt');

        $this->translatableListener->setTranslatableLocale('ru_ru');
        $this->translatableListener->setTranslationFallback(false);

        // array hydration
        $result = $q->getArrayResult();
        static::assertCount(1, $result);
        static::assertSame('Maistas', $result[0]['title']);
    }

    /**
     * @test
     */
    public function shouldSelectWithTranslationFallbackOnObjectHydration()
    {
        $this->em->getConfiguration()->addCustomHydrationMode(
            TranslationWalker::HYDRATE_OBJECT_TRANSLATION,
            ObjectHydrator::class
        );

        $dql = 'SELECT a FROM '.self::ARTICLE.' a';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        $this->translatableListener->setTranslatableLocale('ru_ru');
        $this->translatableListener->setTranslationFallback(false);

        // object hydration
        $this->startQueryLog();
        $result = $q->getResult();
        static::assertSame(1, $this->queryAnalyzer->getNumExecutedQueries());
        static::assertNull($result[0]->getTitle());
        static::assertNull($result[0]->getContent());

        $this->translatableListener->setTranslationFallback(true);
        $this->queryAnalyzer->cleanUp();
        $result = $q->getResult();
        static::assertSame(1, $this->queryAnalyzer->getNumExecutedQueries());
        //Default translation is en_us, so we expect the results in that locale
        static::assertSame('Food', $result[0]->getTitle());
        static::assertSame('about food', $result[0]->getContent());

        // test fallback hint
        $this->translatableListener->setTranslationFallback(false);
        $q->setHint(TranslatableListener::HINT_FALLBACK, 1);

        $result = $q->getResult();
        //Default translation is en_us, so we expect the results in that locale
        static::assertSame('Food', $result[0]->getTitle());
        static::assertSame('about food', $result[0]->getContent());

        // test fallback hint
        $this->translatableListener->setTranslationFallback(true);
        $q->setHint(TranslatableListener::HINT_FALLBACK, 0);

        $result = $q->getResult();
        //Default translation is en_us, so we expect the results in that locale
        static::assertNull($result[0]->getTitle());
        static::assertNull($result[0]->getContent());
    }

    /**
     * @test
     */
    public function shouldSelectCountStatement()
    {
        $dql = 'SELECT COUNT(a) FROM '.self::ARTICLE.' a';
        $dql .= ' WHERE a.title LIKE :title';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        $this->translatableListener->setTranslatableLocale('en_us');
        $q->setParameter('title', 'Foo%');
        $result = $q->getSingleScalarResult();
        static::assertSame('1', $result);

        $this->translatableListener->setTranslatableLocale('lt_lt');
        $q->setParameter('title', 'Mai%');
        $result = $q->getSingleScalarResult();
        static::assertSame('1', $result);

        $this->translatableListener->setTranslatableLocale('en_us');
        $q->setParameter('title', 'Mai%');
        $result = $q->getSingleScalarResult();
        static::assertSame('0', $result);
    }

    /**
     * @test
     */
    public function shouldSelectOrderedJoinedComponentTranslation()
    {
        $this->em->getConfiguration()->addCustomHydrationMode(
            TranslationWalker::HYDRATE_OBJECT_TRANSLATION,
            ObjectHydrator::class
        );

        $this->populateMore();
        $dql = 'SELECT a, c FROM '.self::ARTICLE.' a';
        $dql .= ' LEFT JOIN a.comments c';
        $dql .= ' ORDER BY a.title';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        // array hydration
        $this->translatableListener->setTranslatableLocale('en_us');
        $result = $q->getArrayResult();
        static::assertCount(4, $result);
        static::assertSame('Alfabet', $result[0]['title']);
        static::assertSame('Cabbages', $result[1]['title']);
        static::assertSame('Food', $result[2]['title']);
        static::assertSame('Woman', $result[3]['title']);

        $this->translatableListener->setTranslatableLocale('lt_lt');
        $result = $q->getArrayResult();
        static::assertCount(4, $result);
        static::assertSame('Alfabetas', $result[0]['title']);
        static::assertSame('Kopustai', $result[1]['title']);
        static::assertSame('Maistas', $result[2]['title']);
        static::assertSame('Moteris', $result[3]['title']);

        // object hydration
        $this->translatableListener->setTranslatableLocale('en_us');
        $result = $q->getResult();
        static::assertCount(4, $result);
        static::assertSame('Alfabet', $result[0]->getTitle());
        static::assertSame('Cabbages', $result[1]->getTitle());
        static::assertSame('Food', $result[2]->getTitle());
        static::assertSame('Woman', $result[3]->getTitle());

        $this->translatableListener->setTranslatableLocale('lt_lt');
        $result = $q->getResult();
        static::assertCount(4, $result);
        static::assertSame('Alfabetas', $result[0]->getTitle());
        static::assertSame('Kopustai', $result[1]->getTitle());
        static::assertSame('Maistas', $result[2]->getTitle());
        static::assertSame('Moteris', $result[3]->getTitle());
    }

    /**
     * @test
     */
    public function shouldSelectOrderedByTranslatableInteger()
    {
        // Given
        $this->populateMore();
        $dql = 'SELECT a.title, a.views FROM '.self::ARTICLE.' a';
        $dql .= ' ORDER BY a.views';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        // Test original
        $this->translatableListener->setTranslatableLocale('en_us');
        $result = $q->getArrayResult();
        array_walk($result, static function ($value, $key) use (&$result) {
            // Make each record be a "Title - Views" string
            $result[$key] = implode(' - ', $value);
        });
        static::assertSame(
            ['Alfabet - 1', 'Food - 99', 'Cabbages - 2222', 'Woman - 3333'], $result,
            'Original of localizible integers should be sorted numerically'
        );

        $this->translatableListener->setTranslatableLocale('lt_lt');
        $result = $q->getArrayResult();
        array_walk($result, static function ($value, $key) use (&$result) {
            // Make each record be a "Title - Views" string
            $result[$key] = implode(' - ', $value);
        });
        static::assertSame(
            ['Moteris - 33', 'Alfabetas - 111', 'Maistas - 999', 'Kopustai - 22222'], $result,
            'Localized integers should be sorted numerically'
        );
    }

    /**
     * @test
     */
    public function shouldSelectSecondJoinedComponentTranslation()
    {
        $this->em->getConfiguration()->addCustomHydrationMode(
            TranslationWalker::HYDRATE_OBJECT_TRANSLATION,
            ObjectHydrator::class
        );

        $dql = 'SELECT a, c FROM '.self::ARTICLE.' a';
        $dql .= ' LEFT JOIN a.comments c ORDER BY c.id ASC';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        // array hydration
        $this->translatableListener->setTranslatableLocale('en_us');
        $result = $q->getArrayResult();
        static::assertCount(1, $result);
        $food = $result[0];
        static::assertCount(6, $food);
        static::assertSame('Food', $food['title']);
        static::assertSame('about food', $food['content']);
        $comments = $food['comments'];
        static::assertCount(2, $comments);
        $good = $comments[0];
        static::assertCount(3, $good);
        static::assertSame('good', $good['subject']);
        static::assertSame('food is good', $good['message']);
        $bad = $comments[1];
        static::assertCount(3, $bad);
        static::assertSame('bad', $bad['subject']);
        static::assertSame('food is bad', $bad['message']);

        $this->translatableListener->setTranslatableLocale('lt_lt');
        $result = $q->getArrayResult();
        static::assertCount(1, $result);
        $food = $result[0];
        static::assertCount(6, $food);
        static::assertSame('Maistas', $food['title']);
        static::assertSame('apie maista', $food['content']);
        $comments = $food['comments'];
        static::assertCount(2, $comments);
        $good = $comments[0];
        static::assertCount(3, $good);
        static::assertSame('geras', $good['subject']);
        static::assertSame('maistas yra geras', $good['message']);
        $bad = $comments[1];
        static::assertCount(3, $bad);
        static::assertSame('blogas', $bad['subject']);
        static::assertSame('maistas yra blogas', $bad['message']);

        // object hydration
        $this->translatableListener->setTranslatableLocale('en_us');
        $result = $q->getResult();
        static::assertCount(1, $result);
        $food = $result[0];
        static::assertSame('Food', $food->getTitle());
        static::assertSame('about food', $food->getContent());
        $comments = $food->getComments();
        static::assertCount(2, $comments);
        $good = $comments[0];
        static::assertSame('good', $good->getSubject());
        static::assertSame('food is good', $good->getMessage());
        $bad = $comments[1];
        static::assertSame('bad', $bad->getSubject());
        static::assertSame('food is bad', $bad->getMessage());

        $this->translatableListener->setTranslatableLocale('lt_lt');
        $result = $q->getResult();
        static::assertCount(1, $result);
        $food = $result[0];
        static::assertSame('Maistas', $food->getTitle());
        static::assertSame('apie maista', $food->getContent());
        $comments = $food->getComments();
        static::assertCount(2, $comments);
        $good = $comments[0];
        static::assertInstanceOf(self::COMMENT, $good);
        static::assertSame('geras', $good->getSubject());
        static::assertSame('maistas yra geras', $good->getMessage());
        $bad = $comments[1];
        static::assertSame('blogas', $bad->getSubject());
        static::assertSame('maistas yra blogas', $bad->getMessage());
    }

    /**
     * @test
     */
    public function shouldSelectSinglePartializedComponentTranslation()
    {
        $this->em->getConfiguration()->addCustomHydrationMode(
            TranslationWalker::HYDRATE_OBJECT_TRANSLATION,
            ObjectHydrator::class
        );

        $dql = 'SELECT a.title FROM '.self::ARTICLE.' a';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        // array hydration
        $this->translatableListener->setTranslatableLocale('en_us');
        $result = $q->getArrayResult();
        static::assertCount(1, $result);
        $food = $result[0];
        static::assertCount(1, $food);
        static::assertSame('Food', $food['title']);
        $this->translatableListener->setTranslatableLocale('lt_lt');
        $result = $q->getArrayResult();
        static::assertCount(1, $result);
        $food = $result[0];
        static::assertCount(1, $food);
        static::assertSame('Maistas', $food['title']);

        // object hydration
        $this->translatableListener->setTranslatableLocale('en_us');
        $result = $q->getResult();
        static::assertCount(1, $result);
        $food = $result[0];
        static::assertCount(1, $food);
        static::assertSame('Food', $food['title']);
        $this->translatableListener->setTranslatableLocale('lt_lt');
        $result = $q->getResult();
        static::assertCount(1, $result);
        $food = $result[0];
        static::assertCount(1, $food);
        static::assertSame('Maistas', $food['title']);
    }

    /**
     * @test
     */
    public function shouldSelectSingleComponentTranslation()
    {
        $this->em->getConfiguration()->addCustomHydrationMode(
            TranslationWalker::HYDRATE_OBJECT_TRANSLATION,
            ObjectHydrator::class
        );

        $dql = 'SELECT a FROM '.self::ARTICLE.' a';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        // array hydration
        $this->translatableListener->setTranslatableLocale('en_us');
        $result = $q->getArrayResult();
        static::assertCount(1, $result);
        $food = $result[0];
        static::assertCount(5, $food);
        static::assertSame('Food', $food['title']);
        static::assertSame('about food', $food['content']);
        $this->translatableListener->setTranslatableLocale('lt_lt');
        $result = $q->getArrayResult();
        static::assertCount(1, $result);
        $food = $result[0];
        static::assertCount(5, $food);
        static::assertSame('Maistas', $food['title']);
        static::assertSame('apie maista', $food['content']);

        // object hydration
        $this->translatableListener->setTranslatableLocale('en_us');
        $result = $q->getResult();
        static::assertCount(1, $result);
        $food = $result[0];
        static::assertInstanceOf(self::ARTICLE, $food);
        static::assertSame('Food', $food->getTitle());
        static::assertSame('about food', $food->getContent());

        $this->translatableListener->setTranslatableLocale('lt_lt');
        $result = $q->getResult();
        static::assertCount(1, $result);
        $food = $result[0];
        static::assertSame('Maistas', $food->getTitle());
        static::assertSame('apie maista', $food->getContent());
    }

    /**
     * @test
     * @group testSelectWithUnmappedField
     */
    public function shouldSelectWithUnmappedField()
    {
        $dql = 'SELECT a.title, count(a.id) AS num FROM '.self::ARTICLE.' a';
        $dql .= ' ORDER BY a.title';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        // array hydration
        $this->translatableListener->setTranslatableLocale('en_us');
        $result = $q->getArrayResult();
        static::assertCount(1, $result);
        static::assertSame('Food', $result[0]['title']);
        static::assertSame(1, $result[0]['num']);
    }

    /**
     * @test
     */
    public function shouldPreserveSkipOnLoadForSimpleHydrator()
    {
        $this->em->getConfiguration()->addCustomHydrationMode(
            TranslationWalker::HYDRATE_SIMPLE_OBJECT_TRANSLATION,
            SimpleObjectHydrator::class
        );
        $dql = 'SELECT a FROM '.self::ARTICLE.' a';
        $dql .= ' ORDER BY a.title';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        // array hydration
        $this->translatableListener->setTranslatableLocale('en_us');
        $this->translatableListener->setSkipOnLoad(true);
        $q->getResult(Query::HYDRATE_SIMPLEOBJECT);

        static::assertTrue($this->translatableListener->isSkipOnLoad());
    }

    /**
     * @test
     */
    public function shouldPreserveSkipOnLoadForObjectHydrator()
    {
        $this->em->getConfiguration()->addCustomHydrationMode(
            TranslationWalker::HYDRATE_OBJECT_TRANSLATION,
            ObjectHydrator::class
        );
        $dql = 'SELECT a FROM '.self::ARTICLE.' a';
        $dql .= ' ORDER BY a.title';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        // array hydration
        $this->translatableListener->setTranslatableLocale('en_us');
        $this->translatableListener->setSkipOnLoad(true);
        $q->getResult(Query::HYDRATE_OBJECT);

        static::assertTrue($this->translatableListener->isSkipOnLoad());
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::ARTICLE,
            self::TRANSLATION,
            self::COMMENT,
        ];
    }

    private function populateMore()
    {
        $repo = $this->em->getRepository(self::ARTICLE);
        $commentRepo = $this->em->getRepository(self::COMMENT);

        $this->translatableListener->setTranslatableLocale('en_us');
        $alfabet = new Article();
        $alfabet->setTitle('Alfabet');
        $alfabet->setContent('hey wtf!');
        $alfabet->setViews(1);

        $woman = new Article();
        $woman->setTitle('Woman');
        $woman->setContent('i like them');
        $woman->setViews(3333);

        $cabbages = new Article();
        $cabbages->setTitle('Cabbages');
        $cabbages->setContent('where went the woman?');
        $cabbages->setViews(2222);

        $this->em->persist($alfabet);
        $this->em->persist($woman);
        $this->em->persist($cabbages);
        $this->em->flush();
        $this->em->clear();

        $this->translatableListener->setTranslatableLocale('lt_lt');
        $alfabet = $repo->find(2);
        $alfabet->setTitle('Alfabetas');
        $alfabet->setContent('ei wtf!');
        $alfabet->setViews(111);

        $woman = $repo->find(3);
        $woman->setTitle('Moteris');
        $woman->setContent('as megstu jas');
        $woman->setViews(33);

        $cabbages = $repo->find(4);
        $cabbages->setTitle('Kopustai');
        $cabbages->setContent('kur dingo moteris?');
        $cabbages->setViews(22222);

        $this->em->persist($alfabet);
        $this->em->persist($woman);
        $this->em->persist($cabbages);
        $this->em->flush();
        $this->em->clear();
    }

    private function populate()
    {
        $repo = $this->em->getRepository(self::ARTICLE);
        $commentRepo = $this->em->getRepository(self::COMMENT);

        $food = new Article();
        $food->setTitle('Food');
        $food->setContent('about food');
        $food->setAuthor('John Doe');
        $food->setViews(99);

        $goodFood = new Comment();
        $goodFood->setArticle($food);
        $goodFood->setMessage('food is good');
        $goodFood->setSubject('good');

        $badFood = new Comment();
        $badFood->setArticle($food);
        $badFood->setMessage('food is bad');
        $badFood->setSubject('bad');

        $this->em->persist($food);
        $this->em->persist($goodFood);
        $this->em->persist($badFood);
        $this->em->flush();
        $this->em->clear();

        $this->translatableListener->setTranslatableLocale('lt_lt');
        $food = $repo->find(1);
        $food->setTitle('Maistas');
        $food->setContent('apie maista');
        $food->setViews(999);

        $goodFood = $commentRepo->find(1);
        $goodFood->setArticle($food);
        $goodFood->setMessage('maistas yra geras');
        $goodFood->setSubject('geras');

        $badFood = $commentRepo->find(2);
        $badFood->setArticle($food);
        $badFood->setMessage('maistas yra blogas');
        $badFood->setSubject('blogas');

        $this->em->persist($food);
        $this->em->persist($goodFood);
        $this->em->persist($badFood);
        $this->em->flush();
        $this->em->clear();
    }
}
