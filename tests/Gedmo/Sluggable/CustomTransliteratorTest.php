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
final class CustomTransliteratorTest extends BaseTestCaseORM
{
    public const ARTICLE = Article::class;

    public function testStandardTransliteratorFailsOnChineseCharacters(): void
    {
        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());

        $this->getDefaultMockSqliteEntityManager($evm);
        $this->populate();

        $repo = $this->em->getRepository(self::ARTICLE);

        $chinese = $repo->findOneBy(['code' => 'zh']);
        static::assertSame('bei-jing-zh', $chinese->getSlug());
    }

    public function testCanUseCustomTransliterator(): void
    {
        $evm = new EventManager();
        $sluggableListener = new SluggableListener();
        $sluggableListener->setTransliterator([Transliterator::class, 'transliterate']);
        $evm->addEventSubscriber($sluggableListener);

        $this->getDefaultMockSqliteEntityManager($evm);
        $this->populate();

        $repo = $this->em->getRepository(self::ARTICLE);

        $chinese = $repo->findOneBy(['code' => 'zh']);
        static::assertSame('bei-jing', $chinese->getSlug());
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::ARTICLE,
        ];
    }

    private function populate(): void
    {
        $chinese = new Article();
        $chinese->setTitle('北京');
        $chinese->setCode('zh');
        $this->em->persist($chinese);
        $this->em->flush();
        $this->em->clear();
    }
}

final class Transliterator
{
    public static function transliterate(string $text, string $separator, object $object): string
    {
        return 'Bei Jing';
    }
}
