<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sortable;

use Doctrine\Common\EventManager;
use Gedmo\Sortable\SortableListener;
use Gedmo\Tests\Sortable\Fixture\Document\Article;
use Gedmo\Tests\Tool\BaseTestCaseMongoODM;

/**
 * These are tests for sortable behavior
 *
 * @author http://github.com/vetalt
 */
final class SortableDocumentTest extends BaseTestCaseMongoODM
{
    public const ARTICLE = Article::class;

    protected function setUp(): void
    {
        parent::setUp();
        $evm = new EventManager();
        $evm->addEventSubscriber(new SortableListener());

        $this->getMockDocumentManager($evm);
        $this->populate();
    }

    public function testInitialPositions(): void
    {
        $repo = $this->dm->getRepository(self::ARTICLE);
        for ($i = 0; $i <= 4; ++$i) {
            $article = $repo->findOneBy(['position' => $i]);
            static::assertSame('article'.$i, $article->getTitle());
        }
    }

    public function testMovePositions(): void
    {
        $repo = $this->dm->getRepository(self::ARTICLE);

        $article = $repo->findOneBy(['position' => 4]);
        $article->setPosition(0);
        $this->dm->flush();

        for ($i = 1; $i <= 4; ++$i) {
            $article = $repo->findOneBy(['position' => $i]);
            static::assertSame('article'.($i - 1), $article->getTitle());
        }
    }

    public function testMoveLastPositions(): void
    {
        $repo = $this->dm->getRepository(self::ARTICLE);

        $article = $repo->findOneBy(['position' => 0]);
        $article->setPosition(-1);
        $this->dm->flush();

        for ($i = 0; $i <= 3; ++$i) {
            $article = $repo->findOneBy(['position' => $i]);
            static::assertSame('article'.($i + 1), $article->getTitle());
        }
        $article = $repo->findOneBy(['position' => 4]);
        static::assertSame('article0', $article->getTitle());
    }

    public function testDeletePositions(): void
    {
        $repo = $this->dm->getRepository(self::ARTICLE);

        $article = $repo->findOneBy(['position' => 0]);
        $this->dm->remove($article);
        $this->dm->flush();

        for ($i = 0; $i <= 3; ++$i) {
            $article = $repo->findOneBy(['position' => $i]);
            static::assertSame('article'.($i + 1), $article->getTitle());
        }
    }

    private function populate(): void
    {
        for ($i = 0; $i <= 4; ++$i) {
            $article = new Article();
            $article->setTitle('article'.$i);
            $this->dm->persist($article);
        }
        $this->dm->flush();
        $this->dm->clear();
    }
}
