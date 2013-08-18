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
        $sport->setBody('Sport article body.');

        $this->assertTrue($sport instanceof Timestampable);

        $sportComment = new Comment();
        $sportComment->setMessage('hello');
        $sportComment->setArticle($sport);
        $sportComment->setStatus(0);

        $this->assertTrue($sportComment instanceof Timestampable);

        $dateCreated = new \DateTime('now');
        $this->em->persist($sport);
        $this->em->persist($sportComment);
        $this->em->flush();

        $sport = $this->em->getRepository(self::ARTICLE)->findOneByTitle('Sport');
        $this->assertEquals(
            $dateCreated->format('Y-m-d'),
            $sport->getCreated()->format('Y-m-d')
        );
        $this->assertEquals(
            $dateCreated->format('Y-m-d H:i'),
            $sport->getUpdated()->format('Y-m-d H:i')
        );
        $this->assertEquals(
            null,
            $sport->getContentChanged()
        );
        $this->assertNull($sport->getPublished());

        $sportComment = $this->em->getRepository(self::COMMENT)->findOneByMessage('hello');
        $this->assertEquals(
            $dateCreated->format('H:i'),
            $sportComment->getModified()->format('H:i')
        );
        $this->assertNull($sportComment->getClosed());

        $sportComment->setStatus(1);
        $published = new Type();
        $published->setTitle('Published');

        $sport->setType($published);
        $datePublished = new \DateTime('now');
        $this->em->persist($sport);
        $this->em->persist($published);
        $this->em->persist($sportComment);
        $this->em->flush();

        $sportComment = $this->em->getRepository(self::COMMENT)->findOneByMessage('hello');
        $this->assertEquals(
            $datePublished->format('Y-m-d H:i'),
            $sportComment->getClosed()->format('Y-m-d H:i')
        );

        $this->assertEquals(
            $datePublished->format('Y-m-d H:i'),
            $sport->getPublished()->format('Y-m-d H:i')
        );

        sleep(1);

        $dateUpdated1 = new \DateTime('now');
        $sport->setTitle('Updated');
        $this->em->persist($sport);
        $this->em->persist($published);
        $this->em->persist($sportComment);
        $this->em->flush();

        $this->assertEquals(
            $dateCreated->format('Y-m-d'),
            $sport->getCreated()->format('Y-m-d')
        );
        $this->assertEquals(
            $dateUpdated1->format('Y-m-d H:i:s'),
            $sport->getUpdated()->format('Y-m-d H:i:s')
        );
        $this->assertEquals(
            $datePublished->format('Y-m-d H:i:s'),
            $sport->getPublished()->format('Y-m-d H:i:s')
        );
        $this->assertEquals(
            $dateUpdated1->format('Y-m-d H:i:s'),
            $sport->getContentChanged()->format('Y-m-d H:i:s')
        );

        sleep(1);

        $dateUpdated2 = new \DateTime('now');
        $sport->setBody('Body updated');
        $this->em->persist($sport);
        $this->em->persist($published);
        $this->em->persist($sportComment);
        $this->em->flush();

        $this->assertEquals(
            $dateCreated->format('Y-m-d'),
            $sport->getCreated()->format('Y-m-d')
        );
        $this->assertEquals(
            $dateUpdated2->format('Y-m-d H:i:s'),
            $sport->getUpdated()->format('Y-m-d H:i:s')
        );
        $this->assertEquals(
            $datePublished->format('Y-m-d H:i:s'),
            $sport->getPublished()->format('Y-m-d H:i:s')
        );
        $this->assertEquals(
            $dateUpdated2->format('Y-m-d H:i:s'),
            $sport->getContentChanged()->format('Y-m-d H:i:s')
        );

        $this->em->clear();
    }

    public function testForcedValues()
    {
        $sport = new Article();
        $sport->setTitle('sport forced');
        $sport->setBody('Sport article body.');
        $sport->setCreated(new \DateTime('2000-01-01'));
        $sport->setUpdated(new \DateTime('2000-01-01 12:00:00'));
        $sport->setContentChanged(new \DateTime('2000-01-01 12:00:00'));

        $this->em->persist($sport);
        $this->em->flush();

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
        $this->assertEquals(
            '2000-01-01 12:00:00',
            $sport->getContentChanged()->format('Y-m-d H:i:s')
        );

        $published = new Type();
        $published->setTitle('Published');

        $sport->setType($published);
        $sport->setPublished(new \DateTime('2000-01-01 12:00:00'));
        $this->em->persist($sport);
        $this->em->persist($published);
        $this->em->flush();

        $sport = $repo->findOneByTitle('sport forced');
        $this->assertEquals(
            '2000-01-01 12:00:00',
            $sport->getPublished()->format('Y-m-d H:i:s')
        );

        $this->em->clear();
    }

    /**
     * @test
     */
    function shouldSolveIssue767()
    {
        $type = new Type;
        $type->setTitle('Published');

        $this->em->persist($type);
        $this->em->flush();
        $this->em->clear();

        $type = $this->em->getReference(self::TYPE, $type->getId());
        $this->assertInstanceOf('Doctrine\ORM\Proxy\Proxy', $type);

        $art = new Article;
        $art->setTitle('Art');
        $art->setBody('body');

        $this->em->persist($art);
        $this->em->flush();

        $art->setType($type);
        $this->em->flush(); // in v2.4.x will work on insert too

        $this->assertNotNull($art->getPublished());
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
