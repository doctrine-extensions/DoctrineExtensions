<?php

namespace Gedmo\Translatable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Doctrine\Common\Util\Debug,
    Translatable\Fixture\TemplatedArticle;

/**
 * These are tests for translatable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Translatable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class InheritanceTest extends BaseTestCaseORM
{
    const ARTICLE = 'Translatable\\Fixture\\TemplatedArticle';
    const TRANSLATION = 'Gedmo\\Translatable\\Entity\\Translation';
    const FILE = 'Translatable\\Fixture\\File';
    const IMAGE = 'Translatable\\Fixture\\Image';

    private $translatableListener;

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $this->translatableListener = new TranslationListener();
        $this->translatableListener->setTranslatableLocale('en_us');
        $this->translatableListener->setDefaultLocale('en_us');
        $evm->addEventSubscriber($this->translatableListener);

        $this->getMockSqliteEntityManager($evm);
    }

    public function testTranslations()
    {
        $article = new TemplatedArticle();
        $article->setName('name in en');
        $article->setContent('content in en');
        $article->setTitle('title in en');

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();

        $repo = $this->em->getRepository(self::TRANSLATION);
        $this->assertTrue($repo instanceof Entity\Repository\TranslationRepository);

        $translations = $repo->findTranslations($article);
        $this->assertEquals(0, count($translations));

        // test second translations
        $article = $this->em->getRepository(self::ARTICLE)->find(1);
        $this->translatableListener->setTranslatableLocale('de_de');
        $article->setName('name in de');
        $article->setContent('content in de');
        $article->setTitle('title in de');

        $this->em->persist($article);
        $this->em->flush();
        $this->em->clear();

        $translations = $repo->findTranslations($article);
        $this->assertEquals(1, count($translations));
        $this->assertArrayHasKey('de_de', $translations);

        $this->assertArrayHasKey('name', $translations['de_de']);
        $this->assertEquals('name in de', $translations['de_de']['name']);

        $this->assertArrayHasKey('title', $translations['de_de']);
        $this->assertEquals('title in de', $translations['de_de']['title']);

        $this->assertArrayHasKey('content', $translations['de_de']);
        $this->assertEquals('content in de', $translations['de_de']['content']);

        $this->translatableListener->setTranslatableLocale('en_us');
    }

    public function testInheritedTable()
    {
        $file = new \Translatable\Fixture\File;
        $file->setSize(500);
        $file->setName('file en');

        $image = new \Translatable\Fixture\Image;
        $image->setMime('mime en');
        $image->setName('image en');
        $image->setSize(445);

        $this->em->persist($file);
        $this->em->persist($image);
        $this->em->flush();
        $this->em->clear();

        $this->translatableListener->setTranslatableLocale('de_de');
        $file = $this->em->getRepository(self::FILE)->findOneByName('file en');
        $file->setName('file de');

        $image = $this->em->getRepository(self::IMAGE)->findOneByName('image en');
        $image->setName('image de');
        $image->setMime('mime de');

        $this->em->persist($file);
        $this->em->persist($image);
        $this->em->flush();
        $this->em->clear();
        $this->translatableListener->setTranslatableLocale('en_us');

        $repo = $this->em->getRepository(self::TRANSLATION);
        $this->assertTrue($repo instanceof Entity\Repository\TranslationRepository);

        $translations = $repo->findTranslations($file);
        $this->assertEquals(1, count($translations));

        $this->assertArrayHasKey('de_de', $translations);

        $this->assertArrayHasKey('name', $translations['de_de']);
        $this->assertEquals('file de', $translations['de_de']['name']);

        $translations = $repo->findTranslations($image);
        $this->assertEquals(1, count($translations));

        $this->assertArrayHasKey('de_de', $translations);

        $this->assertArrayHasKey('name', $translations['de_de']);
        $this->assertEquals('image de', $translations['de_de']['name']);
        $this->assertArrayHasKey('mime', $translations['de_de']);
        $this->assertEquals('mime de', $translations['de_de']['mime']);
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::ARTICLE,
            self::TRANSLATION,
            self::FILE,
            self::IMAGE
        );
    }
}
