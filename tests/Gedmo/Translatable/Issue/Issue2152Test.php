<?php

namespace Gedmo\Translatable\Issue;

use Doctrine\Common\EventManager;
use Gedmo\Translatable\TranslatableListener;
use Tool\BaseTestCaseORM;
use Gedmo\Translatable\Entity\Translation;
use Translatable\Fixture\Issue2152\EntityWithTranslatableBoolean;

class Issue2152Test extends BaseTestCaseORM
{
    const TRANSLATION = '\Gedmo\Translatable\Entity\Translation';
    const ENTITY = '\Translatable\Fixture\Issue2152\EntityWithTranslatableBoolean';

    /**
     * @var TranslatableListener
     */
    private $translatableListener;

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager();

        $this->translatableListener = new TranslatableListener();
        $this->translatableListener->setTranslatableLocale('en');
        $this->translatableListener->setDefaultLocale('en');
        $this->translatableListener->setTranslationFallback(true);
        $evm->addEventSubscriber($this->translatableListener);

        $this->getMockSqliteEntityManager($evm);
    }

    /**
     * @test
     */
    public function shouldFindInheritedClassTranslations()
    {
        //Arrange
        //by default we have English
        $title = 'Hello World';
        $isOperating = '1';

        //operating in germany
        $deTitle = 'Hallo Welt';
        $isOperatingInGermany = '0';

        //but in Ukraine not operating, should fallback to default one
        $uaTitle = null;
        $isOperatingInUkraine = null;

        $entity = new EntityWithTranslatableBoolean($title, $isOperating);
        $this->em->persist($entity);
        $this->em->flush();

        $entity->translateInLocale('de', $deTitle, $isOperatingInGermany);

        $this->em->persist($entity);
        $this->em->flush();

        $entity->translateInLocale('ua', $uaTitle, $isOperatingInUkraine);

        $this->em->persist($entity);
        $this->em->flush();

        //Act
        $entityInDe = $this->findUsingQueryBuilder('de');
        $entityInUa = $this->findUsingQueryBuilder('ua');

        //Assert

        $this->assertSame($deTitle, $entityInDe->getTitle());
        $this->assertEquals($isOperatingInGermany, $entityInDe->isOperating());

        $this->assertSame($title, $entityInUa->getTitle(), 'should fallback to default title if null');
        $this->assertEquals($isOperating, $entityInUa->isOperating(), ' should fallback to default operating if null');
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::TRANSLATION,
            self::ENTITY,
        );
    }

    /**
     * @param string $locale
     *
     * @return EntityWithTranslatableBoolean|null
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\Persistence\Mapping\MappingException
     */
    private function findUsingQueryBuilder($locale)
    {
        $this->em->clear();
        $this->translatableListener->setTranslatableLocale($locale);

        $qb = $this->em->createQueryBuilder()->select('e')->from(self::ENTITY, 'e');

        return $qb->getQuery()->getSingleResult();
    }
}
