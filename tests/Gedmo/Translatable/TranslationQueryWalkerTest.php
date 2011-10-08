<?php

namespace Gedmo\Translatable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Doctrine\ORM\Query;
use Gedmo\Translatable\Query\TreeWalker\TranslationWalker;
use Translatable\Fixture\Article;
use Translatable\Fixture\Comment;

/**
 * These are tests for translation query walker
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Translatable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TranslationQueryWalkerTest extends BaseTestCaseORM
{
    const ARTICLE = 'Translatable\\Fixture\\Article';
    const COMMENT = 'Translatable\\Fixture\\Comment';
    const TRANSLATION = 'Gedmo\\Translatable\\Entity\\Translation';

    const TREE_WALKER_TRANSLATION = 'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker';

    private $translationListener;

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $this->translationListener = new TranslationListener();
        $this->translationListener->setTranslatableLocale('en_us');
        $this->translationListener->setDefaultLocale('en_us');
        $evm->addEventSubscriber($this->translationListener);

        $this->getMockSqliteEntityManager($evm);
        $this->populate();
    }

    public function testSubselectByTranslatedField()
    {
        $this->populateMore();
        $dql = 'SELECT a FROM ' . self::ARTICLE . ' a';
        $subSelect = 'SELECT a2.title FROM ' . self::ARTICLE . ' a2';
        $subSelect .= " WHERE a2.title LIKE '%ab%'";
        $dql .= " WHERE a.title IN ({$subSelect})";
        $dql .= ' ORDER BY a.title';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        // array hydration
        $this->translationListener->setTranslatableLocale('en_us');
        $result = $q->getArrayResult();
        $this->assertEquals(2, count($result));
        $this->assertEquals('Alfabet', $result[0]['title']);
        $this->assertEquals('Cabbages', $result[1]['title']);
    }

    public function testSubselectStatements()
    {
        $this->populateMore();
        $dql = 'SELECT a FROM ' . self::ARTICLE . ' a';
        $subSelect = 'SELECT a2.id FROM ' . self::ARTICLE . ' a2';
        $subSelect .= " WHERE a2.title LIKE '%ab%'";
        $dql .= " WHERE a.id IN ({$subSelect})";
        $dql .= ' ORDER BY a.title';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        // array hydration
        $this->translationListener->setTranslatableLocale('en_us');
        $result = $q->getArrayResult();
        $this->assertEquals(2, count($result));
        $this->assertEquals('Alfabet', $result[0]['title']);
        $this->assertEquals('Cabbages', $result[1]['title']);
    }

    public function testJoinedWithStatements()
    {
        $this->populateMore();
        $dql = 'SELECT a, c FROM ' . self::ARTICLE . ' a';
        $dql .= ' LEFT JOIN a.comments c WITH c.subject LIKE :lookup';
        $dql .= ' WHERE a.title LIKE :filter';
        $dql .= ' ORDER BY a.title';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        // array hydration
        $this->translationListener->setTranslatableLocale('en_us');
        $q->setParameter('lookup', '%goo%');
        $q->setParameter('filter', 'Foo%');
        $result = $q->getArrayResult();

        $this->assertEquals(1, count($result));
        $this->assertEquals('Food', $result[0]['title']);
        $comments = $result[0]['comments'];
        $this->assertEquals(1, count($comments));
        $this->assertEquals('good', $comments[0]['subject']);
    }

    public function testSelectWithTranslationFallbackOnSimpleObjectHydration()
    {
        $this->em
            ->getConfiguration()
            ->expects($this->any())
            ->method('getCustomHydrationMode')
            ->with(TranslationWalker::HYDRATE_SIMPLE_OBJECT_TRANSLATION)
            ->will($this->returnValue('Gedmo\\Translatable\\Hydrator\\ORM\\SimpleObjectHydrator'));

        $dql = 'SELECT a FROM ' . self::ARTICLE . ' a';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        $this->translationListener->setTranslatableLocale('ru_ru');
        $this->translationListener->setTranslationFallback(false);

        // simple object hydration
        $this->startQueryLog();
        $result = $q->getResult(Query::HYDRATE_SIMPLEOBJECT);
        $this->assertEquals(1, $this->queryAnalyzer->getNumExecutedQueries());
        $this->assertEquals('', $result[0]->getTitle());
        $this->assertEquals('', $result[0]->getContent());

        $this->translationListener->setTranslationFallback(true);
        $this->queryAnalyzer->cleanUp();
        $result = $q->getResult(Query::HYDRATE_SIMPLEOBJECT);
        $this->assertEquals(1, $this->queryAnalyzer->getNumExecutedQueries());
        //Default translation is en_us, so we expect the results in that locale
        $this->assertEquals('Food', $result[0]->getTitle());
        $this->assertEquals('about food', $result[0]->getContent());
    }

    public function testSelectWithTranslationFallbackOnArrayHydration()
    {
        $dql = 'SELECT a, c FROM ' . self::ARTICLE . ' a';
        $dql .= ' LEFT JOIN a.comments c';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        $this->translationListener->setTranslatableLocale('ru_ru');
        $this->translationListener->setTranslationFallback(false);

        // array hydration
        $this->startQueryLog();
        $result = $q->getArrayResult();
        $this->assertEquals(1, $this->queryAnalyzer->getNumExecutedQueries());
        $this->assertEquals('', $result[0]['title']);
        $this->assertEquals('', $result[0]['content']);

        $this->translationListener->setTranslationFallback(true);
        $this->queryAnalyzer->cleanUp();
        $result = $q->getArrayResult();
        $this->assertEquals(1, $this->queryAnalyzer->getNumExecutedQueries());
        //Default translation is en_us, so we expect the results in that locale
        $this->assertEquals('Food', $result[0]['title']);
        $this->assertEquals('about food', $result[0]['content']);
    }

    public function testSelectWithTranslationFallbackOnObjectHydration()
    {
        $this->em
            ->getConfiguration()
            ->expects($this->any())
            ->method('getCustomHydrationMode')
            ->with(TranslationWalker::HYDRATE_OBJECT_TRANSLATION)
            ->will($this->returnValue('Gedmo\\Translatable\\Hydrator\\ORM\\ObjectHydrator'));

        $dql = 'SELECT a FROM ' . self::ARTICLE . ' a';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        $this->translationListener->setTranslatableLocale('ru_ru');
        $this->translationListener->setTranslationFallback(false);

        // object hydration
        $this->startQueryLog();
        $result = $q->getResult();
        $this->assertEquals(1, $this->queryAnalyzer->getNumExecutedQueries());
        $this->assertEquals('', $result[0]->getTitle());
        $this->assertEquals('', $result[0]->getContent());

        $this->translationListener->setTranslationFallback(true);
        $this->queryAnalyzer->cleanUp();
        $result = $q->getResult();
        $this->assertEquals(1, $this->queryAnalyzer->getNumExecutedQueries());
        //Default translation is en_us, so we expect the results in that locale
        $this->assertEquals('Food', $result[0]->getTitle());
        $this->assertEquals('about food', $result[0]->getContent());
    }

    public function testSelectCountStatement()
    {
        $dql = 'SELECT COUNT(a) FROM ' . self::ARTICLE . ' a';
        $dql .= ' WHERE a.title LIKE :title';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        $this->translationListener->setTranslatableLocale('en_us');
        $q->setParameter('title', 'Foo%');
        $result = $q->getSingleScalarResult();
        $this->assertEquals(1, $result);

        $this->translationListener->setTranslatableLocale('lt_lt');
        $q->setParameter('title', 'Mai%');
        $result = $q->getSingleScalarResult();
        $this->assertEquals(1, $result);

        $this->translationListener->setTranslatableLocale('en_us');
        $q->setParameter('title', 'Mai%');
        $result = $q->getSingleScalarResult();
        $this->assertEquals(0, $result);
    }

    public function testSelectOrderedJoinedComponentTranslation()
    {
        $this->em
            ->getConfiguration()
            ->expects($this->any())
            ->method('getCustomHydrationMode')
            ->with(TranslationWalker::HYDRATE_OBJECT_TRANSLATION)
            ->will($this->returnValue('Gedmo\\Translatable\\Hydrator\\ORM\\ObjectHydrator'));

        $this->populateMore();
        $dql = 'SELECT a, c FROM ' . self::ARTICLE . ' a';
        $dql .= ' LEFT JOIN a.comments c';
        $dql .= ' ORDER BY a.title';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        // array hydration
        $this->translationListener->setTranslatableLocale('en_us');
        $result = $q->getArrayResult();
        $this->assertEquals(4, count($result));
        $this->assertEquals('Alfabet', $result[0]['title']);
        $this->assertEquals('Cabbages', $result[1]['title']);
        $this->assertEquals('Food', $result[2]['title']);
        $this->assertEquals('Woman', $result[3]['title']);

        $this->translationListener->setTranslatableLocale('lt_lt');
        $result = $q->getArrayResult();
        $this->assertEquals(4, count($result));
        $this->assertEquals('Alfabetas', $result[0]['title']);
        $this->assertEquals('Kopustai', $result[1]['title']);
        $this->assertEquals('Maistas', $result[2]['title']);
        $this->assertEquals('Moteris', $result[3]['title']);

        // object hydration
        $this->translationListener->setTranslatableLocale('en_us');
        $result = $q->getResult();
        $this->assertEquals(4, count($result));
        $this->assertEquals('Alfabet', $result[0]->getTitle());
        $this->assertEquals('Cabbages', $result[1]->getTitle());
        $this->assertEquals('Food', $result[2]->getTitle());
        $this->assertEquals('Woman', $result[3]->getTitle());

        $this->translationListener->setTranslatableLocale('lt_lt');
        $result = $q->getResult();
        $this->assertEquals(4, count($result));
        $this->assertEquals('Alfabetas', $result[0]->getTitle());
        $this->assertEquals('Kopustai', $result[1]->getTitle());
        $this->assertEquals('Maistas', $result[2]->getTitle());
        $this->assertEquals('Moteris', $result[3]->getTitle());
    }

    public function testSelectSecondJoinedComponentTranslation()
    {
        $this->em
            ->getConfiguration()
            ->expects($this->any())
            ->method('getCustomHydrationMode')
            ->with(TranslationWalker::HYDRATE_OBJECT_TRANSLATION)
            ->will($this->returnValue('Gedmo\\Translatable\\Hydrator\\ORM\\ObjectHydrator'));

        $dql = 'SELECT a, c FROM ' . self::ARTICLE . ' a';
        $dql .= ' LEFT JOIN a.comments c';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        // array hydration
        $this->translationListener->setTranslatableLocale('en_us');
        $result = $q->getArrayResult();
        $this->assertEquals(1, count($result));
        $food = $result[0];
        $this->assertEquals(4, count($food));
        $this->assertEquals('Food', $food['title']);
        $this->assertEquals('about food', $food['content']);
        $comments = $food['comments'];
        $this->assertEquals(2, count($comments));
        $good = $comments[0];
        $this->assertEquals(3, count($good));
        $this->assertEquals('good', $good['subject']);
        $this->assertEquals('food is good', $good['message']);
        $bad = $comments[1];
        $this->assertEquals(3, count($bad));
        $this->assertEquals('bad', $bad['subject']);
        $this->assertEquals('food is bad', $bad['message']);

        $this->translationListener->setTranslatableLocale('lt_lt');
        $result = $q->getArrayResult();
        $this->assertEquals(1, count($result));
        $food = $result[0];
        $this->assertEquals(4, count($food));
        $this->assertEquals('Maistas', $food['title']);
        $this->assertEquals('apie maista', $food['content']);
        $comments = $food['comments'];
        $this->assertEquals(2, count($comments));
        $good = $comments[0];
        $this->assertEquals(3, count($good));
        $this->assertEquals('geras', $good['subject']);
        $this->assertEquals('maistas yra geras', $good['message']);
        $bad = $comments[1];
        $this->assertEquals(3, count($bad));
        $this->assertEquals('blogas', $bad['subject']);
        $this->assertEquals('maistas yra blogas', $bad['message']);

        // object hydration
        $this->translationListener->setTranslatableLocale('en_us');
        $result = $q->getResult();
        $this->assertEquals(1, count($result));
        $food = $result[0];
        $this->assertEquals('Food', $food->getTitle());
        $this->assertEquals('about food', $food->getContent());
        $comments = $food->getComments();
        $this->assertEquals(2, count($comments));
        $good = $comments[0];
        $this->assertEquals('good', $good->getSubject());
        $this->assertEquals('food is good', $good->getMessage());
        $bad = $comments[1];
        $this->assertEquals('bad', $bad->getSubject());
        $this->assertEquals('food is bad', $bad->getMessage());

        $this->translationListener->setTranslatableLocale('lt_lt');
        $result = $q->getResult();
        $this->assertEquals(1, count($result));
        $food = $result[0];
        $this->assertEquals('Maistas', $food->getTitle());
        $this->assertEquals('apie maista', $food->getContent());
        $comments = $food->getComments();
        $this->assertEquals(2, count($comments));
        $good = $comments[0];
        $this->assertInstanceOf(self::COMMENT, $good);
        $this->assertEquals('geras', $good->getSubject());
        $this->assertEquals('maistas yra geras', $good->getMessage());
        $bad = $comments[1];
        $this->assertEquals('blogas', $bad->getSubject());
        $this->assertEquals('maistas yra blogas', $bad->getMessage());
    }

    public function testSelectSinglePartializedComponentTranslation()
    {
        $this->em
            ->getConfiguration()
            ->expects($this->any())
            ->method('getCustomHydrationMode')
            ->with(TranslationWalker::HYDRATE_OBJECT_TRANSLATION)
            ->will($this->returnValue('Gedmo\\Translatable\\Hydrator\\ORM\\ObjectHydrator'));

        $dql = 'SELECT a.title FROM ' . self::ARTICLE . ' a';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        // array hydration
        $this->translationListener->setTranslatableLocale('en_us');
        $result = $q->getArrayResult();
        $this->assertEquals(1, count($result));
        $food = $result[0];
        $this->assertEquals(1, count($food));
        $this->assertEquals('Food', $food['title']);
        $this->translationListener->setTranslatableLocale('lt_lt');
        $result = $q->getArrayResult();
        $this->assertEquals(1, count($result));
        $food = $result[0];
        $this->assertEquals(1, count($food));
        $this->assertEquals('Maistas', $food['title']);

        // object hydration
        $this->translationListener->setTranslatableLocale('en_us');
        $result = $q->getResult();
        $this->assertEquals(1, count($result));
        $food = $result[0];
        $this->assertEquals(1, count($food));
        $this->assertEquals('Food', $food['title']);
        $this->translationListener->setTranslatableLocale('lt_lt');
        $result = $q->getResult();
        $this->assertEquals(1, count($result));
        $food = $result[0];
        $this->assertEquals(1, count($food));
        $this->assertEquals('Maistas', $food['title']);
    }

    public function testSelectSingleComponentTranslation()
    {
        $this->em
            ->getConfiguration()
            ->expects($this->any())
            ->method('getCustomHydrationMode')
            ->with(TranslationWalker::HYDRATE_OBJECT_TRANSLATION)
            ->will($this->returnValue('Gedmo\\Translatable\\Hydrator\\ORM\\ObjectHydrator'));

        $dql = 'SELECT a FROM ' . self::ARTICLE . ' a';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        // array hydration
        $this->translationListener->setTranslatableLocale('en_us');
        $result = $q->getArrayResult();
        $this->assertEquals(1, count($result));
        $food = $result[0];
        $this->assertEquals(3, count($food));
        $this->assertEquals('Food', $food['title']);
        $this->assertEquals('about food', $food['content']);
        $this->translationListener->setTranslatableLocale('lt_lt');
        $result = $q->getArrayResult();
        $this->assertEquals(1, count($result));
        $food = $result[0];
        $this->assertEquals(3, count($food));
        $this->assertEquals('Maistas', $food['title']);
        $this->assertEquals('apie maista', $food['content']);

        // object hydration
        $this->translationListener->setTranslatableLocale('en_us');
        $result = $q->getResult();
        $this->assertEquals(1, count($result));
        $food = $result[0];
        $this->assertInstanceOf(self::ARTICLE, $food);
        $this->assertEquals('Food', $food->getTitle());
        $this->assertEquals('about food', $food->getContent());

        $this->translationListener->setTranslatableLocale('lt_lt');
        $result = $q->getResult();
        $this->assertEquals(1, count($result));
        $food = $result[0];
        $this->assertEquals('Maistas', $food->getTitle());
        $this->assertEquals('apie maista', $food->getContent());
    }

    /**
     * @group testSelectWithUnmappedField
     */
    public function testSelectWithUnmappedField()
    {
        $dql = 'SELECT a.title, count(a.id) AS num FROM ' . self::ARTICLE . ' a';
        $dql .= ' ORDER BY a.title';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        // array hydration
        $this->translationListener->setTranslatableLocale('en_us');
        $result = $q->getArrayResult();
        $this->assertEquals(1, count($result));
        $this->assertEquals('Food', $result[0]['title']);
        $this->assertEquals(1, $result[0]['num']);
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::ARTICLE,
            self::TRANSLATION,
            self::COMMENT
        );
    }

    private function populateMore()
    {
        $repo = $this->em->getRepository(self::ARTICLE);
        $commentRepo = $this->em->getRepository(self::COMMENT);

        $this->translationListener->setTranslatableLocale('en_us');
        $alfabet = new Article;
        $alfabet->setTitle('Alfabet');
        $alfabet->setContent('hey wtf!');

        $woman = new Article;
        $woman->setTitle('Woman');
        $woman->setContent('i like them');

        $cabbages = new Article;
        $cabbages->setTitle('Cabbages');
        $cabbages->setContent('where went the woman?');

        $this->em->persist($alfabet);
        $this->em->persist($woman);
        $this->em->persist($cabbages);
        $this->em->flush();
        $this->em->clear();

        $this->translationListener->setTranslatableLocale('lt_lt');
        $alfabet = $repo->find(2);
        $alfabet->setTitle('Alfabetas');
        $alfabet->setContent('ei wtf!');

        $woman = $repo->find(3);
        $woman->setTitle('Moteris');
        $woman->setContent('as megstu jas');

        $cabbages = $repo->find(4);
        $cabbages->setTitle('Kopustai');
        $cabbages->setContent('kur dingo moteris?');

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

        $food = new Article;
        $food->setTitle('Food');
        $food->setContent('about food');

        $goodFood = new Comment;
        $goodFood->setArticle($food);
        $goodFood->setMessage('food is good');
        $goodFood->setSubject('good');

        $badFood = new Comment;
        $badFood->setArticle($food);
        $badFood->setMessage('food is bad');
        $badFood->setSubject('bad');

        $this->em->persist($food);
        $this->em->persist($goodFood);
        $this->em->persist($badFood);
        $this->em->flush();
        $this->em->clear();

        $this->translationListener->setTranslatableLocale('lt_lt');
        $food = $repo->find(1);
        $food->setTitle('Maistas');
        $food->setContent('apie maista');

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
