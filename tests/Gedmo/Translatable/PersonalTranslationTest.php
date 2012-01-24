<?php

namespace Gedmo\Translatable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Translatable\Fixture\Personal\Article;
use Translatable\Fixture\Personal\PersonalArticleTranslation;

/**
 * These are tests for translatable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Translatable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class PersonalTranslationTest extends BaseTestCaseORM
{
    const ARTICLE = 'Translatable\Fixture\Personal\Article';
    const TRANSLATION = 'Translatable\Fixture\Personal\PersonalArticleTranslation';

    private $translatableListener;

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $this->translatableListener = new TranslatableListener();
        $this->translatableListener->setTranslatableLocale('en');
        $this->translatableListener->setDefaultLocale('en');
        $evm->addEventSubscriber($this->translatableListener);

        $conn = array(
            'driver' => 'pdo_mysql',
            'host' => '127.0.0.1',
            'dbname' => 'test',
            'user' => 'root',
            'password' => 'nimda'
        );
        //$this->getMockCustomEntityManager($conn, $evm);
        $this->getMockSqliteEntityManager($evm);
    }

    /**
     * @test
     */
    function shouldCreateTranslations()
    {
        $this->populate();
        $article = $this->em->find(self::ARTICLE, array('id' => 1));
        $translations = $article->getTranslations();
        $this->assertEquals(2, count($translations));
    }

    /**
     * @test
     */
    function shouldTranslateTheRecord()
    {
        $this->populate();
        $this->translatableListener->setTranslatableLocale('lt');

        $this->startQueryLog();
        $article = $this->em->find(self::ARTICLE, array('id' => 1));

        $sqlQueriesExecuted = $this->queryAnalyzer->getExecutedQueries();
        $this->assertEquals(2, count($sqlQueriesExecuted));
        $this->assertEquals('SELECT t0.id AS id1, t0.locale AS locale2, t0.field AS field3, t0.content AS content4, t0.object_id AS object_id5 FROM article_translations t0 WHERE t0.object_id = 1', $sqlQueriesExecuted[1]);
        $this->assertEquals('lt', $article->getTitle());
    }

    private function populate()
    {
        $article = new Article;
        $article->setTitle('en');

        $this->em->persist($article);
        $this->em->flush();

        $this->translatableListener->setTranslatableLocale('de');
        $article->setTitle('de');

        $ltTranslation = new PersonalArticleTranslation;
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

    protected function getUsedEntityFixtures()
    {
        return array(
            self::ARTICLE,
            self::TRANSLATION
        );
    }
}