<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Translator;

use Doctrine\Common\EventManager;
use Doctrine\Persistence\Proxy;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Tests\Translator\Fixture\Person;
use Gedmo\Tests\Translator\Fixture\PersonCustom;

/**
 * These are tests for translatable behavior
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
final class TranslatableTest extends BaseTestCaseORM
{
    public const PERSON = Person::class;
    public const PERSON_CUSTOM_PROXY = PersonCustom::class;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $this->getDefaultMockSqliteEntityManager($evm);
    }

    public function testTranslatable(): void
    {
        $person = new Person();
        $person->setName('Jen');
        $person->translate('ru_RU')->setName('Женя');
        $person->setDescription('description');
        $person->translate('ru_RU')->setDescription('multilingual description');

        static::assertSame('Jen', $person->getName());
        static::assertSame('Женя', $person->translate('ru_RU')->getName());
        static::assertSame('multilingual description', $person->translate('ru_RU')->getDescription());
        static::assertSame('multilingual description', $person->getDescription());

        $this->em->persist($person);
        $this->em->flush();
        $this->em->clear();

        // retrieve record (translations would be fetched later - by demand)
        $person = $this->em->getRepository(self::PERSON)->findOneBy(['name' => 'Jen']);

        static::assertSame('Jen', $person->getName());
        static::assertSame('Женя', $person->translate('ru_RU')->getName());
        static::assertSame('multilingual description', $person->translate('ru_RU')->getDescription());
        static::assertSame('multilingual description', $person->getDescription());

        // retrieve record with all translations in one query
        $persons = $this->em->getRepository(self::PERSON)
            ->createQueryBuilder('p')
            ->select('p, t')
            ->join('p.translations', 't')
            ->getQuery()
            ->execute();
        $person = $persons[0];

        static::assertSame('Jen', $person->getName());
        static::assertSame('Женя', $person->translate('ru_RU')->getName());
        static::assertSame('multilingual description', $person->translate('ru_RU')->getDescription());
        static::assertSame('multilingual description', $person->getDescription());

        $person->translate('es_ES')->setName('Amigo');

        $this->em->flush();

        // retrieve record with all translations in one query
        $persons = $this->em->getRepository(self::PERSON)
            ->createQueryBuilder('p')
            ->select('p, t')
            ->join('p.translations', 't')
            ->getQuery()
            ->execute();
        $person = $persons[0];

        static::assertSame('Jen', $person->getName());
        static::assertSame('Женя', $person->translate('ru_RU')->getName());
        static::assertSame('Amigo', $person->translate('es_ES')->getName());
        static::assertSame('multilingual description', $person->translate('ru_RU')->getDescription());
    }

    public function testShouldTranslateRelation(): void
    {
        $person = new Person();
        $person->setName('Jen');
        $person->translate('ru')->setName('Женя');
        $person->setDescription('description');
        $person->translate('ru')->setDescription('multilingual description');

        $parent = new Person();
        $parent->setName('Jen');
        $parent->translate('ru')->setName('Женя starshai');
        $parent->translate('fr')->setName('zenia');
        $parent->setDescription('description');
        $parent->translate('ru')->setDescription('multilingual description');

        $person->setParent($parent);
        $this->em->persist($person);
        $this->em->persist($parent);
        $this->em->flush();
        $this->em->clear();

        $person = $this->em->getRepository(self::PERSON)->findOneBy(['name' => 'Jen']);
        static::assertSame('Женя', $person->translate('ru')->getName());
        $parent = $person->getParent();
        static::assertInstanceOf(Proxy::class, $parent);
        static::assertInstanceOf(Person::class, $parent);
        static::assertSame('Женя starshai', $parent->translate('ru')->getName());
        static::assertSame('zenia', $parent->translate('fr')->getName());
    }

    public function testShouldHandleDomainObjectProxy(): void
    {
        $person = new Person();
        $person->setName('Jen');
        $person->translate('ru_RU')->setName('Женя');
        $person->setDescription('description');
        $person->translate('ru_RU')->setDescription('multilingual description');

        $this->em->persist($person);
        $this->em->flush();
        $this->em->clear();

        $personProxy = $this->em->getReference(self::PERSON, ['id' => 1]);
        static::assertInstanceOf(Proxy::class, $personProxy);
        $name = $personProxy->translate('ru_RU')->getName();
        static::assertSame('Женя', $name);
    }

    public function testTranslatableProxyWithUpperCaseProperty(): void
    {
        $person = new Person();
        $person->setName('Jen');
        $person->translate('ru_RU')->name = 'Женя';
        $person->setLastName('Abramowicz');
        $person->translate('ru_RU')->setLastName('Абрамович');
        $person->setDescription('description');
        $person->translate('ru_RU')->setDescription('multilingual description');

        $this->em->persist($person);
        $this->em->flush();
        $this->em->clear();

        $personProxy = $this->em->getReference(self::PERSON, ['id' => 1]);
        static::assertInstanceOf(Proxy::class, $personProxy);
        $name = $personProxy->translate('ru_RU')->getName();
        static::assertSame('Женя', $name);
        $lastName = $personProxy->translate('ru_RU')->getLastName();
        static::assertSame('Абрамович', $lastName);
    }

    public function testTranslatableWithMagicProperties(): void
    {
        $person = new Person();
        $person->translate('en')->setName('Jen');
        $person->translate('ru_RU')->name = 'Женя';
        $person->translate('ru_RU')->description = 'multilingual description';

        static::assertSame('Jen', $person->name);
        static::assertSame('Jen', $person->translate()->name);
        static::assertSame('Женя', $person->translate('ru_RU')->name);
        static::assertSame('multilingual description', $person->translate('ru_RU')->description);
        static::assertSame('multilingual description', $person->description);
    }

    public function testTranslatableWithCustomProxy(): void
    {
        $person = new PersonCustom();
        $person->setName('Jen');
        $person->translate('ru_RU')->setName('Женя');
        $person->setDescription('description');
        $person->translate('ru_RU')->setDescription('multilingual description');

        static::assertSame('Jen', $person->getName());
        static::assertSame('Женя', $person->translate('ru_RU')->getName());
        static::assertSame('multilingual description', $person->translate('ru_RU')->getDescription());
        static::assertSame('multilingual description', $person->getDescription());

        $this->em->persist($person);
        $this->em->flush();
        $this->em->clear();

        // retrieve record (translations would be fetched later - by demand)
        $person = $this->em->getRepository(self::PERSON_CUSTOM_PROXY)->findOneBy(['name' => 'Jen']);

        static::assertSame('Jen', $person->getName());
        static::assertSame('Женя', $person->translate('ru_RU')->getName());
        static::assertSame('multilingual description', $person->translate('ru_RU')->getDescription());
        static::assertSame('multilingual description', $person->getDescription());

        // retrieve record with all translations in one query
        $persons = $this->em->getRepository(self::PERSON_CUSTOM_PROXY)
            ->createQueryBuilder('p')
            ->select('p, t')
            ->join('p.translations', 't')
            ->getQuery()
            ->execute();
        $person = $persons[0];

        static::assertSame('Jen', $person->getName());
        static::assertSame('Женя', $person->translate('ru_RU')->getName());
        static::assertSame('multilingual description', $person->translate('ru_RU')->getDescription());
        static::assertSame('multilingual description', $person->getDescription());

        $person->translate('es_ES')->setName('Amigo');

        $this->em->flush();

        // retrieve record with all translations in one query
        $persons = $this->em->getRepository(self::PERSON_CUSTOM_PROXY)
            ->createQueryBuilder('p')
            ->select('p, t')
            ->join('p.translations', 't')
            ->getQuery()
            ->execute();
        $person = $persons[0];

        static::assertSame('Jen', $person->getName());
        static::assertSame('Женя', $person->translate('ru_RU')->getName());
        static::assertSame('Amigo', $person->translate('es_ES')->getName());
        static::assertSame('multilingual description', $person->translate('ru_RU')->getDescription());
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::PERSON, self::PERSON.'Translation',
            self::PERSON_CUSTOM_PROXY, self::PERSON_CUSTOM_PROXY.'Translation',
        ];
    }
}
