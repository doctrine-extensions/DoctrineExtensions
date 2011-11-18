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
 * @package Gedmo.Tree
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

        $this->assertEquals($left, 9);
        $this->assertEquals($right, 16);

        $skiing = $repo->findOneByTitle('Skiing');
        $left = $meta->getReflectionProperty('lft')->getValue($skiing);
        $right = $meta->getReflectionProperty('rgt')->getValue($skiing);

        $this->assertEquals($left, 10);
        $this->assertEquals($right, 13);
    }

    public function testConcurrentTree()
    {
        $repo = $this->em->getRepository(self::CATEGORY);
        $meta = $this->em->getClassMetadata(self::CATEGORY);

        $root = $repo->findOneByTitle('Root');

        $this->assertEquals($root->getLeft(), 1);
        $this->assertEquals($root->getRight(), 8);

        $root2 = $repo->findOneByTitle('Root2');

        $this->assertEquals($root2->getLeft(), 9);
        $this->assertEquals($root2->getRight(), 10);

        $child2Child = $repo->findOneByTitle('childs2_child');

        $this->assertEquals($child2Child->getLeft(), 5);
        $this->assertEquals($child2Child->getRight(), 6);

        $child2Parent = $child2Child->getParent();

        $this->assertEquals($child2Parent->getLeft(), 4);
        $this->assertEquals($child2Parent->getRight(), 7);
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
