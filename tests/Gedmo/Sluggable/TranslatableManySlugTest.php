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
use Gedmo\Tests\Sluggable\Fixture\TransArticleManySlug;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Translatable\Entity\Translation;
use Gedmo\Translatable\Translatable;
use Gedmo\Translatable\TranslatableListener;

/**
 * These are tests for Sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class TranslatableManySlugTest extends BaseTestCaseORM
{
    public const ARTICLE = TransArticleManySlug::class;
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
        static::assertSame('the-unique-title', $article->getUniqueSlug());
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

        static::assertSame('title in de', $translations['de_DE']['title']);

        static::assertArrayHasKey('slug', $translations['de_DE']);
        static::assertSame('title-in-de-code-in-de', $translations['de_DE']['slug']);
    }

    public function testUniqueness(): void
    {
        $a0 = new TransArticleManySlug();
        $a0->setTitle('the title');
        $a0->setCode('my code');
        $a0->setUniqueTitle('title');

        $this->em->persist($a0);

        $a1 = new TransArticleManySlug();
        $a1->setTitle('the title');
        $a1->setCode('my code');
        $a1->setUniqueTitle('title');

        $this->em->persist($a1);
        $this->em->flush();

        static::assertSame('title', $a0->getUniqueSlug());
        static::assertSame('title-1', $a1->getUniqueSlug());
        // if its translated maybe should be different
        static::assertSame('the-title-my-code-1', $a0->getSlug());
        static::assertSame('the-title-my-code-2', $a1->getSlug());
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
        $article = new TransArticleManySlug();
        $article->setTitle('the title');
        $article->setCode('my code');
        $article->setUniqueTitle('the unique title');

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();
        $this->articleId = $article->getId();
    }
}
