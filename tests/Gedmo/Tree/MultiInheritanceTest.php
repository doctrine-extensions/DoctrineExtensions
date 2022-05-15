<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Tree;

use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Tests\Tree\Fixture\ANode;
use Gedmo\Tests\Tree\Fixture\BaseNode;
use Gedmo\Tests\Tree\Fixture\Node;
use Gedmo\Translatable\Entity\Translation;

/**
 * These are tests for Tree behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class MultiInheritanceTest extends BaseTestCaseORM
{
    public const NODE = Node::class;
    public const BASE_NODE = BaseNode::class;
    public const ANODE = ANode::class;
    public const TRANSLATION = Translation::class;

    protected function setUp(): void
    {
        parent::setUp();

        $this->getDefaultMockSqliteEntityManager();
        $this->populate();
    }

    public function testInheritance(): void
    {
        $meta = $this->em->getClassMetadata(self::NODE);
        $repo = $this->em->getRepository(self::NODE);

        $food = $repo->findOneBy(['identifier' => 'food']);
        $left = $meta->getReflectionProperty('lft')->getValue($food);
        $right = $meta->getReflectionProperty('rgt')->getValue($food);

        static::assertSame(1, $left);
        static::assertNotNull($food->getCreated());
        static::assertNotNull($food->getUpdated());

        $translationRepo = $this->em->getRepository(self::TRANSLATION);
        $translations = $translationRepo->findTranslations($food);

        static::assertCount(0, $translations);
        static::assertSame('food', $food->getSlug());
    }

    /**
     * Test case for github issue#7
     * Child count is invalid resulting in SINGLE_TABLE and JOINED
     * inheritance mapping. Also getChildren, getPath results are invalid
     */
    public function testCaseGithubIssue7(): void
    {
        $repo = $this->em->getRepository(self::NODE);
        $vegies = $repo->findOneBy(['title' => 'Vegitables']);

        $count = $repo->childCount($vegies, true/* direct */);
        static::assertSame(3, $count);

        $children = $repo->children($vegies, true);
        static::assertCount(3, $children);

        // node repository will not find it
        $baseNodeRepo = $this->em->getRepository(self::BASE_NODE);
        $cabbage = $baseNodeRepo->findOneBy(['identifier' => 'cabbage']);
        $path = $baseNodeRepo->getPath($cabbage);
        static::assertCount(3, $path);
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            self::NODE,
            self::ANODE,
            self::TRANSLATION,
            self::BASE_NODE,
        ];
    }

    private function populate(): void
    {
        $root = new \Gedmo\Tests\Tree\Fixture\Node();
        $root->setTitle('Food');
        $root->setIdentifier('food');

        $root2 = new \Gedmo\Tests\Tree\Fixture\Node();
        $root2->setTitle('Sports');
        $root2->setIdentifier('sport');

        $child = new \Gedmo\Tests\Tree\Fixture\Node();
        $child->setTitle('Fruits');
        $child->setParent($root);
        $child->setIdentifier('fruit');

        $child2 = new \Gedmo\Tests\Tree\Fixture\Node();
        $child2->setTitle('Vegitables');
        $child2->setParent($root);
        $child2->setIdentifier('vegie');

        $childsChild = new \Gedmo\Tests\Tree\Fixture\Node();
        $childsChild->setTitle('Carrots');
        $childsChild->setParent($child2);
        $childsChild->setIdentifier('carrot');

        $potatoes = new \Gedmo\Tests\Tree\Fixture\Node();
        $potatoes->setTitle('Potatoes');
        $potatoes->setParent($child2);
        $potatoes->setIdentifier('potatoe');

        $cabbages = new \Gedmo\Tests\Tree\Fixture\BaseNode();
        $cabbages->setIdentifier('cabbage');
        $cabbages->setParent($child2);

        $this->em->persist($root);
        $this->em->persist($root2);
        $this->em->persist($child);
        $this->em->persist($child2);
        $this->em->persist($childsChild);
        $this->em->persist($potatoes);
        $this->em->persist($cabbages);
        $this->em->flush();
        $this->em->clear();
    }
}
