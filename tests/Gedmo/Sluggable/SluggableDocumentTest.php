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
use Gedmo\Tests\Sluggable\Fixture\Document\Article;
use Gedmo\Tests\Tool\BaseTestCaseMongoODM;

/**
 * These are tests for sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class SluggableDocumentTest extends BaseTestCaseMongoODM
{
    public const ARTICLE = Article::class;

    protected function setUp(): void
    {
        parent::setUp();
        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());

        $this->getDefaultDocumentManager($evm);
        $this->populate();
    }

    public function testSlugGeneration(): void
    {
        // test insert
        $repo = $this->dm->getRepository(self::ARTICLE);
        $article = $repo->findOneBy(['title' => 'My Title']);

        static::assertSame('my-title-the-code', $article->getSlug());

        // test update
        $article->setTitle('New Title');

        $this->dm->persist($article);
        $this->dm->flush();
        $this->dm->clear();

        $article = $repo->findOneBy(['title' => 'New Title']);
        static::assertSame('new-title-the-code', $article->getSlug());
    }

    public function testUniqueSlugGeneration(): void
    {
        for ($i = 0; $i < 12; ++$i) {
            $article = new Article();
            $article->setTitle('My Title');
            $article->setCode('The Code');

            $this->dm->persist($article);
            $this->dm->flush();
            $this->dm->clear();
            static::assertSame('my-title-the-code-'.($i + 1), $article->getSlug());
        }
    }

    public function testGithubIssue57(): void
    {
        // slug matched by prefix
        $article = new Article();
        $article->setTitle('my');
        $article->setCode('slug');
        $this->dm->persist($article);

        $article2 = new Article();
        $article2->setTitle('my');
        $article2->setCode('s');
        $this->dm->persist($article2);

        $this->dm->flush();
        static::assertSame('my-s', $article2->getSlug());
    }

    private function populate(): void
    {
        $art0 = new Article();
        $art0->setTitle('My Title');
        $art0->setCode('The Code');

        $this->dm->persist($art0);
        $this->dm->flush();
        $this->dm->clear();
    }
}
