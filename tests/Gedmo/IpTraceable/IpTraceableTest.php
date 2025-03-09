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
use Doctrine\Persistence\Mapping\ClassMetadata;
use Gedmo\Exception\InvalidArgumentException;
use Gedmo\IpTraceable\IpTraceableListener;
use Gedmo\IpTraceable\Mapping\Event\IpTraceableAdapter;
use Gedmo\Tests\IpTraceable\Fixture\Article;
use Gedmo\Tests\IpTraceable\Fixture\Comment;
use Gedmo\Tests\IpTraceable\Fixture\Type;
use Gedmo\Tests\TestIpAddressProvider;
use Gedmo\Tests\Tool\BaseTestCaseORM;

/**
 * These are tests for IpTraceable behavior
 *
 * @author Pierre-Charles Bertineau <pc.bertineau@alterphp.com>
 */
final class IpTraceableTest extends BaseTestCaseORM
{
    private const TEST_IP = '34.234.1.10';
    private const TEST_PROVIDER_IP = '34.234.2.10';

    private IpTraceableListener $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new IpTraceableListener();
        $this->listener->setIpValue(self::TEST_IP);

        $evm = new EventManager();
        $evm->addEventSubscriber($this->listener);

        $this->getDefaultMockSqliteEntityManager($evm);
    }

    public function testInvalidIpShouldThrowInvalidArgumentException(): void
    {
        $listener = new IpTraceableListener();

        $this->expectException(InvalidArgumentException::class);

        $listener->setIpValue('xx.xxx.xx.xxx');
    }

    public function testIpV4(): void
    {
        $listener = new IpTraceableListener();
        $listener->setIpValue('123.218.45.39');
        static::assertSame('123.218.45.39', $listener->getFieldValue(
            static::createStub(ClassMetadata::class),
            'ip',
            static::createStub(IpTraceableAdapter::class)
        ));
    }

    public function testIpV6(): void
    {
        $listener = new IpTraceableListener();
        $listener->setIpValue('2001:0db8:0000:85a3:0000:0000:ac1f:8001');
        static::assertSame('2001:0db8:0000:85a3:0000:0000:ac1f:8001', $listener->getFieldValue(
            static::createStub(ClassMetadata::class),
            'ip',
            static::createStub(IpTraceableAdapter::class)
        ));
    }

    public function testIpTraceable(): void
    {
        $sport = new Article();
        $sport->setTitle('Sport');

        $sportComment = new Comment();
        $sportComment->setMessage('hello');
        $sportComment->setArticle($sport);
        $sportComment->setStatus(0);

        $this->em->persist($sport);
        $this->em->persist($sportComment);
        $this->em->flush();
        $this->em->clear();

        $sport = $this->em->getRepository(Article::class)->findOneBy(['title' => 'Sport']);
        static::assertSame(self::TEST_IP, $sport->getCreated());
        static::assertSame(self::TEST_IP, $sport->getUpdated());
        static::assertNull($sport->getPublished());

        $sportComment = $this->em->getRepository(Comment::class)->findOneBy(['message' => 'hello']);
        static::assertSame(self::TEST_IP, $sportComment->getModified());
        static::assertNull($sportComment->getClosed());

        $sportComment->setStatus(1);
        $published = new Type();
        $published->setTitle('Published');

        $sport->setTitle('Updated');
        $sport->setType($published);
        $this->em->persist($sport);
        $this->em->persist($published);
        $this->em->persist($sportComment);
        $this->em->flush();
        $this->em->clear();

        $sportComment = $this->em->getRepository(Comment::class)->findOneBy(['message' => 'hello']);
        static::assertSame(self::TEST_IP, $sportComment->getClosed());

        static::assertSame(self::TEST_IP, $sport->getPublished());
    }

    public function testIpTraceableWithProvider(): void
    {
        $this->listener->setIpAddressProvider(new TestIpAddressProvider(self::TEST_PROVIDER_IP));

        $sport = new Article();
        $sport->setTitle('Sport');

        $sportComment = new Comment();
        $sportComment->setMessage('hello');
        $sportComment->setArticle($sport);
        $sportComment->setStatus(0);

        $this->em->persist($sport);
        $this->em->persist($sportComment);
        $this->em->flush();
        $this->em->clear();

        $sport = $this->em->getRepository(Article::class)->findOneBy(['title' => 'Sport']);
        static::assertSame(self::TEST_PROVIDER_IP, $sport->getCreated());
        static::assertSame(self::TEST_PROVIDER_IP, $sport->getUpdated());
        static::assertNull($sport->getPublished());

        $sportComment = $this->em->getRepository(Comment::class)->findOneBy(['message' => 'hello']);
        static::assertSame(self::TEST_PROVIDER_IP, $sportComment->getModified());
        static::assertNull($sportComment->getClosed());

        $sportComment->setStatus(1);
        $published = new Type();
        $published->setTitle('Published');

        $sport->setTitle('Updated');
        $sport->setType($published);
        $this->em->persist($sport);
        $this->em->persist($published);
        $this->em->persist($sportComment);
        $this->em->flush();
        $this->em->clear();

        $sportComment = $this->em->getRepository(Comment::class)->findOneBy(['message' => 'hello']);
        static::assertSame(self::TEST_PROVIDER_IP, $sportComment->getClosed());

        static::assertSame(self::TEST_PROVIDER_IP, $sport->getPublished());
    }

    public function testForcedValues(): void
    {
        $sport = new Article();
        $sport->setTitle('sport forced');
        $sport->setCreated(self::TEST_IP);
        $sport->setUpdated(self::TEST_IP);

        $this->em->persist($sport);
        $this->em->flush();
        $this->em->clear();

        $repo = $this->em->getRepository(Article::class);
        $sport = $repo->findOneBy(['title' => 'sport forced']);
        static::assertSame(self::TEST_IP, $sport->getCreated());
        static::assertSame(self::TEST_IP, $sport->getUpdated());

        $published = new Type();
        $published->setTitle('Published');

        $sport->setType($published);
        $sport->setPublished(self::TEST_IP);
        $this->em->persist($sport);
        $this->em->persist($published);
        $this->em->flush();
        $this->em->clear();

        $sport = $repo->findOneBy(['title' => 'sport forced']);
        static::assertSame(self::TEST_IP, $sport->getPublished());
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            Article::class,
            Comment::class,
            Type::class,
        ];
    }
}
