<?php

namespace Gedmo\Tests\Translatable;

use Doctrine\Common\EventManager;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Tests\Translatable\Fixture\Person;
use Gedmo\Tests\Translatable\Fixture\PersonTranslation;
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
final class EntityTranslationTableTest extends BaseTestCaseORM
{
    public const PERSON = Person::class;
    public const TRANSLATION = PersonTranslation::class;

    private $translatableListener;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $this->translatableListener = new TranslatableListener();
        $this->translatableListener->setTranslatableLocale('en_us');
        $this->translatableListener->setDefaultLocale('en_us');
        $evm->addEventSubscriber($this->translatableListener);

        $this->getDefaultMockSqliteEntityManager($evm);
    }

    public function testFixtureGeneratedTranslations()
    {
        $person = new Person();
        $person->setName('name in en');

        $this->em->persist($person);
        $this->em->flush();
        $this->em->clear();

        $repo = $this->em->getRepository(self::TRANSLATION);
        static::assertInstanceOf(TranslationRepository::class, $repo);

        $translations = $repo->findTranslations($person);
        //As Translate locale and Default locale are the same, no records should be present in translations table
        static::assertCount(0, $translations);

        // test second translations
        $person = $this->em->find(self::PERSON, $person->getId());
        $this->translatableListener->setTranslatableLocale('de_de');
        $person->setName('name in de');

        $this->em->persist($person);
        $this->em->flush();
        $this->em->clear();

        $translations = $repo->findTranslations($person);
        //Only one translation should be present
        static::assertCount(1, $translations);
        static::assertArrayHasKey('de_de', $translations);

        static::assertArrayHasKey('name', $translations['de_de']);
        static::assertSame('name in de', $translations['de_de']['name']);

        $this->translatableListener->setTranslatableLocale('en_us');
    }

    /**
     * Covers issue #438
     *
     * @test
     */
    public function shouldPersistDefaultLocaleValue()
    {
        $this->translatableListener->setPersistDefaultLocaleTranslation(true);
        $this->translatableListener->setTranslatableLocale('de');
        $person = new Person();
        $person->setName('de');

        $repo = $this->em->getRepository(self::TRANSLATION);
        $repo
            ->translate($person, 'name', 'de', 'de')
            ->translate($person, 'name', 'en_us', 'en_us')
        ;
        $this->em->persist($person);
        $this->em->flush();

        $this->translatableListener->setTranslatableLocale('en_us');
        $articles = $this->em->createQuery('SELECT p FROM '.self::PERSON.' p')->getArrayResult();
        static::assertSame('en_us', $articles[0]['name']);
        $trans = $this->em->createQuery('SELECT t FROM '.self::TRANSLATION.' t')->getArrayResult();
        static::assertCount(2, $trans);
        foreach ($trans as $item) {
            static::assertSame($item['locale'], $item['content']);
        }
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::PERSON,
            self::TRANSLATION,
        ];
    }
}
