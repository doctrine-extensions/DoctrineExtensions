<?php

namespace Gedmo\Tree;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Doctrine\Common\Util\Debug,
    Tree\Fixture\Category,
    Tree\Fixture\Article,
    Tree\Fixture\Comment;

/**
 * These are tests for Tree behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class ConcurrencyTest extends BaseTestCaseORM
{
    const CATEGORY = "Tree\\Fixture\\Category";
    const ARTICLE = "Tree\\Fixture\\Article";
    const COMMENT = "Tree\\Fixture\\Comment";

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $evm->addEventSubscriber(new TreeListener);

        $this->getMockSqliteEntityManager($evm);
        $this->populate();
    }

    public function testConcurrentEntitiesInOneFlush()
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $sport = $repo->findOneByTitle('Root2');
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
        $sport = $repo->findOneByTitle('Sport');
        $left = $meta->getReflectionProperty('lft')->getValue($sport);
        $right = $meta->getReflectionProperty('rgt')->getValue($sport);

        $this->assertEquals(9, $left);
        $this->assertEquals(16, $right);

        $skiing = $repo->findOneByTitle('Skiing');
        $left = $meta->getReflectionProperty('lft')->getValue($skiing);
        $right = $meta->getReflectionProperty('rgt')->getValue($skiing);

        $this->assertEquals(10, $left);
        $this->assertEquals(13, $right);
    }

    public function testConcurrentTree()
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $meta = $this->em->getClassMetadata(self::CATEGORY);

        $root = $repo->findOneByTitle('Root');

        $this->assertEquals(1, $root->getLeft());
        $this->assertEquals(8, $root->getRight());

        $root2 = $repo->findOneByTitle('Root2');

        $this->assertEquals(9, $root2->getLeft());
        $this->assertEquals(10, $root2->getRight());

        $child2Child = $repo->findOneByTitle('childs2_child');

        $this->assertEquals(5, $child2Child->getLeft());
        $this->assertEquals(6, $child2Child->getRight());

        $child2Parent = $child2Child->getParent();

        $this->assertEquals(4, $child2Parent->getLeft());
        $this->assertEquals(7, $child2Parent->getRight());
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::CATEGORY,
            self::ARTICLE,
            self::COMMENT
        );
    }

    private function populate()
    {
        $root = new Category();
        $root->setTitle("Root");

        $root2 = new Category();
        $root2->setTitle("Root2");

        $child = new Category();
        $child->setTitle("child");
        $child->setParent($root);

        $child2 = new Category();
        $child2->setTitle("child2");
        $child2->setParent($root);

        $childsChild = new Category();
        $childsChild->setTitle("childs2_child");
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
