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
use Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter;
use Gedmo\SoftDeleteable\SoftDeleteableListener;
use Gedmo\Tests\Blameable\Fixture\Entity\Article;
use Gedmo\Tests\Blameable\Fixture\Entity\Comment;
use Gedmo\Tests\Blameable\Fixture\Entity\Type;
use Gedmo\Tests\Clock;
use Gedmo\Tests\TestActorProvider;
use Gedmo\Tests\Tool\BaseTestCaseORM;

/**
 * These are tests for Blameable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class BlameableWithSoftDeleteTest extends BaseTestCaseORM
{
    private const SOFT_DELETEABLE_FILTER_NAME = 'soft-deleteable';

    private BlameableListener $blameableListener;
    private SoftDeleteableListener $softDeleteableListener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->blameableListener = new BlameableListener();
        $this->blameableListener->setUserValue('testuser');

        $this->softDeleteableListener = new SoftDeleteableListener();
        $this->softDeleteableListener->setClock(new Clock());

        $evm = new EventManager();
        $evm->addEventSubscriber($this->blameableListener);
        $evm->addEventSubscriber($this->softDeleteableListener);

        $config = $this->getDefaultConfiguration();
        $config->addFilter(self::SOFT_DELETEABLE_FILTER_NAME, SoftDeleteableFilter::class);
        $this->em = $this->getDefaultMockSqliteEntityManager($evm, $config);
        $this->em->getFilters()->enable(self::SOFT_DELETEABLE_FILTER_NAME);
    }

    public function testBlameable(): void
    {
        $article = new Article();
        $article->setTitle('Sport');

        $comment = new Comment();
        $comment->setMessage('hello');
        $comment->setArticle($article);
        $comment->setStatus(0);

        $this->em->persist($article);
        $this->em->persist($comment);
        $this->em->flush();
        $this->em->clear();

        $article = $this->em->getRepository(Article::class)->findOneBy(['title' => 'Sport']);
        static::assertSame('testuser', $article->getCreated());
        static::assertSame('testuser', $article->getUpdated());
        static::assertNull($article->getPublished());

        $comment = $this->em->getRepository(Comment::class)->findOneBy(['message' => 'hello']);
        static::assertSame('testuser', $comment->getModified());
        static::assertNull($comment->getClosed());

        $comment->setStatus(1);
        $type = new Type();
        $type->setTitle('Published');

        $article->setTitle('Updated');
        $article->setType($type);
        $this->em->persist($article);
        $this->em->persist($type);
        $this->em->persist($comment);
        $this->em->flush();
        $this->em->clear();

        $comment = $this->em->getRepository(Comment::class)->findOneBy(['message' => 'hello']);
        static::assertSame('testuser', $comment->getClosed());
        static::assertSame('testuser', $article->getPublished());

        // Now delete event
        $article = $this->em->getRepository(Article::class)->findOneBy(['title' => 'Updated']);
        $this->em->remove($article);
        $this->em->flush();

        static::assertTrue($article->isDeleted());
        static::assertSame('testuser', $article->getDeleted());
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
