<?php

namespace Gedmo\Translatable;

use Doctrine\Common\EventManager;
use Doctrine\Common\Collections\ArrayCollection;
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

        // Step1: article creation in default locale
        $image1 = new Image;
        $image1->setTitle('img1');
        $this->em->persist($image1);

        $image2 = new Image;
        $image2->setTitle('img2');
        $this->em->persist($image2);
		
        $image3 = new Image;
        $image3->setTitle('img3');
        $this->em->persist($image3);

        $article = new Article;
        $article->setTitle('en art');
        // images is not an array
        $images = new ArrayCollection();
		$images[]= $image1;
		$images[]= $image2;
        $article->setImages($images);
        $this->em->persist($article);

        $this->em->flush();
		
        // Step2: article update in another locale
        // The problem actually happens when I change the images - otherwise Doctrine does not detect any change ?
        $article = $this->em->find(self::ARTICLE, $article->getId());
		// Now i want images 1 & 3, instead of 1 & 2
        $image1 = $this->em->find(self::IMAGE, $image1->getId());
        $image3 = $this->em->find(self::IMAGE, $image3->getId());
        $article->setTitle('en updated');
        /**
         * here you duplicate the objects in collection, it allready
         * contains them. Read more about doctrine collections
         */
        $images = new ArrayCollection();
		$images[]= $image1;
		$images[]= $image3;
        $article->setImages($images);
        $this->em->persist($article);
        $this->em->flush();
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