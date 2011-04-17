<?php

namespace Gedmo\Timestampable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Doctrine\Common\Util\Debug,
    Timestampable\Fixture\Article,
    Timestampable\Fixture\Comment,
    Timestampable\Fixture\Type;

/**
 * These are tests for Timestampable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Timestampable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TimestampableTest extends BaseTestCaseORM
{
    const ARTICLE = "Timestampable\\Fixture\\Article";
    const COMMENT = "Timestampable\\Fixture\\Comment";
    const TYPE = "Timestampable\\Fixture\\Type";

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $evm->addEventSubscriber(new TimestampableListener);

        $this->getMockSqliteEntityManager($evm);
    }

    public function testTimestampable()
    {
        $sport = new Article();
        $sport->setTitle('Sport');

        $this->assertTrue($sport instanceof Timestampable);

        $sportComment = new Comment();
        $sportComment->setMessage('hello');
        $sportComment->setArticle($sport);
        $sportComment->setStatus(0);

        $this->assertTrue($sportComment instanceof Timestampable);

        $date = new \DateTime('now');
        $this->em->persist($sport);
        $this->em->persist($sportComment);
        $this->em->flush();
        $this->em->clear();

        $sport = $this->em->getRepository(self::ARTICLE)->findOneByTitle('Sport');
        $this->assertEquals(
            $date->format('Y-m-d 00:00:00'),
            $sport->getCreated()->format('Y-m-d H:i:s')
        );
        $this->assertEquals(
            $date->format('Y-m-d H:i:s'),
            $sport->getUpdated()->format('Y-m-d H:i:s')
        );
        $this->assertEquals(null, $sport->getPublished());

        $sportComment = $this->em->getRepository(self::COMMENT)->findOneByMessage('hello');
        $this->assertEquals(
            $date->format('H:i:s'),
            $sportComment->getModified()->format('H:i:s')
        );
        $this->assertEquals(null, $sportComment->getClosed());

        $sportComment->setStatus(1);
        $published = new Type();
        $published->setTitle('Published');

        $sport->setTitle('Updated');
        $sport->setType($published);
        $date = new \DateTime('now');
        $this->em->persist($sport);
        $this->em->persist($published);
        $this->em->persist($sportComment);
        $this->em->flush();
        $this->em->clear();

        $sportComment = $this->em->getRepository(self::COMMENT)->findOneByMessage('hello');
        $this->assertEquals(
            $date->format('Y-m-d H:i:s'),
            $sportComment->getClosed()->format('Y-m-d H:i:s')
        );

        $sport = $this->em->getRepository(self::ARTICLE)->findOneByTitle('Updated');
        $this->assertEquals(
            $date->format('Y-m-d H:i:s'),
            $sport->getUpdated()->format('Y-m-d H:i:s')
        );
        $this->assertEquals(
            $date->format('Y-m-d H:i:s'),
            $sport->getPublished()->format('Y-m-d H:i:s')
        );
    }

    public function testForcedValues()
    {
        $sport = new Article();
        $sport->setTitle('sport forced');
        $sport->setCreated(new \DateTime('2000-01-01'));
        $sport->setUpdated(new \DateTime('2000-01-01 12:00:00'));

        $this->em->persist($sport);
        $this->em->flush();
        $this->em->clear();

        $repo = $this->em->getRepository(self::ARTICLE);
        $sport = $repo->findOneByTitle('sport forced');
        $this->assertEquals(
            '2000-01-01',
            $sport->getCreated()->format('Y-m-d')
        );
        $this->assertEquals(
            '2000-01-01 12:00:00',
            $sport->getUpdated()->format('Y-m-d H:i:s')
        );

        $published = new Type();
        $published->setTitle('Published');

        $sport->setType($published);
        $sport->setPublished(new \DateTime('2000-01-01 12:00:00'));
        $this->em->persist($sport);
        $this->em->persist($published);
        $this->em->flush();
        $this->em->clear();

        $sport = $repo->findOneByTitle('sport forced');
        $this->assertEquals(
            '2000-01-01 12:00:00',
            $sport->getPublished()->format('Y-m-d H:i:s')
        );
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
