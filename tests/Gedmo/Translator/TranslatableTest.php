<?php

namespace Gedmo\Translator;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Translator\Fixture\Person;

/**
 * These are tests for translatable behavior
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 * @package Gedmo.Translatable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TranslatableTest extends BaseTestCaseORM
{
    const PERSON = 'Translator\\Fixture\\Person';

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $this->getMockSqliteEntityManager($evm);
    }

    public function testTranslatable()
    {
        $person = new Person();
        $person->translate()->setName('Jen');
        $person->translate('ru_RU')->setName('Женя');
        $person->setDescription('description');
        $person->translate('ru_RU')->setDescription('multilingual description');

        $this->assertSame('Jen', $person->translate()->getName());
        $this->assertSame('Женя', $person->translate('ru_RU')->getName());
        $this->assertSame('multilingual description', $person->getDescription());
        $this->assertSame('multilingual description', $person->translate()->getDescription());
        $this->assertSame('multilingual description', $person->translate('ru_RU')->getDescription());

        $this->em->persist($person);
        $this->em->flush();
        $this->em->clear();

        // retrieve record (translations would be fetched later - by demand)
        $person = $this->em->getRepository(self::PERSON)->findOneByName('Jen');

        $this->assertSame('Jen', $person->getName());
        $this->assertSame('Jen', $person->translate()->getName());
        $this->assertSame('Женя', $person->translate('ru_RU')->getName());
        $this->assertSame('multilingual description', $person->getDescription());
        $this->assertSame('multilingual description', $person->translate()->getDescription());
        $this->assertSame('multilingual description', $person->translate('ru_RU')->getDescription());

        // retrieve record with all translations in one query
        $persons = $this->em->getRepository(self::PERSON)
            ->createQueryBuilder('p')
            ->select('p, t')
            ->join('p.translations', 't')
            ->getQuery()
            ->execute();
        $person = $persons[0];

        $this->assertSame('Jen', $person->getName());
        $this->assertSame('Jen', $person->translate()->getName());
        $this->assertSame('Женя', $person->translate('ru_RU')->getName());
        $this->assertSame('multilingual description', $person->getDescription());
        $this->assertSame('multilingual description', $person->translate()->getDescription());
        $this->assertSame('multilingual description', $person->translate('ru_RU')->getDescription());

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

        $this->assertSame('Jen', $person->getName());
        $this->assertSame('Jen', $person->translate()->getName());
        $this->assertSame('Женя', $person->translate('ru_RU')->getName());
        $this->assertSame('Amigo', $person->translate('es_ES')->getName());
        $this->assertSame('multilingual description', $person->getDescription());
        $this->assertSame('multilingual description', $person->translate()->getDescription());
        $this->assertSame('multilingual description', $person->translate('ru_RU')->getDescription());
    }

    protected function getUsedEntityFixtures()
    {
        return array(self::PERSON, self::PERSON.'Translation');
    }
}
