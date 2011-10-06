<?php

namespace Gedmo\Translatable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Doctrine\Common\Util\Debug,
    Translatable\Fixture\PersonTranslation,
    Translatable\Fixture\Person;

/**
 * These are tests for translatable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Translatable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class EntityTranslationTableTest extends BaseTestCaseORM
{
    const PERSON = 'Translatable\\Fixture\\Person';
    const TRANSLATION = 'Translatable\\Fixture\\PersonTranslation';

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

    public function testFixtureGeneratedTranslations()
    {
        $person = new Person;
        $person->setName('name in en');

        $this->em->persist($person);
        $this->em->flush();
        $this->em->clear();

        $repo = $this->em->getRepository(self::TRANSLATION);
        $this->assertTrue($repo instanceof Entity\Repository\TranslationRepository);

        $translations = $repo->findTranslations($person);
        //As Translate locale and Default locale are the same, no records should be present in translations table
        $this->assertEquals(count($translations), 0);

        // test second translations
        $person = $this->em->find(self::PERSON, $person->getId());
        $this->translatableListener->setTranslatableLocale('de_de');
        $person->setName('name in de');

        $this->em->persist($person);
        $this->em->flush();
        $this->em->clear();

        $translations = $repo->findTranslations($person);
        //Only one translation should be present
        $this->assertEquals(count($translations), 1);
        $this->assertArrayHasKey('de_de', $translations);

        $this->assertArrayHasKey('name', $translations['de_de']);
        $this->assertEquals('name in de', $translations['de_de']['name']);

        $this->translatableListener->setTranslatableLocale('en_us');
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::PERSON,
            self::TRANSLATION
        );
    }
}
