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
use Gedmo\Tests\Translatable\Fixture\Issue1123\BaseEntity;
use Gedmo\Tests\Translatable\Fixture\Issue1123\ChildEntity;
use Gedmo\Translatable\Entity\Translation;
use Gedmo\Translatable\Query\TreeWalker\TranslationWalker;
use Gedmo\Translatable\TranslatableListener;

final class Issue1123Test extends BaseTestCaseORM
{
    public const TRANSLATION = Translation::class;
    public const BASE_ENTITY = BaseEntity::class;
    public const CHILD_ENTITY = ChildEntity::class;

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
        $repo = $this->em->getRepository(self::TRANSLATION);

        $title = 'Hello World';
        $deTitle = 'Hallo Welt';

        // Check that the child class can have translations
        $childEntity = new ChildEntity();
        $childEntity->setChildTitle($title);
        $this->em->persist($childEntity);
        $this->em->flush();

        $childEntity->setTranslatableLocale('de');
        $childEntity->setChildTitle($deTitle);
        $this->em->persist($childEntity);
        $this->em->flush();

        // Clear to be sure...
        $this->em->clear();

        // Find using the repository
        $translations = $repo->findTranslations($childEntity);
        static::assertCount(1, $translations);
        static::assertArrayHasKey('de', $translations);
        static::assertSame(['childTitle' => $deTitle], $translations['de']);

        // find using QueryBuilder
        $qb = $this->em->createQueryBuilder()->select('e')->from(self::CHILD_ENTITY, 'e');

        $query = $qb->getQuery();
        $query->setHint(\Doctrine\ORM\Query::HINT_CUSTOM_OUTPUT_WALKER, TranslationWalker::class);
        $query->setHint(\Gedmo\Translatable\TranslatableListener::HINT_TRANSLATABLE_LOCALE, 'de');
        $query->setHint(\Gedmo\Translatable\TranslatableListener::HINT_FALLBACK, 1);

        $res = $query->getArrayResult();
        static::assertArrayHasKey('id', $res[0]);
        static::assertArrayHasKey('childTitle', $res[0]);
        static::assertArrayHasKey('discr', $res[0]);
        static::assertSame(1, $res[0]['id']);
        static::assertSame($deTitle, $res[0]['childTitle']);
        static::assertSame('child', $res[0]['discr']);
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::TRANSLATION,
            self::BASE_ENTITY,
            self::CHILD_ENTITY,
        ];
    }
}
