<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sluggable;

use Doctrine\Common\EventManager;
use Gedmo\Sluggable\Sluggable;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Tests\Sluggable\Fixture\Comment;
use Gedmo\Tests\Sluggable\Fixture\Page;
use Gedmo\Tests\Sluggable\Fixture\TranslatableArticle;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Translatable\Entity\Translation;
use Gedmo\Translatable\Translatable;
use Gedmo\Translatable\TranslatableListener;

/**
 * These are tests for Sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class TranslatableSlugTest extends BaseTestCaseORM
{
    public const ARTICLE = TranslatableArticle::class;
    public const COMMENT = Comment::class;
    public const PAGE = Page::class;
    public const TRANSLATION = Translation::class;

    /**
     * @var int|null
     */
    private $articleId;

    /**
     * @var TranslatableListener
     */
    private $translatableListener;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $this->translatableListener = new TranslatableListener();
        $this->translatableListener->setTranslatableLocale('en_US');
        $evm->addEventSubscriber(new SluggableListener());
        $evm->addEventSubscriber($this->translatableListener);

        $this->getDefaultMockSqliteEntityManager($evm);
        $this->populate();
    }

    public function testSlugAndTranslation(): void
    {
        $article = $this->em->find(self::ARTICLE, $this->articleId);
        static::assertTrue($article instanceof Translatable && $article instanceof Sluggable);
        static::assertSame('the-title-my-code', $article->getSlug());
        $repo = $this->em->getRepository(self::TRANSLATION);

        $translations = $repo->findTranslations($article);
        static::assertCount(0, $translations);

        $article = $this->em->find(self::ARTICLE, $this->articleId);
        $article->setTranslatableLocale('de_DE');
        $article->setCode('code in de');
        $article->setTitle('title in de');

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();

        $repo = $this->em->getRepository(self::TRANSLATION);
        $translations = $repo->findTranslations($article);
        static::assertCount(1, $translations);
        static::assertArrayHasKey('de_DE', $translations);
        static::assertCount(3, $translations['de_DE']);

        static::assertArrayHasKey('code', $translations['de_DE']);
        static::assertSame('code in de', $translations['de_DE']['code']);

        static::assertArrayHasKey('title', $translations['de_DE']);
        static::assertSame('title in de', $translations['de_DE']['title']);

        static::assertArrayHasKey('slug', $translations['de_DE']);
        static::assertSame('title-in-de-code-in-de', $translations['de_DE']['slug']);
    }

    public function testConcurrentChanges(): void
    {
        $page = new Page();
        $page->setContent('cont test');

        $a0Page = new Page();
        $a0Page->setContent('bi vv');

        $article0 = $this->em->find(self::ARTICLE, $this->articleId);
        $article0->setCode('cell');
        $article0->setTitle('xx gg');
        $a0Page->addArticle($article0);

        $a0Comment = new Comment();
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

        $comment1 = new Comment();
        $comment1->setMessage('mes1-test');
        $comment2 = new Comment();
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

        static::assertSame('Cont_Test', $page->getSlug());
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::ARTICLE,
            self::COMMENT,
            self::PAGE,
            self::TRANSLATION,
        ];
    }

    private function populate(): void
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
