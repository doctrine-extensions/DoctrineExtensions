<?php

namespace Gedmo\Tests\IpTraceable;

use Doctrine\Common\EventManager;
use Gedmo\Exception\InvalidArgumentException;
use Gedmo\IpTraceable\IpTraceable;
use Gedmo\IpTraceable\IpTraceableListener;
use Gedmo\Tests\IpTraceable\Fixture\Article;
use Gedmo\Tests\IpTraceable\Fixture\Comment;
use Gedmo\Tests\IpTraceable\Fixture\Type;
use Gedmo\Tests\Tool\BaseTestCaseORM;

/**
 * These are tests for IpTraceable behavior
 *
 * @author Pierre-Charles Bertineau <pc.bertineau@alterphp.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class IpTraceableTest extends BaseTestCaseORM
{
    public const TEST_IP = '34.234.1.10';

    public const ARTICLE = Article::class;
    public const COMMENT = Comment::class;
    public const TYPE = Type::class;

    protected function setUp(): void
    {
        parent::setUp();

        $listener = new IpTraceableListener();
        $listener->setIpValue(self::TEST_IP);

        $evm = new EventManager();
        $evm->addEventSubscriber($listener);

        $this->getMockSqliteEntityManager($evm);
    }

    public function testInvalidIpShouldThrowInvalidArgumentException()
    {
        $listener = new IpTraceableListener();

        $this->expectException(InvalidArgumentException::class);

        $listener->setIpValue('xx.xxx.xx.xxx');
    }

    public function testIpV4()
    {
        $listener = new IpTraceableListener();
        $listener->setIpValue('123.218.45.39');
        static::assertSame('123.218.45.39', $listener->getFieldValue(null, null, null));
    }

    public function testIpV6()
    {
        $listener = new IpTraceableListener();
        $listener->setIpValue('2001:0db8:0000:85a3:0000:0000:ac1f:8001');
        static::assertSame('2001:0db8:0000:85a3:0000:0000:ac1f:8001', $listener->getFieldValue(null, null, null));
    }

    public function testIpTraceable()
    {
        $sport = new Article();
        $sport->setTitle('Sport');

        static::assertInstanceOf(IpTraceable::class, $sport);

        $sportComment = new Comment();
        $sportComment->setMessage('hello');
        $sportComment->setArticle($sport);
        $sportComment->setStatus(0);

        static::assertInstanceOf(IpTraceable::class, $sportComment);

        $this->em->persist($sport);
        $this->em->persist($sportComment);
        $this->em->flush();
        $this->em->clear();

        $sport = $this->em->getRepository(self::ARTICLE)->findOneBy(['title' => 'Sport']);
        static::assertSame(self::TEST_IP, $sport->getCreated());
        static::assertSame(self::TEST_IP, $sport->getUpdated());
        static::assertNull($sport->getPublished());

        $sportComment = $this->em->getRepository(self::COMMENT)->findOneBy(['message' => 'hello']);
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

        $sportComment = $this->em->getRepository(self::COMMENT)->findOneBy(['message' => 'hello']);
        static::assertSame(self::TEST_IP, $sportComment->getClosed());

        static::assertSame(self::TEST_IP, $sport->getPublished());
    }

    public function testForcedValues()
    {
        $sport = new Article();
        $sport->setTitle('sport forced');
        $sport->setCreated(self::TEST_IP);
        $sport->setUpdated(self::TEST_IP);

        $this->em->persist($sport);
        $this->em->flush();
        $this->em->clear();

        $repo = $this->em->getRepository(self::ARTICLE);
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

    protected function getUsedEntityFixtures()
    {
        return [
            self::ARTICLE,
            self::COMMENT,
            self::TYPE,
        ];
    }
}
