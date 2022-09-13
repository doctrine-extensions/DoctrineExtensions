<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Sortable;

use Doctrine\Common\EventManager;
use Gedmo\Sortable\SortableListener;
use Gedmo\Tests\Sortable\Fixture\Document\Category;
use Gedmo\Tests\Sortable\Fixture\Document\Kid;
use Gedmo\Tests\Sortable\Fixture\Document\Post;
use Gedmo\Tests\Tool\BaseTestCaseMongoODM;

/**
 * These are tests for sortable behavior with SortableGroup
 *
 * @author http://github.com/vetalt
 */
final class SortableDocumentGroupTest extends BaseTestCaseMongoODM
{
    public const POST = Post::class;
    public const CATEGORY = Category::class;
    public const KID = Kid::class;
    public const KID_DATE1 = '1999-12-31';
    public const KID_DATE2 = '2000-01-01';

    protected function setUp(): void
    {
        parent::setUp();
        $evm = new EventManager();
        $evm->addEventSubscriber(new SortableListener());

        $this->getMockDocumentManager($evm);
        $this->populate();
    }

    /**
     * There should be 2 kids by position
     */
    public function testKidInitialPositions(): void
    {
        $repo = $this->dm->getRepository(self::KID);

        for ($i = 0; $i < 2; ++$i) {
            $kids = $repo->findBy(['position' => $i]);
            static::assertCount(2, $kids);
        }
    }

    /**
     * Move the last kid in the first position
     */
    public function testKidMovePosition(): void
    {
        $repo = $this->dm->getRepository(self::KID);

        $kid = $repo->findOneBy(['lastname' => 'kid2']);
        static::assertInstanceOf(self::KID, $kid);

        $kid->setPosition(0);
        $this->dm->flush();

        $kids = $repo->findBy(['birthdate' => new \DateTime(self::KID_DATE1)]);
        static::assertCount(2, $kids);

        for ($i = 0; $i < 2; ++$i) {
            $expected = (1 == $i + 1) ? $i + 1 : 0;
            static::assertSame($expected, $kids[$i]->getPosition());
        }
    }

    /**
     * There should be 2 posts by position
     */
    public function testPostsInitialPositions(): void
    {
        $repo = $this->dm->getRepository(self::POST);

        for ($i = 0; $i < 3; ++$i) {
            $posts = $repo->findBy(['position' => $i]);
            static::assertCount(2, $posts);
        }
    }

    /**
     * Move the last inserted post in first position and check
     */
    public function testPostsMovePosition(): void
    {
        $repo_category = $this->dm->getRepository(self::CATEGORY);
        $repo_post = $this->dm->getRepository(self::POST);

        $category = $repo_category->findOneBy(['name' => 'category1']);
        static::assertInstanceOf(self::CATEGORY, $category);

        $post = $repo_post->findOneBy([
            'position' => 2,
            'category.id' => $category->getId(),
        ]);
        static::assertInstanceOf(self::POST, $post);

        $post->setPosition(0);

        $this->dm->flush();

        $posts = $repo_post->findBy([
            'category.id' => $category->getId(),
        ]);
        static::assertCount(3, $posts);

        for ($i = 0; $i < 3; ++$i) {
            $expected = ($i + 1 < 3) ? $i + 1 : 0;
            static::assertSame($expected, $posts[$i]->getPosition());
        }
    }

    /**
     * Delete the 2nd post linked to a Category and check
     */
    public function testPostsDeletePosition(): void
    {
        $repo_category = $this->dm->getRepository(self::CATEGORY);
        $repo_post = $this->dm->getRepository(self::POST);

        $category = $repo_category->findOneBy(['name' => 'category1']);
        static::assertInstanceOf(self::CATEGORY, $category);

        $post = $repo_post->findOneBy([
            'position' => 1,
            'category.id' => $category->getId(),
        ]);
        static::assertInstanceOf(self::POST, $post);

        $this->dm->remove($post);
        $this->dm->flush();

        $posts = $repo_post->findBy([
            'category.id' => $category->getId(),
        ]);
        static::assertCount(2, $posts);

        for ($i = 0; $i < 2; ++$i) {
            static::assertSame($i, $posts[$i]->getPosition());
        }
    }

    /**
     * Insert 2 categories, 6 posts and 4 kids
     * 3 posts are linked to a category, and 3 to the other one
     * 2 kids have one date, 2 another one
     */
    private function populate(): void
    {
        $categories = [];
        for ($i = 0; $i < 2; ++$i) {
            $categories[$i] = new Category();
            $categories[$i]->setName('category'.$i);
            $this->dm->persist($categories[$i]);
        }

        for ($i = 0; $i < 6; ++$i) {
            $post = new Post();
            $post->setTitle('post'.$i);
            $post->setCategory($categories[$i % 2]);
            $this->dm->persist($post);
        }

        $birthdates = [
            new \DateTime(self::KID_DATE1),
            new \DateTime(self::KID_DATE2),
            ];

        for ($i = 0; $i < 4; ++$i) {
            $kid = new Kid();
            $kid->setLastname('kid'.$i);
            $kid->setBirthdate($birthdates[$i % 2]);
            $this->dm->persist($kid);
        }
        $this->dm->flush();
        $this->dm->clear();
    }
}
