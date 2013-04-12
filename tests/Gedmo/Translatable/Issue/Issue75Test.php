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
        $this->translatableListener = new TranslatableListener();
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

        /*$image2 = new Image;
        $image2->setTitle('img2');
        $this->em->persist($image2);*/

        $article = new Article;
        $article->setTitle('en art');
        // images is not an array
        //$article->setImages(array($image1, $image2));
        $this->em->persist($article);

        //$this->em->flush();*/
        $image2 = new Image;  //line 62
        $image2->setTitle('en img2');
        $this->em->persist($image2);

        $image32 = new Image; // +
        $image32->setTitle('en img3');  // +
        $this->em->persist($image32); // +

        $article->addImage($image1);
        $article->addImage($image2);

        $this->em->persist($article);  // +
        $this->em->flush();

        $article->setTitle('nada');  // +
        $article->addImage($image32);  // +
        $this->em->persist($article);  // +
        $this->em->flush();

        //Step2: article update in another locale
        $article = $this->em->find(self::ARTICLE, $article->getId());
        $image1 = $this->em->find(self::IMAGE, $image1->getId());
        $image2 = $this->em->find(self::IMAGE, $image2->getId());
        $article->setTitle('en updated');
        /**
         * here you duplicate the objects in collection, it allready
         * contains them. Read more about doctrine collections
         */
        $article->setImages(array($image1, $image2));
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