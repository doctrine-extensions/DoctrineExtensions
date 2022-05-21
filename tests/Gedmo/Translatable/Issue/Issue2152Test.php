<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Translatable\Issue;

use Doctrine\Common\EventManager;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Tests\Translatable\Fixture\Issue2152\EntityWithTranslatableBoolean;
use Gedmo\Translatable\Entity\Translation;
use Gedmo\Translatable\TranslatableListener;

final class Issue2152Test extends BaseTestCaseORM
{
    private const TRANSLATION = Translation::class;
    private const ENTITY = EntityWithTranslatableBoolean::class;

    /**
     * @var TranslatableListener
     */
    private $translatableListener;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();

        $this->translatableListener = new TranslatableListener();
        $this->translatableListener->setTranslatableLocale('en');
        $this->translatableListener->setDefaultLocale('en');
        $this->translatableListener->setTranslationFallback(true);
        $evm->addEventSubscriber($this->translatableListener);

        $this->getDefaultMockSqliteEntityManager($evm);
    }

    public function testShouldFindInheritedClassTranslations(): void
    {
        // Arrange
        // by default we have English
        $title = 'Hello World';
        $isOperating = '1';

        // operating in germany
        $deTitle = 'Hallo Welt';
        $isOperatingInGermany = '0';

        // but in Ukraine not operating, should fallback to default one
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

        // Act
        $entityInDe = $this->findUsingQueryBuilder('de');
        $entityInUa = $this->findUsingQueryBuilder('ua');

        // Assert

        static::assertSame($deTitle, $entityInDe->getTitle());
        static::assertSame($isOperatingInGermany, $entityInDe->isOperating());

        static::assertSame($title, $entityInUa->getTitle(), 'should fallback to default title if null');
        static::assertSame($isOperating, $entityInUa->isOperating(), ' should fallback to default operating if null');
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::TRANSLATION,
            self::ENTITY,
        ];
    }

    private function findUsingQueryBuilder(string $locale): ?EntityWithTranslatableBoolean
    {
        $this->em->clear();
        $this->translatableListener->setTranslatableLocale($locale);

        $qb = $this->em->createQueryBuilder()->select('e')->from(self::ENTITY, 'e');

        return $qb->getQuery()->getSingleResult();
    }
}
