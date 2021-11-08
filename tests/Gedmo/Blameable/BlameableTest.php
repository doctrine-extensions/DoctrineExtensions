<?php

namespace Gedmo\Tests\Blameable;

use Doctrine\Common\EventManager;
use Gedmo\Blameable\Blameable;
use Gedmo\Blameable\BlameableListener;
use Gedmo\Tests\Blameable\Fixture\Entity\Article;
use Gedmo\Tests\Blameable\Fixture\Entity\Comment;
use Gedmo\Tests\Blameable\Fixture\Entity\Type;
use Gedmo\Tests\Tool\BaseTestCaseORM;

/**
 * These are tests for Blameable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class BlameableTest extends BaseTestCaseORM
{
    public const ARTICLE = Article::class;
    public const COMMENT = Comment::class;
    public const TYPE = Type::class;

    protected function setUp(): void
    {
        parent::setUp();

        $listener = new BlameableListener();
        $listener->setUserValue('testuser');

        $evm = new EventManager();
        $evm->addEventSubscriber($listener);

        $this->getMockSqliteEntityManager($evm);
    }

    public function testBlameable()
    {
        $sport = new Article();
        $sport->setTitle('Sport');

        static::assertInstanceOf(Blameable::class, $sport);

        $sportComment = new Comment();
        $sportComment->setMessage('hello');
        $sportComment->setArticle($sport);
        $sportComment->setStatus(0);

        static::assertInstanceOf(Blameable::class, $sportComment);

        $this->em->persist($sport);
        $this->em->persist($sportComment);
        $this->em->flush();
        $this->em->clear();

        $sport = $this->em->getRepository(self::ARTICLE)->findOneBy(['title' => 'Sport']);
        static::assertEquals('testuser', $sport->getCreated());
        static::assertEquals('testuser', $sport->getUpdated());
        static::assertNull($sport->getPublished());

        $sportComment = $this->em->getRepository(self::COMMENT)->findOneBy(['message' => 'hello']);
        static::assertEquals('testuser', $sportComment->getModified());
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
        static::assertEquals('testuser', $sportComment->getClosed());

        static::assertEquals('testuser', $sport->getPublished());
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
        $sport = $repo->findOneBy(['title' => 'sport forced']);
        static::assertEquals('myuser', $sport->getCreated());
        static::assertEquals('myuser', $sport->getUpdated());

        $published = new Type();
        $published->setTitle('Published');

        $sport->setType($published);
        $sport->setPublished('myuser');
        $this->em->persist($sport);
        $this->em->persist($published);
        $this->em->flush();
        $this->em->clear();

        $sport = $repo->findOneBy(['title' => 'sport forced']);
        static::assertEquals('myuser', $sport->getPublished());
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
