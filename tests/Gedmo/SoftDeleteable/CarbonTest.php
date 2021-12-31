<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\SoftDeleteable;

use Carbon\Carbon;
use Carbon\Doctrine\DateTimeType;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Types\Type as DoctrineType;
use Doctrine\DBAL\Types\Types;
use Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter;
use Gedmo\SoftDeleteable\SoftDeleteableListener;
use Gedmo\Tests\SoftDeleteable\Fixture\Entity\Article;
use Gedmo\Tests\SoftDeleteable\Fixture\Entity\Comment;
use Gedmo\Tests\Tool\BaseTestCaseORM;

final class CarbonTest extends BaseTestCaseORM
{
    public const ARTICLE_CLASS = Article::class;
    public const COMMENT_CLASS = Comment::class;
    public const SOFT_DELETEABLE_FILTER_NAME = 'soft-deleteable';

    /**
     * @var SoftDeleteableListener
     */
    private $softDeleteableListener;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $this->softDeleteableListener = new SoftDeleteableListener();
        $evm->addEventSubscriber($this->softDeleteableListener);
        $config = $this->getDefaultConfiguration();
        $config->addFilter(self::SOFT_DELETEABLE_FILTER_NAME, SoftDeleteableFilter::class);
        $this->em = $this->getDefaultMockSqliteEntityManager($evm, $config);
        $this->em->getFilters()->enable(self::SOFT_DELETEABLE_FILTER_NAME);

        DoctrineType::overrideType(Types::DATETIME_MUTABLE, DateTimeType::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        DoctrineType::overrideType(Types::DATETIME_MUTABLE, \Doctrine\DBAL\Types\DateTimeType::class);
    }

    public function testSoftDeleteable(): void
    {
        $repo = $this->em->getRepository(self::ARTICLE_CLASS);
        $commentRepo = $this->em->getRepository(self::COMMENT_CLASS);

        $comment = new Comment();
        $commentField = 'comment';
        $commentValue = 'Comment 1';
        $comment->setComment($commentValue);
        $art0 = new Article();
        $field = 'title';
        $value = 'Title 1';
        $art0->setTitle($value);
        $art0->addComment($comment);

        $this->em->persist($art0);
        $this->em->flush();

        $art = $repo->findOneBy([$field => $value]);

        static::assertNull($art->getDeletedAt());
        static::assertNull($comment->getDeletedAt());

        $this->em->remove($art);
        $this->em->flush();

        $art = $repo->findOneBy([$field => $value]);
        static::assertNull($art);
        $comment = $commentRepo->findOneBy([$commentField => $commentValue]);
        static::assertNull($comment);

        // Now we deactivate the filter so we test if the entity appears in the result
        $this->em->getFilters()->disable(self::SOFT_DELETEABLE_FILTER_NAME);

        $art = $repo->findOneBy([$field => $value]);
        static::assertIsObject($art);
        static::assertIsObject($art->getDeletedAt());
        static::assertInstanceOf(Carbon::class, $art->getDeletedAt());
        $comment = $commentRepo->findOneBy([$commentField => $commentValue]);
        static::assertIsObject($comment);
        static::assertIsObject($comment->getDeletedAt());
        static::assertInstanceOf(Carbon::class, $comment->getDeletedAt());
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::ARTICLE_CLASS,
            self::COMMENT_CLASS,
        ];
    }
}
