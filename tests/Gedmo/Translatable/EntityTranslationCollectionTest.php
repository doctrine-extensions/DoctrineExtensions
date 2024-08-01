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
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Gedmo\Tests\Tool\BaseTestCaseMongoODM;
use Gedmo\Tests\Translatable\Fixture\Document\TranslationCollection\Person;
use Gedmo\Tests\Translatable\Fixture\Document\TranslationCollection\PersonTranslation;
use Gedmo\Translatable\Document\Repository\TranslationRepository;
use Gedmo\Translatable\TranslatableListener;

/**
 * These are tests for translatable behavior
 *
 * @author Dominik Grothaus <dominik@dodo-softworks.de>
 */
class EntityTranslationCollectionTest extends BaseTestCaseMongoODM
{
    private const PERSON = Person::class;
    private const TRANSLATION = PersonTranslation::class;

    private TranslatableListener $translatableListener;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $this->translatableListener = new TranslatableListener();
        $this->translatableListener->setDefaultLocale('en_US');
        $this->translatableListener->setTranslatableLocale('en_US');
        $evm->addEventSubscriber($this->translatableListener);

        $this->getDefaultDocumentManager($evm);
    }

    public function testFixtureGeneratedTranslations(): void
    {
        $person = new (self::PERSON)();
        $person->setName('name in en');

        $this->dm->persist($person);
        $this->dm->flush();
        $this->dm->clear();

        $repo = $this->dm->getRepository(self::TRANSLATION);
        static::assertInstanceOf(TranslationRepository::class, $repo);

        $translations = $repo->findTranslations($person);
        // As Translate locale and Default locale are the same, no records should be present in translations table
        static::assertCount(0, $translations);

        // test second translations
        $person = $this->dm->find(self::PERSON, $person->getId());
        $this->translatableListener->setTranslatableLocale('de_DE');
        $person->setName('name in de');

        $this->dm->persist($person);
        $this->dm->flush();
        $this->dm->clear();

        $translations = $repo->findTranslations($person);
        // Only one translation should be present
        static::assertCount(1, $translations);
        static::assertArrayHasKey('de_DE', $translations);

        static::assertArrayHasKey('name', $translations['de_DE']);
        static::assertSame('name in de', $translations['de_DE']['name']);

        $this->translatableListener->setTranslatableLocale('en_US');
    }

    public function testShouldPersistDefaultLocaleValue(): void
    {
        $this->translatableListener->setPersistDefaultLocaleTranslation(true);

        $person = new (self::PERSON)();
        $person->setName('de_DE');

        /** @var TranslationRepository $translationRepository */
        $translationRepository = $this->dm->getRepository(self::TRANSLATION);
        $translationRepository
            ->translate($person, 'name', 'de_DE', 'de_DE')
            ->translate($person, 'name', 'en_US', 'en_US');
        $this->dm->persist($person);
        $this->dm->flush();

        $this->translatableListener->setTranslatableLocale('en_US');

        /** @var DocumentRepository<Person> $personRepository */
        $personRepository = $this->dm->getRepository(self::PERSON);
        $persons = $personRepository
            ->createQueryBuilder()
            ->hydrate(false)
            ->find()
            ->getQuery()
            ->getIterator()
            ->toArray()
        ;
        static::assertSame('en_US', $persons[0]['name']);

        $trans = $translationRepository
            ->createQueryBuilder()
            ->hydrate(false)
            ->find()
            ->getQuery()
            ->getIterator()
            ->toArray()
        ;
        static::assertCount(2, $trans);
        foreach ($trans as $item) {
            static::assertSame($item['locale'], $item['content']);
        }
        $this->translatableListener->setTranslatableLocale('en_US');
    }
}
