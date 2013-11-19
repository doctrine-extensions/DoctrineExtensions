<?php

namespace Gedmo\IpTraceable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Doctrine\Common\Util\Debug,
    IpTraceable\Fixture\Article,
    IpTraceable\Fixture\Comment,
    IpTraceable\Fixture\Type;

/**
 * These are tests for IpTraceable behavior
 *
 * @author Pierre-Charles Bertineau <pc.bertineau@alterphp.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class IpTraceableTest extends BaseTestCaseORM
{
    const TEST_IP = 'test-ip';

    const ARTICLE = "IpTraceable\\Fixture\\Article";
    const COMMENT = "IpTraceable\\Fixture\\Comment";
    const TYPE = "IpTraceable\\Fixture\\Type";

    protected function setUp()
    {
        parent::setUp();

        $listener = new IpTraceableListener;
        $listener->setIpValue(self::TEST_IP);

        $evm = new EventManager;
        $evm->addEventSubscriber($listener);

        $this->getMockSqliteEntityManager($evm);
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
            self::TYPE
        );
    }
}
