<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\IpTraceable;

use Doctrine\Common\EventManager;
use Gedmo\IpTraceable\IpTraceableListener;
use Gedmo\Tests\IpTraceable\Fixture\Document\Article;
use Gedmo\Tests\IpTraceable\Fixture\Document\Type;
use Gedmo\Tests\Tool\BaseTestCaseMongoODM;

/**
 * These are tests for IpTraceable behavior ODM implementation
 *
 * @author Pierre-Charles Bertineau <pc.bertineau@alterphp.com>
 */
final class IpTraceableDocumentTest extends BaseTestCaseMongoODM
{
    public const TEST_IP = '34.234.1.10';

    public const ARTICLE = Article::class;
    public const TYPE = Type::class;

    protected function setUp(): void
    {
        parent::setUp();

        $listener = new IpTraceableListener();
        $listener->setIpValue(self::TEST_IP);

        $evm = new EventManager();
        $evm->addEventSubscriber($listener);

        $this->getDefaultDocumentManager($evm);
        $this->populate();
    }

    public function testIpTraceable(): void
    {
        $repo = $this->dm->getRepository(self::ARTICLE);
        $article = $repo->findOneBy(['title' => 'IpTraceable Article']);

        static::assertSame(self::TEST_IP, $article->getCreated());
        static::assertSame(self::TEST_IP, $article->getUpdated());

        $published = new Type();
        $published->setIdentifier('published');
        $published->setTitle('Published');

        $article->setType($published);
        $this->dm->persist($article);
        $this->dm->persist($published);
        $this->dm->flush();
        $this->dm->clear();

        $article = $repo->findOneBy(['title' => 'IpTraceable Article']);

        static::assertSame(self::TEST_IP, $article->getPublished());
        static::assertSame(self::TEST_IP, $article->getCreated());
    }

    public function testForcedValues(): void
    {
        $sport = new Article();
        $sport->setTitle('sport forced');
        $sport->setCreated(self::TEST_IP);
        $sport->setUpdated(self::TEST_IP);

        $this->dm->persist($sport);
        $this->dm->flush();
        $this->dm->clear();

        $repo = $this->dm->getRepository(self::ARTICLE);
        $sport = $repo->findOneBy(['title' => 'sport forced']);
        static::assertSame(self::TEST_IP, (string) $sport->getCreated());
        static::assertSame(self::TEST_IP, $sport->getUpdated());

        $published = new Type();
        $published->setIdentifier('published');
        $published->setTitle('Published');

        $sport->setType($published);
        $sport->setPublished(self::TEST_IP);
        $this->dm->persist($sport);
        $this->dm->persist($published);
        $this->dm->flush();
        $this->dm->clear();

        $sport = $repo->findOneBy(['title' => 'sport forced']);
        static::assertSame(self::TEST_IP, $sport->getPublished());
    }

    private function populate(): void
    {
        $art0 = new Article();
        $art0->setTitle('IpTraceable Article');

        $this->dm->persist($art0);
        $this->dm->flush();
        $this->dm->clear();
    }
}
