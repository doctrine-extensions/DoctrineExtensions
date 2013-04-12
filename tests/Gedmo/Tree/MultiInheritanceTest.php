<?php

namespace Gedmo\Tree;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Doctrine\Common\Util\Debug;

/**
 * These are tests for Tree behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class MultiInheritanceTest extends BaseTestCaseORM
{
    const NODE = "Tree\\Fixture\\Node";
    const BASE_NODE = "Tree\\Fixture\\BaseNode";
    const ANODE = "Tree\\Fixture\\ANode";
    const TRANSLATION = "Gedmo\\Translatable\\Entity\\Translation";

    protected function setUp()
    {
        parent::setUp();

        $this->getMockSqliteEntityManager();
        $this->populate();
    }

    public function testInheritance()
    {
        $meta = $this->em->getClassMetadata(self::NODE);
        $repo = $this->em->getRepository(self::NODE);

        $food = $repo->findOneByIdentifier('food');
        $left = $meta->getReflectionProperty('lft')->getValue($food);
        $right = $meta->getReflectionProperty('rgt')->getValue($food);

        $this->assertEquals(1, $left);
        $this->assertNotNull($food->getCreated());
        $this->assertNotNull($food->getUpdated());

        $translationRepo = $this->em->getRepository(self::TRANSLATION);
        $translations = $translationRepo->findTranslations($food);

        $this->assertCount(0, $translations);
        $this->assertEquals('food', $food->getSlug());
    }

    /**
     * Test case for github issue#7
     * Child count is invalid resulting in SINGLE_TABLE and JOINED
     * inheritance mapping. Also getChildren, getPath results are invalid
     */
    public function testCaseGithubIssue7()
    {
        $repo = $this->em->getRepository(self::NODE);
        $vegies = $repo->findOneByTitle('Vegitables');

        $count = $repo->childCount($vegies, true/*direct*/);
        $this->assertEquals(3, $count);

        $children = $repo->children($vegies, true);
        $this->assertCount(3, $children);

        // node repository will not find it
        $baseNodeRepo = $this->em->getRepository(self::BASE_NODE);
        $cabbage = $baseNodeRepo->findOneByIdentifier('cabbage');
        $path = $baseNodeRepo->getPath($cabbage);
        $this->assertCount(3, $path);
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::NODE,
            self::ANODE,
            self::TRANSLATION,
            self::BASE_NODE
        );
    }

    private function populate()
    {
        $root = new \Tree\Fixture\Node();
        $root->setTitle("Food");
        $root->setIdentifier('food');

        $root2 = new \Tree\Fixture\Node();
        $root2->setTitle("Sports");
        $root2->setIdentifier('sport');

        $child = new \Tree\Fixture\Node();
        $child->setTitle("Fruits");
        $child->setParent($root);
        $child->setIdentifier('fruit');

        $child2 = new \Tree\Fixture\Node();
        $child2->setTitle("Vegitables");
        $child2->setParent($root);
        $child2->setIdentifier('vegie');

        $childsChild = new \Tree\Fixture\Node();
        $childsChild->setTitle("Carrots");
        $childsChild->setParent($child2);
        $childsChild->setIdentifier('carrot');

        $potatoes = new \Tree\Fixture\Node();
        $potatoes->setTitle("Potatoes");
        $potatoes->setParent($child2);
        $potatoes->setIdentifier('potatoe');

        $cabbages = new \Tree\Fixture\BaseNode();
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
