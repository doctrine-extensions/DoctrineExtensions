<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sluggable;

use Doctrine\Common\EventManager;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Tests\Sluggable\Fixture\Article;
use Gedmo\Tests\Tool\BaseTestCaseORM;

/**
 * These are tests for sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class TransliterationTest extends BaseTestCaseORM
{
    public const ARTICLE = Article::class;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());

        $this->getDefaultMockSqliteEntityManager($evm);
        $this->populate();
    }

    public function testInsertedNewSlug(): void
    {
        $repo = $this->em->getRepository(self::ARTICLE);

        $lithuanian = $repo->findOneBy(['code' => 'lt']);
        static::assertSame('transliteration-test-usage-uz-lt', $lithuanian->getSlug());

        $bulgarian = $repo->findOneBy(['code' => 'bg']);
        static::assertSame('tova-e-testovo-zaglavie-bg', $bulgarian->getSlug());

        $russian = $repo->findOneBy(['code' => 'ru']);
        static::assertSame('eto-testovyi-zagolovok-ru', $russian->getSlug());

        $german = $repo->findOneBy(['code' => 'de']);
        static::assertSame('fuhren-aktivitaten-haglofs-de', $german->getSlug());
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::ARTICLE,
        ];
    }

    private function populate(): void
    {
        $lithuanian = new Article();
        $lithuanian->setTitle('trąnslįteration tėst ųsąge ūž');
        $lithuanian->setCode('lt');

        $bulgarian = new Article();
        $bulgarian->setTitle('това е тестово заглавие');
        $bulgarian->setCode('bg');

        $russian = new Article();
        $russian->setTitle('это тестовый заголовок');
        $russian->setCode('ru');

        $german = new Article();
        $german->setTitle('führen Aktivitäten Haglöfs');
        $german->setCode('de');

        $this->em->persist($lithuanian);
        $this->em->persist($bulgarian);
        $this->em->persist($russian);
        $this->em->persist($german);
        $this->em->flush();
        $this->em->clear();
    }
}
