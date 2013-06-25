<?php

namespace Gedmo\Translatable;

use Doctrine\Common\EventManager;
use Gedmo\Translatable\TranslatableListener;
use Tool\BaseTestCaseORM;
use Translatable\Fixture\Person;
use Translatable\Fixture\Personal\PersonalArticleTranslation;
use Gedmo\SoftDeleteable\SoftDeleteableListener;

/**
 * These are tests for translatable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SoftdeletedTranslationTest extends BaseTestCaseORM
{
    const PERSON = 'Translatable\Fixture\Person';
    const TRANSLATION = 'Translatable\Fixture\PersonTranslation';

    private $translatableListener;

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $this->translatableListener = new TranslatableListener();
        $this->translatableListener->setTranslatableLocale('en');
        $this->translatableListener->setDefaultLocale('en');
        $evm->addEventSubscriber($this->translatableListener);
        $evm->addEventSubscriber(new SoftDeleteableListener);

        $this->getMockSqliteEntityManager($evm);
        $this->em->getConfiguration()->addFilter('soft-deleteable', 'Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter');
        $this->em->getFilters()->enable('soft-deleteable');
    }

    /**
     * @test
     */
    function shouldNotRemoveTranslationsWhenSoftdeleted()
    {
        $repo = $this->em->getRepository(self::TRANSLATION);

        $person = new Person;
        $person->setName('name en');
        $this->em->persist($person);
        $this->em->flush();

        $id = $person->getId();

        $this->translatableListener->setTranslatableLocale('de');
        $person->setName('name de');
        $this->em->persist($person);
        $this->em->flush();

        $translations = $repo->findAll();
        $this->assertSame(1, count($translations));

        $this->em->remove($person);
        $this->em->flush();

        $person = $this->em->getRepository(self::PERSON)->findOneById($id);
        $this->assertNull($person, "Person should have been softdeleted");

        $translations = $repo->findAll();
        $this->assertSame(1, count($translations), "Number of translations should remain");

        // now disable filter and remove
        $this->em->getFilters()->disable('soft-deleteable');
        $person = $this->em->getRepository(self::PERSON)->findOneById($id);
        $this->assertNotNull($person);

        $this->em->remove($person);
        $this->em->flush();

        $translations = $repo->findAll();
        $this->assertSame(0, count($translations), "Translations should be removed");
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::PERSON,
            self::TRANSLATION
        );
    }
}
