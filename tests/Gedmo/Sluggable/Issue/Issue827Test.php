<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sluggable\Issue;

use Doctrine\Common\EventManager;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Tests\Sluggable\Fixture\Issue827\Article;
use Gedmo\Tests\Sluggable\Fixture\Issue827\Category;
use Gedmo\Tests\Sluggable\Fixture\Issue827\Comment;
use Gedmo\Tests\Sluggable\Fixture\Issue827\Post;
use Gedmo\Tests\Tool\BaseTestCaseORM;

/**
 * These are tests for Sluggable behavior
 *
 * @author Anders S. Øfsdahl <anders@aloof.no>
 *
 * @see http://www.aloof.no
 */
final class Issue827Test extends BaseTestCaseORM
{
    public const ARTICLE = Article::class;
    public const CATEGORY = Category::class;
    public const COMMENT = Comment::class;
    public const POST = Post::class;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());

        $this->getDefaultMockSqliteEntityManager($evm);
    }

    /**
     * @group issue827
     */
    public function testShouldHandleForeignKeyUniqueBasedSlug(): void
    {
        // Creating categories

        $testCat1 = new Category();
        $testCat1->setTitle('Category1');
        $this->em->persist($testCat1);
        $this->em->flush();

        static::assertSame('category1', $testCat1->getSlug());

        $testCat11 = new Category();
        $testCat11->setTitle('Category1');
        $this->em->persist($testCat11);
        $this->em->flush();

        static::assertSame('category1-1', $testCat11->getSlug());

        $testCat2 = new Category();
        $testCat2->setTitle('Category2');
        $this->em->persist($testCat2);
        $this->em->flush();

        static::assertSame('category2', $testCat2->getSlug());

        // Creating articles

        $test = new Article();
        $test->setTitle('Unique to category 1');
        $test->setCategory($testCat1);
        $this->em->persist($test);
        $this->em->flush();

        static::assertSame('unique-to-category-1', $test->getSlug());

        $test2 = new Article();
        $test2->setTitle('Unique to category 2');
        $test2->setCategory($testCat2);
        $this->em->persist($test2);
        $this->em->flush();

        static::assertSame('unique-to-category-2', $test2->getSlug());

        $test3 = new Article();
        $test3->setTitle('Unique to category 1');
        $test3->setCategory($testCat1);
        $this->em->persist($test3);
        $this->em->flush();

        static::assertSame('unique-to-category-1-1', $test3->getSlug());
    }

    /**
     * @group issue827
     */
    public function testHandlePersistedSlugsForForeignKeyUniqueBased(): void
    {
        // Creating categories

        $testCat1 = new Category();
        $testCat1->setTitle('Category1');
        $this->em->persist($testCat1);

        $testCat11 = new Category();
        $testCat11->setTitle('Category1');
        $this->em->persist($testCat11);

        $testCat2 = new Category();
        $testCat2->setTitle('Category2');
        $this->em->persist($testCat2);

        // Creating articles

        $test = new Article();
        $test->setTitle('Unique to category 1');
        $test->setCategory($testCat1);
        $this->em->persist($test);

        $test2 = new Article();
        $test2->setTitle('Unique to category 2');
        $test2->setCategory($testCat2);
        $this->em->persist($test2);

        $test3 = new Article();
        $test3->setTitle('Unique to category 1');
        $test3->setCategory($testCat1);
        $this->em->persist($test3);

        $this->em->flush();

        static::assertSame('category1', $testCat1->getSlug());
        static::assertSame('category1-1', $testCat11->getSlug());
        static::assertSame('category2', $testCat2->getSlug());
        static::assertSame('unique-to-category-1', $test->getSlug());
        static::assertSame('unique-to-category-2', $test2->getSlug());
        static::assertSame('unique-to-category-1-1', $test3->getSlug());
    }

    /**
     * @group issue827
     */
    public function testShouldHandleForeignKeyMultipleColumnsUniqueBasedSlug(): void
    {
        // Creating parents

        $testPost1 = new Post();
        $testPost1->setTitle('Post 1');
        $this->em->persist($testPost1);
        $this->em->flush();

        static::assertSame('post-1', $testPost1->getSlug());

        $testPost2 = new Post();
        $testPost2->setTitle('Post 2');
        $this->em->persist($testPost2);
        $this->em->flush();

        static::assertSame('post-2', $testPost2->getSlug());

        // we have to refresh entities to ensure that Doctrine are aware of the sluggable generated identifiers
        $this->em->clear();

        $testPost1 = $this->em->find(
            self::POST,
            ['title' => $testPost1->getTitle(), 'slug' => $testPost1->getSlug()]
        );
        $testPost2 = $this->em->find(
            self::POST,
            ['title' => $testPost2->getTitle(), 'slug' => $testPost2->getSlug()]
        );

        // Creating comments

        $test = new Comment();
        $test->setTitle('Unique to post 1');
        $test->setPost($testPost1);
        $this->em->persist($test);
        $this->em->flush();

        static::assertSame('unique-to-post-1', $test->getSlug());

        $test2 = new Comment();
        $test2->setTitle('Unique to post 2');
        $test2->setPost($testPost2);
        $this->em->persist($test2);
        $this->em->flush();

        static::assertSame('unique-to-post-2', $test2->getSlug());

        $test3 = new Comment();
        $test3->setTitle('Unique to post 1');
        $test3->setPost($testPost1);
        $this->em->persist($test3);
        $this->em->flush();

        static::assertSame('unique-to-post-1-1', $test3->getSlug());

        $test4 = new Comment();
        $test4->setTitle('Unique to post 1');
        $test4->setPost($testPost1);
        $this->em->persist($test4);
        $this->em->flush();

        static::assertSame('unique-to-post-1-2', $test4->getSlug());

        $test5 = new Comment();
        $test5->setTitle('Unique to post 2');
        $test5->setPost($testPost2);
        $this->em->persist($test5);
        $this->em->flush();

        static::assertSame('unique-to-post-2-1', $test5->getSlug());
    }

    /**
     * @group issue827
     */
    public function testHandlePersistedForeignKeyMultipleColumnsUniqueBasedSlug(): void
    {
        // Creating parents

        $testPost1 = new Post();
        $testPost1->setTitle('Post 1');
        $this->em->persist($testPost1);

        $testPost2 = new Post();
        $testPost2->setTitle('Post 2');
        $this->em->persist($testPost2);

        // Creating comments

        $test = new Comment();
        $test->setTitle('Unique to post 1');
        $test->setPost($testPost1);
        $this->em->persist($test);

        $test2 = new Comment();
        $test2->setTitle('Unique to post 2');
        $test2->setPost($testPost2);
        $this->em->persist($test2);

        $test3 = new Comment();
        $test3->setTitle('Unique to post 1');
        $test3->setPost($testPost1);
        $this->em->persist($test3);

        $test4 = new Comment();
        $test4->setTitle('Unique to post 1');
        $test4->setPost($testPost1);
        $this->em->persist($test4);

        $test5 = new Comment();
        $test5->setTitle('Unique to post 2');
        $test5->setPost($testPost2);
        $this->em->persist($test5);

        $this->em->flush();

        static::assertSame('post-1', $testPost1->getSlug());
        static::assertSame('post-2', $testPost2->getSlug());
        static::assertSame('unique-to-post-1', $test->getSlug());
        static::assertSame('unique-to-post-2', $test2->getSlug());
        static::assertSame('unique-to-post-1-1', $test3->getSlug());
        static::assertSame('unique-to-post-1-2', $test4->getSlug());
        static::assertSame('unique-to-post-2-1', $test5->getSlug());
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::ARTICLE,
            self::CATEGORY,
            self::COMMENT,
            self::POST,
        ];
    }
}
