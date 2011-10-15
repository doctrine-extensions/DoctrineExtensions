<?php

namespace Gedmo\Translatable;

use Tool\BaseTestCaseMongoODM;
use Doctrine\Common\EventManager;
use Translatable\Fixture\Issue165\SimpleArticle;

/**
 * These are tests for Translatable behavior ODM implementation
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Translatable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Issue165Test extends BaseTestCaseMongoODM
{
    const ARTICLE = 'Translatable\Fixture\Issue165\SimpleArticle';
    const TRANSLATION = 'Gedmo\\Translatable\\Document\\Translation';

    private $translationListener;
    private $articleId;

    protected function setUp()
    {
        parent::setUp();
        $evm = new EventManager();
        $this->translationListener = new TranslationListener;
        $this->translationListener->setDefaultLocale('en');
        $this->translationListener->setTranslatableLocale('en');
        $evm->addEventSubscriber($this->translationListener);

        $this->getMockDocumentManager($evm);
    }

    /**
     * @test
     */
    public function shouldPersistUntranslatedFields()
    {
        $article = new SimpleArticle;
        $article->setTitle('en');
        $article->setContent('en');
        $article->setUntranslated('en');

        $this->dm->persist($article);
        $this->dm->flush();

        $this->assertEquals('en', $article->getUntranslated());

        $this->translationListener->setTranslatableLocale('ru');

        $article->setTitle('ru');
        $article->setContent('ru');
        $article->setUntranslated('ru');

        $this->dm->persist($article);
        $this->dm->flush();

        $this->assertEquals('ru', $article->getUntranslated());

        $this->translationListener->setTranslatableLocale('de');

        $newarticle = new SimpleArticle;
        $newarticle->setTitle('de');
        $newarticle->setContent('de');
        $newarticle->setUntranslated('de');

        $this->dm->persist($newarticle);
        $this->dm->flush();

        $this->assertEquals('de', $newarticle->getUntranslated());
    }
}