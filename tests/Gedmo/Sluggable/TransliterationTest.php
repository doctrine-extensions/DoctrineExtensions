<?php

namespace Gedmo\Tests\Sluggable;

use Doctrine\Common\EventManager;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Tests\Sluggable\Fixture\Article;
use Gedmo\Tests\Tool\BaseTestCaseORM;

/**
 * These are tests for sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class TransliterationTest extends BaseTestCaseORM
{
    public const ARTICLE = Article::class;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());

        $this->getMockSqliteEntityManager($evm);
        $this->populate();
    }

    public function testInsertedNewSlug()
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

    private function populate()
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

    protected function getUsedEntityFixtures()
    {
        return [
            self::ARTICLE,
        ];
    }
}
