<?php

namespace Gedmo\Tree\NestedSet;

use Doctrine\Common\EventManager;
use Gedmo\TestTool\ObjectManagerTestCase;
use Gedmo\Tree\TreeListener;
use Gedmo\Fixture\Tree\NestedSet\Node\Node;
use Gedmo\Fixture\Tree\NestedSet\Node\ANode;
use Gedmo\Fixture\Tree\NestedSet\Node\BaseNode;
use Gedmo\Timestampable\TimestampableListener;
use Gedmo\Sluggable\SluggableListener;

class MultiInheritanceTest extends ObjectManagerTestCase
{
    const NODE = "Gedmo\Fixture\Tree\NestedSet\Node\Node";
    const BASE_NODE = "Gedmo\Fixture\Tree\NestedSet\Node\BaseNode";
    const ANODE = "Gedmo\Fixture\Tree\NestedSet\Node\ANode";

    private $em;

    protected function setUp()
    {
        $evm = new EventManager;
        $evm->addEventSubscriber(new TreeListener);
        $evm->addEventSubscriber(new TimestampableListener);
        $evm->addEventSubscriber(new SluggableListener);

        $this->em = $this->createEntityManager($evm);
        $this->createSchema($this->em, array(
            self::NODE,
            self::ANODE,
            self::BASE_NODE
        ));
        $this->populate();
    }

    protected function tearDown()
    {
        $this->releaseEntityManager($this->em);
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

    private function populate()
    {
        $root = new Node;
        $root->setTitle("Food");
        $root->setIdentifier('food');

        $root2 = new Node;
        $root2->setTitle("Sports");
        $root2->setIdentifier('sport');

        $child = new Node;
        $child->setTitle("Fruits");
        $child->setParent($root);
        $child->setIdentifier('fruit');

        $child2 = new Node;
        $child2->setTitle("Vegitables");
        $child2->setParent($root);
        $child2->setIdentifier('vegie');

        $childsChild = new Node;
        $childsChild->setTitle("Carrots");
        $childsChild->setParent($child2);
        $childsChild->setIdentifier('carrot');

        $potatoes = new Node;
        $potatoes->setTitle("Potatoes");
        $potatoes->setParent($child2);
        $potatoes->setIdentifier('potatoe');

        $cabbages = new BaseNode;
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
    }
}
