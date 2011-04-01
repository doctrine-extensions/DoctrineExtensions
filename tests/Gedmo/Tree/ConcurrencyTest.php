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
        $sport = $this->em->getRepository(self::CATEGORY)->find(2);
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
        $sport = $this->em->getRepository(self::CATEGORY)->find(2);
        $left = $meta->getReflectionProperty('lft')->getValue($sport);
        $right = $meta->getReflectionProperty('rgt')->getValue($sport);

        $this->assertEquals($left, 9);
        $this->assertEquals($right, 16);

        $skiing = $this->em->getRepository(self::CATEGORY)->find(6);
        $left = $meta->getReflectionProperty('lft')->getValue($skiing);
        $right = $meta->getReflectionProperty('rgt')->getValue($skiing);

        $this->assertEquals($left, 10);
        $this->assertEquals($right, 13);
    }

    public function testConcurrentTree()
    {
        $meta = $this->em->getClassMetadata(self::CATEGORY);

        $root = $this->em->getRepository(self::CATEGORY)->find(1);
        $left = $meta->getReflectionProperty('lft')->getValue($root);
        $right = $meta->getReflectionProperty('rgt')->getValue($root);

        $this->assertEquals($left, 1);
        $this->assertEquals($right, 8);

        $root2 = $this->em->getRepository(self::CATEGORY)->find(2);
        $left = $meta->getReflectionProperty('lft')->getValue($root2);
        $right = $meta->getReflectionProperty('rgt')->getValue($root2);

        $this->assertEquals($left, 9);
        $this->assertEquals($right, 10);

        $child2Child = $this->em->getRepository(self::CATEGORY)->find(5);
        $left = $meta->getReflectionProperty('lft')->getValue($child2Child);
        $right = $meta->getReflectionProperty('rgt')->getValue($child2Child);

        $this->assertEquals($left, 5);
        $this->assertEquals($right, 6);

        $child2Parent = $child2Child->getParent();
        $child2Parent->getId(); // initialize if proxy
        $left = $meta->getReflectionProperty('lft')->getValue($child2Parent);
        $right = $meta->getReflectionProperty('rgt')->getValue($child2Parent);

        $this->assertEquals($left, 4);
        $this->assertEquals($right, 7);
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
