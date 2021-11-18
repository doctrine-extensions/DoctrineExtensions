<?php

namespace Gedmo\Tests\Timestampable;

use Doctrine\Common\EventManager;
use Gedmo\Tests\Timestampable\Fixture\SupperClassExtension;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Timestampable\TimestampableListener;
use Gedmo\Translatable\Entity\Translation;
use Gedmo\Translatable\TranslatableListener;

/**
 * These are tests for Timestampable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class ProtectedPropertySupperclassTest extends BaseTestCaseORM
{
    public const SUPERCLASS = SupperClassExtension::class;
    public const TRANSLATION = Translation::class;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $translatableListener = new TranslatableListener();
        $translatableListener->setTranslatableLocale('en_US');
        $evm->addEventSubscriber($translatableListener);
        $evm->addEventSubscriber(new TimestampableListener());

        $this->getDefaultMockSqliteEntityManager($evm);
    }

    public function testProtectedProperty()
    {
        $test = new SupperClassExtension();
        $test->setName('name');
        $test->setTitle('title');

        $this->em->persist($test);
        $this->em->flush();
        $this->em->clear();

        $repo = $this->em->getRepository(self::TRANSLATION);
        $translations = $repo->findTranslations($test);
        static::assertCount(0, $translations);

        static::assertNotNull($test->getCreatedAt());
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::TRANSLATION,
            self::SUPERCLASS,
        ];
    }
}
