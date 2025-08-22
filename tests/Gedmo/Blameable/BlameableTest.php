<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Blameable;

use Doctrine\Common\EventManager;
use Gedmo\Blameable\BlameableListener;
use Gedmo\Tests\Blameable\Fixture\Entity\Article;
use Gedmo\Tests\Blameable\Fixture\Entity\Comment;
use Gedmo\Tests\Blameable\Fixture\Entity\Type;
use Gedmo\Tests\TestActorProvider;
use Gedmo\Tests\Tool\BaseTestCaseORM;

/**
 * These are tests for Blameable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class BlameableTest extends BaseTestCaseORM
{
    private BlameableListener $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new BlameableListener();
        $this->listener->setUserValue('testuser');

        $evm = new EventManager();
        $evm->addEventSubscriber($this->listener);

        $this->getDefaultMockSqliteEntityManager($evm);
    }

    public function testBlameable(): void
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
        static::assertSame('testuser', $sport->getCreated());
        static::assertSame('testuser', $sport->getUpdated());
        static::assertNull($sport->getPublished());

        $sportComment = $this->em->getRepository(Comment::class)->findOneBy(['message' => 'hello']);
        static::assertSame('testuser', $sportComment->getModified());
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
        static::assertSame('testuser', $sportComment->getClosed());

        static::assertSame('testuser', $sport->getPublished());
    }

    public function testBlameableWithActorProvider(): void
    {
        $this->listener->setActorProvider(new TestActorProvider('testactor'));

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
        static::assertSame('testactor', $sport->getCreated());
        static::assertSame('testactor', $sport->getUpdated());
        static::assertNull($sport->getPublished());

        $sportComment = $this->em->getRepository(Comment::class)->findOneBy(['message' => 'hello']);
        static::assertSame('testactor', $sportComment->getModified());
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
        static::assertSame('testactor', $sportComment->getClosed());

        static::assertSame('testactor', $sport->getPublished());
    }

    public function testForcedValues(): void
    {
        $sport = new Article();
        $sport->setTitle('sport forced');
        $sport->setCreated('myuser');
        $sport->setUpdated('myuser');

        $this->em->persist($sport);
        $this->em->flush();
        $this->em->clear();

        $repo = $this->em->getRepository(Article::class);
        $sport = $repo->findOneBy(['title' => 'sport forced']);
        static::assertSame('myuser', $sport->getCreated());
        static::assertSame('myuser', $sport->getUpdated());

        $published = new Type();
        $published->setTitle('Published');

        $sport->setType($published);
        $sport->setPublished('myuser');
        $this->em->persist($sport);
        $this->em->persist($published);
        $this->em->flush();
        $this->em->clear();

        $sport = $repo->findOneBy(['title' => 'sport forced']);
        static::assertSame('myuser', $sport->getPublished());
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
