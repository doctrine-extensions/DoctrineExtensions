<?php

namespace Gedmo\Tests\Translatable;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Query;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Tests\Translatable\Fixture\File;
use Gedmo\Tests\Translatable\Fixture\Image;
use Gedmo\Tests\Translatable\Fixture\TemplatedArticle;
use Gedmo\Translatable\Entity\Repository\TranslationRepository;
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
class InheritanceTest extends BaseTestCaseORM
{
    public const ARTICLE = 'Gedmo\\Tests\\Translatable\\Fixture\\TemplatedArticle';
    public const TRANSLATION = 'Gedmo\\Translatable\\Entity\\Translation';
    public const FILE = 'Gedmo\\Tests\\Translatable\\Fixture\\File';
    public const IMAGE = 'Gedmo\\Tests\\Translatable\\Fixture\\Image';

    public const TREE_WALKER_TRANSLATION = 'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker';

    private $translatableListener;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $this->translatableListener = new TranslatableListener();
        $this->translatableListener->setTranslatableLocale('en');
        $this->translatableListener->setDefaultLocale('en');
        $evm->addEventSubscriber($this->translatableListener);

        $this->getMockSqliteEntityManager($evm);
    }

    /**
     * @test
     */
    public function shouldHandleMappedSuperclass()
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
        static::assertEquals('name in de', $translations['de']['name']);

        static::assertArrayHasKey('title', $translations['de']);
        static::assertEquals('title in de', $translations['de']['title']);

        static::assertArrayHasKey('content', $translations['de']);
        static::assertEquals('content in de', $translations['de']['content']);
    }

    /**
     * @test
     */
    public function shouldHandleInheritedTranslationsThroughBaseObjectClass()
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
        static::assertEquals('image de', $files[0]['name']);
        static::assertEquals('file de', $files[1]['name']);

        // test loading in locale
        $images = $this->em->getRepository(self::IMAGE)->findAll();
        static::assertCount(1, $images);
        static::assertEquals('image de', $images[0]->getName());
        static::assertEquals('mime de', $images[0]->getMime());
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::ARTICLE,
            self::TRANSLATION,
            self::FILE,
            self::IMAGE,
        ];
    }
}
