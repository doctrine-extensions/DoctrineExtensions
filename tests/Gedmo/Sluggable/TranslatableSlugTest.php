<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Doctrine\Common\Util\Debug,
    Gedmo\Translatable\Translatable,
    Gedmo\Translatable\Entity\Translation,
    Gedmo\Translatable\TranslationListener,
    Sluggable\Fixture\TranslatableArticle,
    Sluggable\Fixture\Comment,
    Sluggable\Fixture\Page;

/**
 * These are tests for Sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Sluggable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TranslatableSlugTest extends BaseTestCaseORM
{
    private $articleId;
    private $translationListener;

    const ARTICLE = 'Sluggable\\Fixture\\TranslatableArticle';
    const COMMENT = 'Sluggable\\Fixture\\Comment';
    const PAGE = 'Sluggable\\Fixture\\Page';
    const TRANSLATION = 'Gedmo\\Translatable\\Entity\\Translation';

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $this->translationListener = new TranslationListener();
        $this->translationListener->setTranslatableLocale('en_us');
        $evm->addEventSubscriber(new SluggableListener);
        $evm->addEventSubscriber($this->translationListener);

        $this->getMockSqliteEntityManager($evm);
        $this->populate();
    }

    public function testSlugAndTranslation()
    {
        $article = $this->em->find(self::ARTICLE, $this->articleId);
        $this->assertTrue($article instanceof Translatable && $article instanceof Sluggable);
        $this->assertEquals($article->getSlug(), 'the-title-my-code');
        $repo = $this->em->getRepository(self::TRANSLATION);

        $translations = $repo->findTranslations($article);
        $this->assertEquals(count($translations), 0);

        $article = $this->em->find(self::ARTICLE, $this->articleId);
        $article->setTranslatableLocale('de_de');
        $article->setCode('code in de');
        $article->setTitle('title in de');

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();

        $repo = $this->em->getRepository(self::TRANSLATION);
        $translations = $repo->findTranslations($article);
        $this->assertEquals(count($translations), 1);
        $this->assertArrayHasKey('de_de', $translations);
        $this->assertEquals(3, count($translations['de_de']));

        $this->assertArrayHasKey('code', $translations['de_de']);
        $this->assertEquals('code in de', $translations['de_de']['code']);

        $this->assertArrayHasKey('title', $translations['de_de']);
        $this->assertEquals('title in de', $translations['de_de']['title']);

        $this->assertArrayHasKey('slug', $translations['de_de']);
        $this->assertEquals('title-in-de-code-in-de', $translations['de_de']['slug']);
    }

    public function testConcurrentChanges()
    {
        $page = new Page;
        $page->setContent('cont test');

        $a0Page = new Page;
        $a0Page->setContent('bi vv');

        $article0 = $this->em->find(self::ARTICLE, $this->articleId);
        $article0->setCode('cell');
        $article0->setTitle('xx gg');
        $a0Page->addArticle($article0);

        $a0Comment = new Comment;
        $a0Comment->setMessage('the xx message');
        $article0->addComment($a0Comment);
        $this->em->persist($a0Comment);
        $this->em->persist($article0);
        $this->em->persist($a0Page);

        $article1 = new TranslatableArticle();
        $article1->setTitle('art1 test');
        $article1->setCode('cd1 test');

        $article2 = new TranslatableArticle();
        $article2->setTitle('art2 test');
        $article2->setCode('cd2 test');

        $page->addArticle($article1);
        $page->addArticle($article2);

        $comment1 = new Comment;
        $comment1->setMessage('mes1-test');
        $comment2 = new Comment;
        $comment2->setMessage('mes2 test');

        $article1->addComment($comment1);
        $article2->addComment($comment2);

        $this->em->persist($page);
        $this->em->persist($article1);
        $this->em->persist($article2);
        $this->em->persist($comment1);
        $this->em->persist($comment2);
        $this->em->flush();
        $this->em->clear();

        $this->assertEquals($page->getSlug(), 'Cont_Test');
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::ARTICLE,
            self::COMMENT,
            self::PAGE,
            self::TRANSLATION
        );
    }

    private function populate()
    {
        $article = new TranslatableArticle();
        $article->setTitle('the title');
        $article->setCode('my code');

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();
        $this->articleId = $article->getId();
    }
}
