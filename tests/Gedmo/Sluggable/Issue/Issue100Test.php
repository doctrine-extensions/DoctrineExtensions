<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sluggable\Issue;

use Doctrine\Common\EventManager;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Tests\Sluggable\Fixture\Issue100\Article;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Translatable\Entity\Translation;
use Gedmo\Translatable\TranslatableListener;

/**
 * These are tests for sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class Issue100Test extends BaseTestCaseORM
{
    public const ARTICLE = Article::class;
    public const TRANSLATION = Translation::class;

    private TranslatableListener $translatableListener;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());
        $this->translatableListener = new TranslatableListener();
        $this->translatableListener->setTranslatableLocale('en');
        $this->translatableListener->setDefaultLocale('en');
        $evm->addEventSubscriber($this->translatableListener);

        $this->getDefaultMockSqliteEntityManager($evm);
    }

    public function testShouldWorkWithTranslatableSlug(): void
    {
        $repository = $this->em->getRepository(self::TRANSLATION);

        /*
         * First article
         */
        $article = new Article();
        $article->setTitle('First Article');
        $this->em->persist($article);

        /*
         * Second article
         */
        $article2 = new Article();
        $article2->setTitle('First Article');
        $this->em->persist($article2);

        $this->em->flush();

        $this->translatableListener->setTranslatableLocale('fr');

        $article->setTitle('Premier article');
        $this->em->flush();

        $article2->setTitle('Premier article');
        $this->em->flush();

        $this->translatableListener->setTranslatableLocale('en');

        $this->em->refresh($article);
        $this->em->refresh($article2);

        static::assertSame('first-article', $article->getSlug());
        static::assertSame('first-article-1', $article2->getSlug());

        $translations = $repository->findTranslations($article);
        static::assertArrayHasKey('fr', $translations);
        static::assertArrayHasKey('title', $translations['fr']);
        static::assertArrayHasKey('slug', $translations['fr']);
        static::assertSame('premier-article', $translations['fr']['slug']);

        $translations2 = $repository->findTranslations($article2);
        static::assertArrayHasKey('fr', $translations2);
        static::assertArrayHasKey('title', $translations2['fr']);
        static::assertArrayHasKey('slug', $translations2['fr']);

        // This should be 'premier-article-1' instead of 'premier-article' because of using
        // TranslationWalker hint in `getSimilarSlugs`method
        static::assertSame('premier-article-1', $translations2['fr']['slug']);
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::ARTICLE,
            self::TRANSLATION,
        ];
    }
}
