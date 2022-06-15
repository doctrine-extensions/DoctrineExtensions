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
use Doctrine\DBAL\Types\Type;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Tests\Translatable\Fixture\MixedValue;
use Gedmo\Tests\Translatable\Fixture\Type\Custom;
use Gedmo\Translatable\Entity\Translation;
use Gedmo\Translatable\TranslatableListener;

/**
 * These are tests for translatable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class MixedValueTranslationTest extends BaseTestCaseORM
{
    public const MIXED = MixedValue::class;
    public const TRANSLATION = Translation::class;

    /**
     * @var TranslatableListener
     */
    private $translatableListener;

    protected function setUp(): void
    {
        parent::setUp();

        if (!Type::hasType('custom')) {
            Type::addType('custom', Custom::class);
        }

        $evm = new EventManager();
        $this->translatableListener = new TranslatableListener();
        $this->translatableListener->setTranslatableLocale('en_us');
        $this->translatableListener->setDefaultLocale('en_us');
        $evm->addEventSubscriber($this->translatableListener);

        $this->getDefaultMockSqliteEntityManager($evm);
        $this->populate();
    }

    public function testFixtureGeneratedTranslations(): void
    {
        $repo = $this->em->getRepository(self::MIXED);
        $mixed = $repo->findOneBy(['id' => 1]);

        static::assertInstanceOf(\DateTime::class, $mixed->getDate());
        static::assertInstanceOf(\stdClass::class, $mixed->getCust());
        static::assertSame('en', $mixed->getCust()->test);
    }

    public function testOtherTranslation(): void
    {
        $repo = $this->em->getRepository(self::MIXED);
        $mixed = $repo->findOneBy(['id' => 1]);

        $this->translatableListener->setTranslatableLocale('de_de');
        $mixed->setDate(new \DateTime('2000-00-00 00:00:00'));
        $cust = new \stdClass();
        $cust->test = 'de';
        $mixed->setCust($cust);

        $this->em->persist($mixed);
        $this->em->flush();
        $this->em->clear();

        $mixed = $repo->findOneBy(['id' => 1]);
        $transRepo = $this->em->getRepository(self::TRANSLATION);
        $translations = $transRepo->findTranslations($mixed);

        static::assertCount(1, $translations);
        static::assertArrayHasKey('de_de', $translations);
        $cust = unserialize($translations['de_de']['cust']);

        static::assertInstanceOf(\stdClass::class, $cust);
        static::assertSame('de', $cust->test);
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::MIXED,
            self::TRANSLATION,
        ];
    }

    private function populate(): void
    {
        $mixedEn = new MixedValue();
        $mixedEn->setDate(new \DateTime());
        $cust = new \stdClass();
        $cust->test = 'en';
        $mixedEn->setCust($cust);

        $this->em->persist($mixedEn);
        $this->em->flush();
        $this->em->clear();
    }
}
