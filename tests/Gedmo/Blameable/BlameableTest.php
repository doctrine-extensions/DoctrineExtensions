<?php

namespace Gedmo\Blameable;

use Blameable\Fixture\Entity\ArticleWithDifferentWhom;
use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Doctrine\Common\Util\Debug,
    Blameable\Fixture\Entity\Article,
    Blameable\Fixture\Entity\Comment,
    Blameable\Fixture\Entity\Type;

/**
 * These are tests for Blameable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 *
 * @group blameable
 */
class BlameableTest extends BaseTestCaseORM
{
    const ARTICLE = "Blameable\\Fixture\\Entity\\Article";
    const COMMENT = "Blameable\\Fixture\\Entity\\Comment";
    const TYPE = "Blameable\\Fixture\\Entity\\Type";
    const ARTICLE_DIFFERENT_WHOM = "Blameable\\Fixture\\Entity\\ArticleWithDifferentWhom";

    /** @var BlameableListener */
    private $listener;

    protected function setUp()
    {
        parent::setUp();

        $this->listener = $listener = new BlameableListener;
        $listener->setUserValue('testuser');

        $evm = new EventManager;
        $evm->addEventSubscriber($listener);

        $this->getMockSqliteEntityManager($evm);
    }

    public function testBlameable()
    {
        $sport = new Article();
        $sport->setTitle('Sport');

        $this->assertTrue($sport instanceof Blameable);

        $sportComment = new Comment();
        $sportComment->setMessage('hello');
        $sportComment->setArticle($sport);
        $sportComment->setStatus(0);

        $this->assertTrue($sportComment instanceof Blameable);

        $this->em->persist($sport);
        $this->em->persist($sportComment);
        $this->em->flush();
        $this->em->clear();

        $sport = $this->em->getRepository(self::ARTICLE)->findOneByTitle('Sport');
        $this->assertEquals('testuser', $sport->getCreated());
        $this->assertEquals('testuser', $sport->getUpdated());
        $this->assertNull($sport->getPublished());

        $sportComment = $this->em->getRepository(self::COMMENT)->findOneByMessage('hello');
        $this->assertEquals('testuser', $sportComment->getModified());
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
        $this->assertEquals('testuser', $sportComment->getClosed());

        $this->assertEquals('testuser', $sport->getPublished());
    }

    public function testForcedValues()
    {
        $sport = new Article();
        $sport->setTitle('sport forced');
        $sport->setCreated('myuser');
        $sport->setUpdated('myuser');

        $this->em->persist($sport);
        $this->em->flush();
        $this->em->clear();

        $repo = $this->em->getRepository(self::ARTICLE);
        $sport = $repo->findOneByTitle('sport forced');
        $this->assertEquals('myuser', $sport->getCreated());
        $this->assertEquals('myuser', $sport->getUpdated());

        $published = new Type();
        $published->setTitle('Published');

        $sport->setType($published);
        $sport->setPublished('myuser');
        $this->em->persist($sport);
        $this->em->persist($published);
        $this->em->flush();
        $this->em->clear();

        $sport = $repo->findOneByTitle('sport forced');
        $this->assertEquals('myuser', $sport->getPublished());
    }

    public function testDifferentWhom()
    {
        $this->listener->setUserValueFor('consumer', 'testconsumer');

        $whom = new ArticleWithDifferentWhom();
        $whom->setTitle('foobar');

        $this->em->persist($whom);
        $this->em->flush();
        $this->em->clear();

        $repo = $this->em->getRepository(self::ARTICLE_DIFFERENT_WHOM);
        $whom = $repo->findOneByTitle('foobar');
        $this->assertEquals('testuser', $whom->getCreatedUser());
        $this->assertEquals('testuser', $whom->getUpdatedUser());
        $this->assertEquals('testconsumer', $whom->getCreatedConsumer());
        $this->assertEquals('testconsumer', $whom->getUpdatedConsumer());

        $this->listener->setUserValueFor('consumer', 'updatedconsumer');
        $this->listener->setUserValue('updateduser');

        $whom->setTitle('updated_foobar');
        $this->em->flush();
        $this->em->clear();
        $whom = $repo->findOneByTitle('updated_foobar');

        $this->assertEquals('testuser', $whom->getCreatedUser());
        $this->assertEquals('updateduser', $whom->getUpdatedUser());
        $this->assertEquals('testconsumer', $whom->getCreatedConsumer());
        $this->assertEquals('updatedconsumer', $whom->getUpdatedConsumer());
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::ARTICLE,
            self::COMMENT,
            self::TYPE,
            self::ARTICLE_DIFFERENT_WHOM
        );
    }
}
