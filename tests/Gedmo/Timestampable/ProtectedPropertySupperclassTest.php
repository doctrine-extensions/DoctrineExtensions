<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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

    public function testProtectedProperty(): void
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

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::TRANSLATION,
            self::SUPERCLASS,
        ];
    }
}
