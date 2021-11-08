<?php

namespace Gedmo\Tests\Tree;

use Doctrine\Common\EventManager;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Tests\Tree\Fixture\Article;
use Gedmo\Tests\Tree\Fixture\Category;
use Gedmo\Tests\Tree\Fixture\Comment;
use Gedmo\Tree\TreeListener;

/**
 * These are tests for Tree behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class ConcurrencyTest extends BaseTestCaseORM
{
    public const CATEGORY = Category::class;
    public const ARTICLE = Article::class;
    public const COMMENT = Comment::class;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new TreeListener());

        $this->getMockSqliteEntityManager($evm);
        $this->populate();
    }

    public function testConcurrentEntitiesInOneFlush()
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $sport = $repo->findOneBy(['title' => 'Root2']);
        $sport->setTitle('Sport');

        $skiing = new Category();
        $skiing->setTitle('Skiing');
        $skiing->setParent($sport);

        $articleAboutSkiing = new Article();
        $articleAboutSkiing->setCategory($skiing);
        $articleAboutSkiing->setTitle('About Skiing');

        $aboutSkiingArticleComment = new Comment();
        $aboutSkiingArticleComment->setArticle($articleAboutSkiing);
        $aboutSkiingArticleComment->setMessage('hello');

        $carRacing = new Category();
        $carRacing->setParent($sport);
        $carRacing->setTitle('Car Racing');

        $articleCarRacing = new Article();
        $articleCarRacing->setCategory($carRacing);
        $articleCarRacing->setTitle('Car racing madness');

        $olympicSkiing = new Category();
        $olympicSkiing->setParent($skiing);
        $olympicSkiing->setTitle('Olympic Skiing Championship 2011');

        $this->em->persist($sport);
        $this->em->persist($skiing);
        $this->em->persist($articleAboutSkiing);
        $this->em->persist($aboutSkiingArticleComment);
        $this->em->persist($carRacing);
        $this->em->persist($articleCarRacing);
        $this->em->persist($olympicSkiing);
        $this->em->flush();
        $this->em->clear();

        $meta = $this->em->getClassMetadata(self::CATEGORY);
        $sport = $repo->findOneBy(['title' => 'Sport']);
        $left = $meta->getReflectionProperty('lft')->getValue($sport);
        $right = $meta->getReflectionProperty('rgt')->getValue($sport);

        static::assertEquals(9, $left);
        static::assertEquals(16, $right);

        $skiing = $repo->findOneBy(['title' => 'Skiing']);
        $left = $meta->getReflectionProperty('lft')->getValue($skiing);
        $right = $meta->getReflectionProperty('rgt')->getValue($skiing);

        static::assertEquals(10, $left);
        static::assertEquals(13, $right);
    }

    public function testConcurrentTree()
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $meta = $this->em->getClassMetadata(self::CATEGORY);

        $root = $repo->findOneBy(['title' => 'Root']);

        static::assertEquals(1, $root->getLeft());
        static::assertEquals(8, $root->getRight());

        $root2 = $repo->findOneBy(['title' => 'Root2']);

        static::assertEquals(9, $root2->getLeft());
        static::assertEquals(10, $root2->getRight());

        $child2Child = $repo->findOneBy(['title' => 'childs2_child']);

        static::assertEquals(5, $child2Child->getLeft());
        static::assertEquals(6, $child2Child->getRight());

        $child2Parent = $child2Child->getParent();

        static::assertEquals(4, $child2Parent->getLeft());
        static::assertEquals(7, $child2Parent->getRight());
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::CATEGORY,
            self::ARTICLE,
            self::COMMENT,
        ];
    }

    private function populate()
    {
        $root = new Category();
        $root->setTitle('Root');

        $root2 = new Category();
        $root2->setTitle('Root2');

        $child = new Category();
        $child->setTitle('child');
        $child->setParent($root);

        $child2 = new Category();
        $child2->setTitle('child2');
        $child2->setParent($root);

        $childsChild = new Category();
        $childsChild->setTitle('childs2_child');
        $childsChild->setParent($child2);

        $this->em->persist($root);
        $this->em->persist($root2);
        $this->em->persist($child);
        $this->em->persist($child2);
        $this->em->persist($childsChild);
        $this->em->flush();
        $this->em->clear();
    }
}
