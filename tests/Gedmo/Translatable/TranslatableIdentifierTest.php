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
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Tests\Translatable\Fixture\StringIdentifier;
use Gedmo\Translatable\Entity\Translation;
use Gedmo\Translatable\TranslatableListener;

/**
 * These are tests for translatable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class TranslatableIdentifierTest extends BaseTestCaseORM
{
    public const FIXTURE = StringIdentifier::class;
    public const TRANSLATION = Translation::class;

    /**
     * @var string|null
     */
    private $testObjectId;

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
    }

    public function testShouldHandleStringIdentifier(): void
    {
        $object = new StringIdentifier();
        $object->setTitle('title in en');
        $object->setUid(md5(self::FIXTURE.time()));

        $this->em->persist($object);
        $this->em->flush();
        $this->em->clear();
        $this->testObjectId = $object->getUid();

        $repo = $this->em->getRepository(self::TRANSLATION);
        $object = $this->em->find(self::FIXTURE, $this->testObjectId);

        $translations = $repo->findTranslations($object);
        static::assertCount(0, $translations);

        $object = $this->em->find(self::FIXTURE, $this->testObjectId);
        $object->setTitle('title in de');
        $object->setTranslatableLocale('de_de');

        $this->em->persist($object);
        $this->em->flush();
        $this->em->clear();

        $repo = $this->em->getRepository(self::TRANSLATION);

        // test the entity load by translated title
        $object = $repo->findObjectByTranslatedField(
            'title',
            'title in de',
            self::FIXTURE
        );

        static::assertSame($this->testObjectId, $object->getUid());

        $translations = $repo->findTranslations($object);
        static::assertCount(1, $translations);
        static::assertArrayHasKey('de_de', $translations);

        static::assertArrayHasKey('title', $translations['de_de']);
        static::assertSame('title in de', $translations['de_de']['title']);

        // dql test object hydration
        $q = $this->em
            ->createQuery('SELECT si FROM '.self::FIXTURE.' si WHERE si.uid = :id')
            ->setParameter('id', $this->testObjectId)
            ->disableResultCache()
        ;
        $data = $q->getResult();
        static::assertCount(1, $data);
        $object = $data[0];
        static::assertSame('title in en', $object->getTitle());

        $this->em->clear(); // based on 2.3.0 it caches in identity map
        $this->translatableListener->setTranslatableLocale('de_de');
        $data = $q->getResult();
        static::assertCount(1, $data);
        $object = $data[0];
        static::assertSame('title in de', $object->getTitle());
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::FIXTURE,
            self::TRANSLATION,
        ];
    }
}
