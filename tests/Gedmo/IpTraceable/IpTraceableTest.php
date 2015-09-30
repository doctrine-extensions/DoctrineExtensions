<?php

namespace Gedmo\IpTraceable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use IpTraceable\Fixture\Article;
use IpTraceable\Fixture\Comment;
use IpTraceable\Fixture\Type;

/**
 * These are tests for IpTraceable behavior
 *
 * @author Pierre-Charles Bertineau <pc.bertineau@alterphp.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class IpTraceableTest extends BaseTestCaseORM
{
    const TEST_IP = '34.234.1.10';

    const ARTICLE = "IpTraceable\\Fixture\\Article";
    const COMMENT = "IpTraceable\\Fixture\\Comment";
    const TYPE = "IpTraceable\\Fixture\\Type";

    protected function setUp()
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

        $this->setExpectedException('Gedmo\Exception\InvalidArgumentException');

        $listener->setIpValue('xx.xxx.xx.xxx');
    }

    public function testIpV4()
    {
        $listener = new IpTraceableListener();
        $listener->setIpValue('123.218.45.39');
        $this->assertEquals('123.218.45.39', $listener->getFieldValue(null, null, null));
    }

    public function testIpV6()
    {
        $listener = new IpTraceableListener();
        $listener->setIpValue('2001:0db8:0000:85a3:0000:0000:ac1f:8001');
        $this->assertEquals('2001:0db8:0000:85a3:0000:0000:ac1f:8001', $listener->getFieldValue(null, null, null));
    }

    public function testIpTraceable()
    {
        $sport = new Article();
        $sport->setTitle('Sport');

        $this->assertTrue($sport instanceof IpTraceable);

        $sportComment = new Comment();
        $sportComment->setMessage('hello');
        $sportComment->setArticle($sport);
        $sportComment->setStatus(0);

        $this->assertTrue($sportComment instanceof IpTraceable);

        $this->em->persist($sport);
        $this->em->persist($sportComment);
        $this->em->flush();
        $this->em->clear();

        $sport = $this->em->getRepository(self::ARTICLE)->findOneByTitle('Sport');
        $this->assertEquals(self::TEST_IP, $sport->getCreated());
        $this->assertEquals(self::TEST_IP, $sport->getUpdated());
        $this->assertNull($sport->getPublished());

        $sportComment = $this->em->getRepository(self::COMMENT)->findOneByMessage('hello');
        $this->assertEquals(self::TEST_IP, $sportComment->getModified());
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
        $this->assertEquals(self::TEST_IP, $sportComment->getClosed());

        $this->assertEquals(self::TEST_IP, $sport->getPublished());
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
        $this->assertEquals(self::TEST_IP, $sport->getCreated());
        $this->assertEquals(self::TEST_IP, $sport->getUpdated());

        $published = new Type();
        $published->setTitle('Published');

        $sport->setType($published);
        $sport->setPublished(self::TEST_IP);
        $this->em->persist($sport);
        $this->em->persist($published);
        $this->em->flush();
        $this->em->clear();

        $sport = $repo->findOneByTitle('sport forced');
        $this->assertEquals(self::TEST_IP, $sport->getPublished());
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::ARTICLE,
            self::COMMENT,
            self::TYPE,
        );
    }
}
