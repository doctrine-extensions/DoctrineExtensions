<?php

namespace Gedmo\Translatable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Doctrine\ORM\Query;
use Gedmo\Translatable\Query\TreeWalker\TranslationWalker;
use Translatable\Fixture\Issue\Person;
use Translatable\Fixture\Issue\PersonTranslation;
use Translatable\Fixture\Issue\Staff;
use Translatable\Fixture\Issue\Student;

/**
 * These are tests for translation query walker
 */
class IssueTest extends BaseTestCaseORM
{
    private $id;

    private $translatableListener;

    const ROLE_EN = 'Teacher';
    const ROLE_FR = 'Professeur';
    const NAME = 'Moroine';

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager();
        $this->translatableListener = new TranslatableListener();
        $this->translatableListener->setTranslatableLocale('en');
        $this->translatableListener->setDefaultLocale('en');
        $evm->addEventSubscriber($this->translatableListener);

        $this->getMockSqliteEntityManager($evm);
        $this->populate();
    }

    /**
     * It fails
     */
    public function testHydrateSubClassTranslationViaHint()
    {
        $query = $this->getSearchQuery();

        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, TranslationWalker::class)
            ->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE, 'fr');

        $person = $query->getOneOrNullResult();

        static::assertEquals(self::ROLE_FR, $person->getRole());
    }

    /**
     * It fails
     */
    public function testHydrateSubClassTranslationViaSwitchLocaleWithoutClearEntityManager()
    {
        $query = $this->getSearchQuery();
        $this->translatableListener->setTranslatableLocale('fr');

        $person = $query->getOneOrNullResult();

        static::assertEquals(self::ROLE_FR, $person->getRole());
    }

    /**
     * It success
     */
    public function testHydrateSubClassTranslationViaSwitchLocaleWithClearEntityManager()
    {
        $query = $this->getSearchQuery();
        $this->translatableListener->setTranslatableLocale('fr');

        $this->em->clear();

        $person = $query->getOneOrNullResult();

        static::assertEquals(self::ROLE_FR, $person->getRole());
    }

    /**
     * It success
     */
    public function testHydrateSubClassTranslationAlreadyManaged()
    {
        $this->anExternalFunctionThatLoadTheObjectWithoutClearEntityManager();

        $query = $this->getSearchQuery();
        $person = $query->getOneOrNullResult();
        $this->translatableListener->setTranslatableLocale('fr');

        static::assertEquals(self::ROLE_FR, $person->getRole());
    }

    private function anExternalFunctionThatLoadTheObjectWithoutClearEntityManager()
    {
        $this->getSearchQuery()->getOneOrNullResult();
    }

    /**
     * @return Query
     */
    private function getSearchQuery()
    {
        $query = $this->em->createQueryBuilder();
        $query->select('p')
            ->from(Person::class, 'p')
            ->where($query->expr()->eq('p.id', ':id'))
            ->setParameter('id', $this->id);

        return $query->getQuery();
    }

    protected function getUsedEntityFixtures()
    {
        return [
            Person::class,
            Staff::class,
            Student::class,
            PersonTranslation::class,
        ];
    }

    public function populate()
    {
        $staff = new Staff();
        $staff->setName(self::NAME);
        $staff->setRole(self::ROLE_EN);
        $staff->setTranslatableLocale('en');

        $this->em->persist($staff);
        $this->em->flush();

        $staff->setRole(self::ROLE_FR);
        $staff->setTranslatableLocale('fr');
        $this->em->persist($staff);
        $this->em->flush();

        $this->em->clear();

        $this->id = $staff->getId();
    }
}
