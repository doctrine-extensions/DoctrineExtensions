<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Translatable\Issue;

use Doctrine\Common\EventManager;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Tests\Translatable\Fixture\Issue2167\Article;
use Gedmo\Translatable\Entity\Translation;
use Gedmo\Translatable\TranslatableListener;

class Issue2167Test extends BaseTestCaseORM
{
    private const TRANSLATION = Translation::class;
    private const ENTITY = Article::class;

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
        $this->translatableListener->setTranslationFallback(false);
        $evm->addEventSubscriber($this->translatableListener);

        $this->getDefaultMockSqliteEntityManager($evm);
    }

    public function testShouldFindInheritedClassTranslations(): void
    {
        $enTitle = 'My english title';
        $deTitle = 'My german title';

        // English
        $entity = new Article();
        $entity->setTitle($enTitle);
        $entity->setLocale('en');
        $this->em->persist($entity);
        $this->em->flush();

        // German
        $entity->setLocale('de');
        $entity->setTitle($deTitle);
        $this->em->flush();

        // Find with default translation value as null value (default setting)
        $entityInEn = $this->findUsingQueryBuilder('en');
        $entityInDe = $this->findUsingQueryBuilder('de');
        $entityInFr = $this->findUsingQueryBuilder('fr');

        static::assertSame($enTitle, $entityInEn->getTitle());
        static::assertSame($deTitle, $entityInDe->getTitle());
        static::assertNull($entityInFr->getTitle());

        // Find with default translation value as empty string
        $this->translatableListener->setDefaultTranslationValue('');

        $entityInEn = $this->findUsingQueryBuilder('en');
        $entityInDe = $this->findUsingQueryBuilder('de');
        $entityInFr = $this->findUsingQueryBuilder('fr');

        static::assertSame($enTitle, $entityInEn->getTitle());
        static::assertSame($deTitle, $entityInDe->getTitle());
        static::assertSame('', $entityInFr->getTitle());

        // Find with default translation value as not empty string
        $this->translatableListener->setDefaultTranslationValue('no_translated');

        $entityInEn = $this->findUsingQueryBuilder('en');
        $entityInDe = $this->findUsingQueryBuilder('de');
        $entityInFr = $this->findUsingQueryBuilder('fr');

        static::assertSame($enTitle, $entityInEn->getTitle());
        static::assertSame($deTitle, $entityInDe->getTitle());
        static::assertSame('no_translated', $entityInFr->getTitle());
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::TRANSLATION,
            self::ENTITY,
        ];
    }

    private function findUsingQueryBuilder(string $locale): ?Article
    {
        $this->em->clear();
        $this->translatableListener->setTranslatableLocale($locale);

        $qb = $this->em->createQueryBuilder()->select('e')->from(self::ENTITY, 'e');

        return $qb->getQuery()->getSingleResult();
    }
}
