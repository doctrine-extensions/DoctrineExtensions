<?php

namespace Gedmo\IpTraceable;

use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManager;
use Gedmo\Fixture\IpTraceable\Article;
use Gedmo\Fixture\IpTraceable\Comment;
use Gedmo\Fixture\IpTraceable\Type;
use Gedmo\TestTool\ObjectManagerTestCase;

/**
 * These are tests for IpTraceable behavior
 *
 * @author Pierre-Charles Bertineau <pc.bertineau@alterphp.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class IpTraceableTest extends ObjectManagerTestCase
{
    const TEST_IP = '34.234.1.10';

    const ARTICLE = 'Gedmo\Fixture\IpTraceable\Article';
    const COMMENT = 'Gedmo\Fixture\IpTraceable\Comment';
    const TYPE = 'Gedmo\Fixture\IpTraceable\Type';

    /**
     * @var EntityManager
     */
    private $em;

    protected function setUp()
    {
        $evm = new EventManager();
        $ipTraceableListener = new IpTraceableListener();
        $ipTraceableListener->setIpValue(self::TEST_IP);
        $evm->addEventSubscriber($ipTraceableListener);

        $this->em = $this->createEntityManager($evm);
        $this->createSchema($this->em, array(
            self::ARTICLE,
            self::COMMENT,
            self::TYPE,
        ));
    }

    protected function tearDown()
    {
        $this->releaseEntityManager($this->em);
    }

    public function testInvalidIpShouldThrowInvalidArgumentException()
    {
        $listener = new IpTraceableListener();

        $this->setExpectedException('Gedmo\Exception\InvalidArgumentException');

        $listener->setIpValue('xx.xxx.xx.xxx');
    }

    public function testIpV4()
    {
        $listener = new IpTraceableListener();
        $listener->setIpValue('123.218.45.39');
        $meta = $this->em->getClassMetadata(self::ARTICLE);
        $this->assertEquals('123.218.45.39', $listener->getIpValue($meta, null));
    }

    public function testIpV6()
    {
        $listener = new IpTraceableListener();
        $listener->setIpValue('2001:0db8:0000:85a3:0000:0000:ac1f:8001');
        $meta = $this->em->getClassMetadata(self::ARTICLE);
        $this->assertEquals('2001:0db8:0000:85a3:0000:0000:ac1f:8001', $listener->getIpValue($meta, null));
    }

    public function testIpTraceable()
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

        $sport = $this->em->getRepository(self::ARTICLE)->findOneByTitle('Sport');
        $this->assertSame(self::TEST_IP, $sport->getCreated());
        $this->assertSame(self::TEST_IP, $sport->getUpdated());
        $this->assertNull($sport->getPublished());

        $sportComment = $this->em->getRepository(self::COMMENT)->findOneByMessage('hello');
        $this->assertSame(self::TEST_IP, $sportComment->getModified());
        $this->assertNull($sportComment->getClosed());

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

        $sportComment = $this->em->getRepository(self::COMMENT)->findOneByMessage('hello');
        $this->assertSame(self::TEST_IP, $sportComment->getClosed());

        $this->assertSame(self::TEST_IP, $sport->getPublished());
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
        $sport = $repo->findOneByTitle('sport forced');
        $this->assertSame(self::TEST_IP, $sport->getCreated());
        $this->assertSame(self::TEST_IP, $sport->getUpdated());

        $published = new Type();
        $published->setTitle('Published');

        $sport->setType($published);
        $sport->setPublished(self::TEST_IP);
        $this->em->persist($sport);
        $this->em->persist($published);
        $this->em->flush();
        $this->em->clear();

        $sport = $repo->findOneByTitle('sport forced');
        $this->assertSame(self::TEST_IP, $sport->getPublished());
    }
}
