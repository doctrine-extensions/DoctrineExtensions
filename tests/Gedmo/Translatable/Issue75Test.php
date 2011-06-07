<?php

namespace Gedmo\Translatable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Translatable\Fixture\Issue75\Article;
use Translatable\Fixture\Issue75\Image;
use Translatable\Fixture\Issue75\File;

/**
 * These are tests for translatable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Translatable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Issue75Test extends BaseTestCaseORM
{
    const ARTICLE = 'Translatable\\Fixture\\Issue75\\Article';
    const IMAGE = 'Translatable\\Fixture\\Issue75\\Image';
    const FILE = 'Translatable\\Fixture\\Issue75\\File';
    const TRANSLATION = 'Gedmo\\Translatable\\Entity\\Translation';

    private $translatableListener;

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $this->translatableListener = new TranslationListener();
        $this->translatableListener->setTranslatableLocale('en');
        $this->translatableListener->setDefaultLocale('en');
        $evm->addEventSubscriber($this->translatableListener);

        $this->getMockSqliteEntityManager($evm);
    }

    public function testIssue75()
    {
        $repo = $this->em->getRepository(self::TRANSLATION);
        $file1 = new File;
        $file1->setTitle('en file1');
        $this->em->persist($file1);

        $file2 = new File;
        $file2->setTitle('en file2');
        $this->em->persist($file2);

        $article = new Article;
        $article->setTitle('en art');
        $article->addFile($file1);
        $article->addFile($file2);
        $this->em->persist($article);

        $image1 = new Image;
        $image1->setTitle('en img1');
        $this->em->persist($image1);

        $image2 = new Image;
        $image2->setTitle('en img2');
        $this->em->persist($image2);

        $article->addImage($image1);
        $article->addImage($image2);

        $this->em->flush();

        $this->translatableListener->setTranslatableLocale('de');
        $image1->setTitle('de img1');
        $article->setTitle('de art');
        $file2->setTitle('de file2');

        $this->em->persist($article);
        $this->em->persist($image1);
        $this->em->persist($file2);

        $this->em->flush();

        $trans = $repo->findTranslations($article);
        $this->assertEquals(2, count($trans));

        $trans = $repo->findTranslations($file2);
        $this->assertEquals(2, count($trans));

        $trans = $repo->findTranslations($image2);
        $this->assertEquals(1, count($trans));
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::ARTICLE,
            self::TRANSLATION,
            self::IMAGE,
            self::FILE
        );
    }
}