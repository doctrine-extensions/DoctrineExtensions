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
use Gedmo\Tests\Timestampable\Fixture\Document\Article;
use Gedmo\Tests\Timestampable\Fixture\Document\Type;
use Gedmo\Tests\Tool\BaseTestCaseMongoODM;
use Gedmo\Timestampable\TimestampableListener;

/**
 * These are tests for Timestampable behavior ODM implementation
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class TimestampableDocumentTest extends BaseTestCaseMongoODM
{
    public const ARTICLE = Article::class;
    public const TYPE = Type::class;

    protected function setUp(): void
    {
        parent::setUp();
        $evm = new EventManager();
        $evm->addEventSubscriber(new TimestampableListener());

        $this->getDefaultDocumentManager($evm);
        $this->populate();
    }

    public function testTimestampable(): void
    {
        $repo = $this->dm->getRepository(self::ARTICLE);
        $article = $repo->findOneBy(['title' => 'Timestampable Article']);

        $date = new \DateTime();
        $now = time();
        $created = $article->getCreated()->getTimestamp();
        static::assertTrue($created > $now - 5 && $created < $now + 5); // 5 seconds interval if lag
        static::assertSame(
            $date->format('Y-m-d H:i'),
            $article->getUpdated()->format('Y-m-d H:i')
        );

        $published = new Type();
        $published->setIdentifier('published');
        $published->setTitle('Published');

        $article->setType($published);
        $this->dm->persist($article);
        $this->dm->persist($published);
        $this->dm->flush();
        $this->dm->clear();

        $article = $repo->findOneBy(['title' => 'Timestampable Article']);
        $date = new \DateTime();
        static::assertSame(
            $date->format('Y-m-d H:i'),
            $article->getPublished()->format('Y-m-d H:i')
        );
    }

    public function testForcedValues(): void
    {
        $sport = new Article();
        $sport->setTitle('sport forced');
        $created = strtotime('2000-01-01 12:00:00');
        $sport->setCreated($created);
        $sport->setUpdated(new \DateTime('2000-01-01 12:00:00'));

        $this->dm->persist($sport);
        $this->dm->flush();
        $this->dm->clear();

        $repo = $this->dm->getRepository(self::ARTICLE);
        $sport = $repo->findOneBy(['title' => 'sport forced']);
        static::assertSame(
            $created,
            $sport->getCreated()->getTimestamp()
        );
        static::assertSame(
            '2000-01-01 12:00:00',
            $sport->getUpdated()->format('Y-m-d H:i:s')
        );

        $published = new Type();
        $published->setIdentifier('published');
        $published->setTitle('Published');

        $sport->setType($published);
        $sport->setPublished(new \DateTime('2000-01-01 12:00:00'));
        $this->dm->persist($sport);
        $this->dm->persist($published);
        $this->dm->flush();
        $this->dm->clear();

        $sport = $repo->findOneBy(['title' => 'sport forced']);
        static::assertSame(
            '2000-01-01 12:00:00',
            $sport->getPublished()->format('Y-m-d H:i:s')
        );
    }

    public function testShouldHandleOnChangeWithBooleanValue(): void
    {
        $repo = $this->dm->getRepository(self::ARTICLE);
        $article = $repo->findOneBy(['title' => 'Timestampable Article']);

        static::assertNull($article->getReady());

        $article->setIsReady(true);
        $this->dm->persist($article);
        $this->dm->flush();

        static::assertNotNull($article->getReady());
    }

    private function populate(): void
    {
        $art0 = new Article();
        $art0->setTitle('Timestampable Article');

        $this->dm->persist($art0);
        $this->dm->flush();
        $this->dm->clear();
    }
}
