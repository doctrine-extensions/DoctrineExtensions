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
use Doctrine\ORM\Query;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Tests\Translatable\Fixture\File;
use Gedmo\Tests\Translatable\Fixture\Image;
use Gedmo\Tests\Translatable\Fixture\TemplatedArticle;
use Gedmo\Translatable\Entity\Repository\TranslationRepository;
use Gedmo\Translatable\Entity\Translation;
use Gedmo\Translatable\Query\TreeWalker\TranslationWalker;
use Gedmo\Translatable\TranslatableListener;

/**
 * These are tests for translatable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class InheritanceTest extends BaseTestCaseORM
{
    public const ARTICLE = TemplatedArticle::class;
    public const TRANSLATION = Translation::class;
    public const FILE = File::class;
    public const IMAGE = Image::class;

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
        $this->translatableListener->setTranslatableLocale('en');
        $this->translatableListener->setDefaultLocale('en');
        $evm->addEventSubscriber($this->translatableListener);

        $this->getDefaultMockSqliteEntityManager($evm);
    }

    public function testShouldHandleMappedSuperclass(): void
    {
        $article = new TemplatedArticle();
        $article->setName('name in en');
        $article->setContent('content in en');
        $article->setTitle('title in en');

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();

        $repo = $this->em->getRepository(self::TRANSLATION);
        static::assertInstanceOf(TranslationRepository::class, $repo);

        $translations = $repo->findTranslations($article);
        static::assertCount(0, $translations);

        // test second translations
        $article = $this->em->getRepository(self::ARTICLE)->find(1);
        $this->translatableListener->setTranslatableLocale('de');
        $article->setName('name in de');
        $article->setContent('content in de');
        $article->setTitle('title in de');

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();

        $translations = $repo->findTranslations($article);
        static::assertCount(1, $translations);
        static::assertArrayHasKey('de', $translations);

        static::assertArrayHasKey('name', $translations['de']);
        static::assertSame('name in de', $translations['de']['name']);

        static::assertArrayHasKey('title', $translations['de']);
        static::assertSame('title in de', $translations['de']['title']);

        static::assertArrayHasKey('content', $translations['de']);
        static::assertSame('content in de', $translations['de']['content']);
    }

    public function testShouldHandleInheritedTranslationsThroughBaseObjectClass(): void
    {
        $file = new File();
        $file->setSize(500);
        $file->setName('file en');

        $image = new Image();
        $image->setMime('mime en');
        $image->setName('image en');
        $image->setSize(445);

        $this->em->persist($file);
        $this->em->persist($image);
        $this->em->flush();

        $this->translatableListener->setTranslatableLocale('de');
        $file->setName('file de');

        $image->setName('image de');
        $image->setMime('mime de');

        $this->em->persist($file);
        $this->em->persist($image);
        $this->em->flush();
        $this->em->clear();

        $dql = 'SELECT f FROM '.self::FILE.' f';
        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        $files = $q->getArrayResult();
        static::assertCount(2, $files);
        static::assertSame('image de', $files[0]['name']);
        static::assertSame('file de', $files[1]['name']);

        // test loading in locale
        $images = $this->em->getRepository(self::IMAGE)->findAll();
        static::assertCount(1, $images);
        static::assertSame('image de', $images[0]->getName());
        static::assertSame('mime de', $images[0]->getMime());
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::ARTICLE,
            self::TRANSLATION,
            self::FILE,
            self::IMAGE,
        ];
    }
}
