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
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Tests\Translatable\Fixture\Company;
use Gedmo\Translatable\Entity\Translation;
use Gedmo\Translatable\Query\TreeWalker\TranslationWalker;
use Gedmo\Translatable\TranslatableListener;

final class TranslatableWithEmbeddedTest extends BaseTestCaseORM
{
    public const FIXTURE = Company::class;
    public const TRANSLATION = Translation::class;

    public const TREE_WALKER_TRANSLATION = TranslationWalker::class;

    /**
     * @var TranslatableListener
     */
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
        $this->populate();
    }

    public function populate(): void
    {
        $entity = new Company();
        $entity->setTitle('test');
        $entity->getLink()->setWebsite('website');
        $entity->getLink()->setFacebook('facebook');

        $this->em->persist($entity);
        $this->em->flush();

        $entity->setTranslatableLocale('de');
        $entity->setTitle('test-de');
        $entity->getLink()->setWebsite('website-de');
        $entity->getLink()->setFacebook('facebook-de');

        $this->em->persist($entity);
        $this->em->flush();
        $this->em->clear();
    }

    public function testTranslate(): void
    {
        /** @var EntityRepository $repo */
        $repo = $this->em->getRepository(self::FIXTURE);

        /** @var Company $entity */
        $entity = $repo->findOneBy(['id' => 1]);

        $repo = $this->em->getRepository(self::TRANSLATION);

        $translations = $repo->findTranslations($entity);

        static::assertArrayHasKey('de', $translations);
        static::assertSame('test-de', $translations['de']['title']);
        static::assertSame('test', $entity->getTitle());

        static::assertSame('website-de', $translations['de']['link.website']);
        static::assertSame('website', $entity->getLink()->getWebsite());

        static::assertSame('facebook-de', $translations['de']['link.facebook']);
        static::assertSame('facebook', $entity->getLink()->getFacebook());

        $this->em->clear();

        $this->translatableListener->setTranslatableLocale('de');
        $repo = $this->em->getRepository(self::FIXTURE);
        $entity = $repo->findOneBy(['id' => $entity->getId()]);

        static::assertSame('website-de', $entity->getLink()->getWebsite());
        static::assertSame('facebook-de', $entity->getLink()->getFacebook());
    }

    public function testQueryWalker(): void
    {
        $dql = 'SELECT f FROM '.self::FIXTURE.' f';

        $q = $this->em->createQuery($dql);
        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, self::TREE_WALKER_TRANSLATION);

        $this->translatableListener->setTranslatableLocale('de');

        $result = $q->getArrayResult();

        static::assertCount(1, $result);
        static::assertSame('test-de', $result[0]['title']);
        static::assertSame('website-de', $result[0]['link.website']);
        static::assertSame('facebook-de', $result[0]['link.facebook']);
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::FIXTURE,
            self::TRANSLATION,
        ];
    }
}
