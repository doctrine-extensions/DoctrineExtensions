<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Tree;

use Doctrine\Common\EventManager;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Tests\Tree\Fixture\Category;
use Gedmo\Tests\Tree\Fixture\CategoryUuid;
use Gedmo\Tree\Node;
use Gedmo\Tree\TreeListener;

/**
 * These are tests for Tree behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class TreeTest extends BaseTestCaseORM
{
    public const CATEGORY = Category::class;
    public const CATEGORY_UUID = CategoryUuid::class;

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new TreeListener());

        $this->getDefaultMockSqliteEntityManager($evm);
    }

    public function testTheTree(): void
    {
        $meta = $this->em->getClassMetadata(self::CATEGORY);

        $root = new Category();
        $root->setTitle('Root');
        static::assertInstanceOf(Node::class, $root);

        $this->em->persist($root);
        $this->em->flush();
        $this->em->clear();

        $root = $this->em->getRepository(self::CATEGORY)->find(1);
        $left = $meta->getReflectionProperty('lft')->getValue($root);
        $right = $meta->getReflectionProperty('rgt')->getValue($root);

        static::assertSame(1, $left);
        static::assertSame(2, $right);

        $child = new Category();
        $child->setTitle('child');
        $child->setParent($root);

        $this->em->persist($child);
        $this->em->flush();
        $this->em->clear();

        $root = $this->em->getRepository(self::CATEGORY)->find(1);
        $left = $meta->getReflectionProperty('lft')->getValue($root);
        $right = $meta->getReflectionProperty('rgt')->getValue($root);
        $level = $meta->getReflectionProperty('level')->getValue($root);

        static::assertSame(1, $left);
        static::assertSame(4, $right);
        static::assertSame(0, $level);

        $child = $this->em->getRepository(self::CATEGORY)->find(2);
        $left = $meta->getReflectionProperty('lft')->getValue($child);
        $right = $meta->getReflectionProperty('rgt')->getValue($child);
        $level = $meta->getReflectionProperty('level')->getValue($child);

        static::assertSame(2, $left);
        static::assertSame(3, $right);
        static::assertSame(1, $level);

        $child2 = new Category();
        $child2->setTitle('child2');
        $child2->setParent($root);

        $this->em->persist($child2);
        $this->em->flush();
        $this->em->clear();

        $root = $this->em->getRepository(self::CATEGORY)->find(1);
        $left = $meta->getReflectionProperty('lft')->getValue($root);
        $right = $meta->getReflectionProperty('rgt')->getValue($root);
        $level = $meta->getReflectionProperty('level')->getValue($root);

        static::assertSame(1, $left);
        static::assertSame(6, $right);
        static::assertSame(0, $level);

        $child2 = $this->em->getRepository(self::CATEGORY)->find(3);
        $left = $meta->getReflectionProperty('lft')->getValue($child2);
        $right = $meta->getReflectionProperty('rgt')->getValue($child2);
        $level = $meta->getReflectionProperty('level')->getValue($child2);

        static::assertSame(4, $left);
        static::assertSame(5, $right);
        static::assertSame(1, $level);

        $childsChild = new Category();
        $childsChild->setTitle('childs2_child');
        $childsChild->setParent($child2);

        $this->em->persist($childsChild);
        $this->em->flush();
        $this->em->clear();

        $child2 = $this->em->getRepository(self::CATEGORY)->find(3);
        $left = $meta->getReflectionProperty('lft')->getValue($child2);
        $right = $meta->getReflectionProperty('rgt')->getValue($child2);
        $level = $meta->getReflectionProperty('level')->getValue($child2);

        static::assertSame(4, $left);
        static::assertSame(7, $right);
        static::assertSame(1, $level);

        $level = $meta->getReflectionProperty('level')->getValue($childsChild);

        static::assertSame(2, $level);

        // test updates to nodes, parent changes

        $childsChild = $this->em->getRepository(self::CATEGORY)->find(4);
        $child = $this->em->getRepository(self::CATEGORY)->find(2);
        $childsChild->setTitle('childs_child');
        $childsChild->setParent($child);

        $this->em->persist($childsChild);
        $this->em->flush();
        $this->em->clear();

        $child = $this->em->getRepository(self::CATEGORY)->find(2);
        $left = $meta->getReflectionProperty('lft')->getValue($child);
        $right = $meta->getReflectionProperty('rgt')->getValue($child);
        $level = $meta->getReflectionProperty('level')->getValue($child);

        static::assertSame(2, $left);
        static::assertSame(5, $right);
        static::assertSame(1, $level);

        // test deletion

        $this->em->remove($child);
        $this->em->flush();
        $this->em->clear();

        $root = $this->em->getRepository(self::CATEGORY)->find(1);
        $left = $meta->getReflectionProperty('lft')->getValue($root);
        $right = $meta->getReflectionProperty('rgt')->getValue($root);

        static::assertSame(1, $left);
        static::assertSame(4, $right);

        // test persisting in any time
        $yetAnotherChild = new Category();
        $this->em->persist($yetAnotherChild);
        $yetAnotherChild->setTitle('yetanotherchild');
        $yetAnotherChild->setParent($root);
        // $this->em->persist($yetAnotherChild);
        $this->em->flush();
        $this->em->clear();

        $left = $meta->getReflectionProperty('lft')->getValue($yetAnotherChild);
        $right = $meta->getReflectionProperty('rgt')->getValue($yetAnotherChild);
        $level = $meta->getReflectionProperty('level')->getValue($yetAnotherChild);

        static::assertSame(4, $left);
        static::assertSame(5, $right);
        static::assertSame(1, $level);
    }

    public function testIssue33(): void
    {
        $repo = $this->em->getRepository(self::CATEGORY);

        $root = new Category();
        $root->setTitle('root');

        $node1 = new Category();
        $node1->setTitle('node1');
        $node1->setParent($root);

        $node2 = new Category();
        $node2->setTitle('node2');
        $node2->setParent($root);

        $subNode = new Category();
        $subNode->setTitle('sub-node');
        $subNode->setParent($node2);

        $this->em->persist($root);
        $this->em->persist($node1);
        $this->em->persist($node2);
        $this->em->persist($subNode);
        $this->em->flush();
        $this->em->clear();

        $subNode = $repo->findOneBy(['title' => 'sub-node']);
        $node1 = $repo->findOneBy(['title' => 'node1']);
        $subNode->setParent($node1);

        $this->em->persist($subNode);
        $this->em->flush();
        $this->em->clear();

        $meta = $this->em->getClassMetadata(self::CATEGORY);
        $subNode = $repo->findOneBy(['title' => 'sub-node']);
        $left = $meta->getReflectionProperty('lft')->getValue($subNode);
        $right = $meta->getReflectionProperty('rgt')->getValue($subNode);
        static::assertSame(3, $left);
        static::assertSame(4, $right);

        $node1 = $repo->findOneBy(['title' => 'node1']);
        $left = $meta->getReflectionProperty('lft')->getValue($node1);
        $right = $meta->getReflectionProperty('rgt')->getValue($node1);
        static::assertSame(2, $left);
        static::assertSame(5, $right);
    }

    public function testIssue273(): void
    {
        $meta = $this->em->getClassMetadata(self::CATEGORY_UUID);

        $root = new CategoryUuid();
        $root->setTitle('Root');
        static::assertInstanceOf(Node::class, $root);

        $this->em->persist($root);
        $rootId = $root->getId();
        $this->em->flush();
        $this->em->clear();

        $root = $this->em->getRepository(self::CATEGORY_UUID)->find($rootId);
        $left = $meta->getReflectionProperty('lft')->getValue($root);
        $right = $meta->getReflectionProperty('rgt')->getValue($root);

        static::assertSame(1, $left);
        static::assertSame(2, $right);

        $child = new CategoryUuid();
        $child->setTitle('child');
        $child->setParent($root);

        $this->em->persist($child);
        $childId = $child->getId();
        $this->em->flush();
        $this->em->clear();

        $root = $this->em->getRepository(self::CATEGORY_UUID)->find($rootId);
        $left = $meta->getReflectionProperty('lft')->getValue($root);
        $right = $meta->getReflectionProperty('rgt')->getValue($root);
        $level = $meta->getReflectionProperty('level')->getValue($root);

        static::assertSame(1, $left);
        static::assertSame(4, $right);
        static::assertSame(0, $level);

        $child = $this->em->getRepository(self::CATEGORY_UUID)->find($childId);
        $left = $meta->getReflectionProperty('lft')->getValue($child);
        $right = $meta->getReflectionProperty('rgt')->getValue($child);
        $level = $meta->getReflectionProperty('level')->getValue($child);

        static::assertSame(2, $left);
        static::assertSame(3, $right);
        static::assertSame(1, $level);

        $child2 = new CategoryUuid();
        $child2->setTitle('child2');
        $child2->setParent($root);

        $this->em->persist($child2);
        $child2Id = $child2->getId();
        $this->em->flush();
        $this->em->clear();

        $root = $this->em->getRepository(self::CATEGORY_UUID)->find($rootId);
        $left = $meta->getReflectionProperty('lft')->getValue($root);
        $right = $meta->getReflectionProperty('rgt')->getValue($root);
        $level = $meta->getReflectionProperty('level')->getValue($root);

        static::assertSame(1, $left);
        static::assertSame(6, $right);
        static::assertSame(0, $level);

        $child2 = $this->em->getRepository(self::CATEGORY_UUID)->find($child2Id);
        $left = $meta->getReflectionProperty('lft')->getValue($child2);
        $right = $meta->getReflectionProperty('rgt')->getValue($child2);
        $level = $meta->getReflectionProperty('level')->getValue($child2);

        static::assertSame(4, $left);
        static::assertSame(5, $right);
        static::assertSame(1, $level);

        $childsChild = new CategoryUuid();
        $childsChild->setTitle('childs2_child');
        $childsChild->setParent($child2);

        $this->em->persist($childsChild);
        $childsChildId = $childsChild->getId();
        $this->em->flush();
        $this->em->clear();

        $child2 = $this->em->getRepository(self::CATEGORY_UUID)->find($child2Id);
        $left = $meta->getReflectionProperty('lft')->getValue($child2);
        $right = $meta->getReflectionProperty('rgt')->getValue($child2);
        $level = $meta->getReflectionProperty('level')->getValue($child2);

        static::assertSame(4, $left);
        static::assertSame(7, $right);
        static::assertSame(1, $level);

        $level = $meta->getReflectionProperty('level')->getValue($childsChild);

        static::assertSame(2, $level);

        // test updates to nodes, parent changes

        $childsChild = $this->em->getRepository(self::CATEGORY_UUID)->find($childsChildId);
        $child = $this->em->getRepository(self::CATEGORY_UUID)->find($childId);
        $childsChild->setTitle('childs_child');
        $childsChild->setParent($child);

        $this->em->persist($childsChild);
        $this->em->flush();
        $this->em->clear();

        $child = $this->em->getRepository(self::CATEGORY_UUID)->find($childId);
        $left = $meta->getReflectionProperty('lft')->getValue($child);
        $right = $meta->getReflectionProperty('rgt')->getValue($child);
        $level = $meta->getReflectionProperty('level')->getValue($child);

        static::assertSame(2, $left);
        static::assertSame(5, $right);
        static::assertSame(1, $level);

        // test deletion

        $this->em->remove($child);
        $this->em->flush();
        $this->em->clear();

        $root = $this->em->getRepository(self::CATEGORY_UUID)->find($rootId);
        $left = $meta->getReflectionProperty('lft')->getValue($root);
        $right = $meta->getReflectionProperty('rgt')->getValue($root);

        static::assertSame(1, $left);
        static::assertSame(4, $right);

        // test persisting in any time
        $yetAnotherChild = new CategoryUuid();
        $this->em->persist($yetAnotherChild);
        $yetAnotherChild->setTitle('yetanotherchild');
        $yetAnotherChild->setParent($root);
        // $this->em->persist($yetAnotherChild);
        $this->em->flush();
        $this->em->clear();

        $left = $meta->getReflectionProperty('lft')->getValue($yetAnotherChild);
        $right = $meta->getReflectionProperty('rgt')->getValue($yetAnotherChild);
        $level = $meta->getReflectionProperty('level')->getValue($yetAnotherChild);

        static::assertSame(4, $left);
        static::assertSame(5, $right);
        static::assertSame(1, $level);
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::CATEGORY,
            self::CATEGORY_UUID,
        ];
    }
}
