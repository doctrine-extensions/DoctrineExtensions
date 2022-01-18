<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Blameable;

use Doctrine\Common\EventManager;
use Gedmo\Blameable\BlameableListener;
use Gedmo\Tests\Blameable\Fixture\Entity\SupperClassExtension;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Translatable\Entity\Translation;
use Gedmo\Translatable\TranslatableListener;

/**
 * These are tests for Blameable behavior
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
        $blameableListener = new BlameableListener();
        $blameableListener->setUserValue('testuser');
        $evm->addEventSubscriber($blameableListener);

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

        static::assertSame('testuser', $test->getCreatedBy());
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::TRANSLATION,
            self::SUPERCLASS,
        ];
    }
}
