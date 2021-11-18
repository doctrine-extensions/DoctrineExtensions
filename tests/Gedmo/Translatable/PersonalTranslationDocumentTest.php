<?php

namespace Gedmo\Tests\Translatable;

use Doctrine\Common\EventManager;
use Gedmo\Tests\Tool\BaseTestCaseMongoODM;
use Gedmo\Tests\Translatable\Fixture\Document\Personal\Article;
use Gedmo\Tests\Translatable\Fixture\Document\Personal\ArticleTranslation;
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
final class PersonalTranslationDocumentTest extends BaseTestCaseMongoODM
{
    public const ARTICLE = Article::class;
    public const TRANSLATION = ArticleTranslation::class;

    private $translatableListener;
    private $id;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $this->translatableListener = new TranslatableListener();
        $this->translatableListener->setDefaultLocale('en');
        $this->translatableListener->setTranslatableLocale('en');
        $evm->addEventSubscriber($this->translatableListener);

        $this->getMockDocumentManager($evm);
    }

    /**
     * @test
     */
    public function shouldCreateTranslations()
    {
        $this->populate();
        $article = $this->dm->getRepository(self::ARTICLE)->find($this->id);
        $translations = $article->getTranslations();

        static::assertCount(2, $translations);
    }

    /**
     * @test
     */
    public function shouldTranslateTheRecord()
    {
        $this->populate();
        $this->translatableListener->setTranslatableLocale('lt');

        $article = $this->dm->getRepository(self::ARTICLE)->find($this->id);
        static::assertSame('lt', $article->getTitle());
    }

    private function populate()
    {
        $article = new Article();
        $article->setTitle('en');

        $this->dm->persist($article);
        $this->dm->flush();

        $this->id = $article->getId();

        $this->translatableListener->setTranslatableLocale('de');
        $article->setTitle('de');

        $ltTranslation = new ArticleTranslation();
        $ltTranslation
            ->setField('title')
            ->setContent('lt')
            ->setObject($article)
            ->setLocale('lt')
        ;
        $this->dm->persist($ltTranslation);
        $this->dm->persist($article);
        $this->dm->flush();
        $this->dm->clear();
    }
}
