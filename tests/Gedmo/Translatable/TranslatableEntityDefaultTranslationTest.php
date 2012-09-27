<?php

namespace Gedmo\Translatable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Translatable\Fixture\Article;
use Translatable\Fixture\Comment;

/**
 * These are tests for translatable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Translatable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TranslatableEntityDefaultTranslationTest extends BaseTestCaseORM
{
    const ARTICLE = 'Translatable\\Fixture\\Article';
    const COMMENT = 'Translatable\\Fixture\\Comment';
    const TRANSLATION = 'Gedmo\\Translatable\\Entity\\Translation';

    private $translatableListener;

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $this->translatableListener = new TranslatableListener();
        $this->translatableListener->setTranslatableLocale('translatedLocale');
        $this->translatableListener->setDefaultLocale('defaultLocale');
        $evm->addEventSubscriber($this->translatableListener);

        $conn = array(
            'driver' => 'pdo_mysql',
            'host' => '127.0.0.1',
            'dbname' => 'test',
            'user' => 'root',
            'password' => 'nimda'
        );
        //$this->getMockCustomEntityManager($conn, $evm);
        $this->getMockSqliteEntityManager($evm);

        $this->repo = $this->em->getRepository(self::TRANSLATION);

    }



    // --- Tests for default translation overruling the translated entity
    //     property ------------------------------------------------------------



    function testTranslatedPropertyWithoutPersistingDefault()
    {
        $this->translatableListener->setPersistDefaultLocaleTranslation( false );
        $entity = new Article;
        $this->repo
            ->translate($entity, 'title', 'defaultLocale'   , 'title defaultLocale'   )
            ->translate($entity, 'title', 'translatedLocale', 'title translatedLocale')
        ;
        $this->assertSame('title translatedLocale', $entity->getTitle());
    }

    function testTranslatedPropertyWithoutPersistingDefaultResorted()
    {
        $this->translatableListener->setPersistDefaultLocaleTranslation( false );
        $entity = new Article;
        $this->repo
            ->translate($entity, 'title', 'translatedLocale', 'title translatedLocale')
            ->translate($entity, 'title', 'defaultLocale'   , 'title defaultLocale'   )
        ;
        $this->assertSame('title translatedLocale', $entity->getTitle());
    }

    function testTranslatedPropertyWithPersistingDefault()
    {
        $this->translatableListener->setPersistDefaultLocaleTranslation( true );
        $entity = new Article;
        $this->repo
            ->translate($entity, 'title', 'defaultLocale'   , 'title defaultLocale'   )
            ->translate($entity, 'title', 'translatedLocale', 'title translatedLocale')
        ;
        $this->assertSame('title translatedLocale', $entity->getTitle());
    }

    function testTranslatedPropertyWithPersistingDefaultResorted()
    {
        $this->translatableListener->setPersistDefaultLocaleTranslation( true );
        $entity = new Article;
        $this->repo
            ->translate($entity, 'title', 'translatedLocale', 'title translatedLocale')
            ->translate($entity, 'title', 'defaultLocale'   , 'title defaultLocale'   )
        ;
        $this->assertSame('title translatedLocale', $entity->getTitle());
    }



    // --- Fixture related methods ---------------------------------------------



    protected function getUsedEntityFixtures()
    {
        return array(
            self::ARTICLE,
            self::TRANSLATION
        );
    }
}
