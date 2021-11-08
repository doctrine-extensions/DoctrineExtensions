<?php

namespace Gedmo\Tests\Translatable;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Types\Type;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Tests\Translatable\Fixture\MixedValue;
use Gedmo\Translatable\TranslatableListener;

/**
 * These are tests for translatable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class MixedValueTranslationTest extends BaseTestCaseORM
{
    public const MIXED = 'Gedmo\\Tests\\Translatable\\Fixture\\MixedValue';
    public const TRANSLATION = 'Gedmo\\Translatable\\Entity\\Translation';

    private $translatableListener;

    protected function setUp(): void
    {
        parent::setUp();

        if (!Type::hasType('custom')) {
            Type::addType('custom', 'Gedmo\Tests\Translatable\Fixture\Type\Custom');
        }

        $evm = new EventManager();
        $this->translatableListener = new TranslatableListener();
        $this->translatableListener->setTranslatableLocale('en_us');
        $this->translatableListener->setDefaultLocale('en_us');
        $evm->addEventSubscriber($this->translatableListener);

        $this->getMockSqliteEntityManager($evm);
        $this->populate();
    }

    public function testFixtureGeneratedTranslations()
    {
        $repo = $this->em->getRepository(self::MIXED);
        $mixed = $repo->findOneBy(['id' => 1]);

        static::assertInstanceOf(\DateTime::class, $mixed->getDate());
        static::assertInstanceOf(\stdClass::class, $mixed->getCust());
        static::assertEquals('en', $mixed->getCust()->test);
    }

    public function testOtherTranslation()
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
        static::assertEquals('de', $cust->test);
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::MIXED,
            self::TRANSLATION,
        ];
    }

    private function populate()
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
