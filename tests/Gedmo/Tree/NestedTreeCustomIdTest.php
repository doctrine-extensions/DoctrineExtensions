<?php

namespace Gedmo\Tree;

use Doctrine\Common\EventManager;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use Tool\BaseTestCaseORM;
use Tree\Fixture\CustomIdCategory;

/**
 * Test Tree behaviour with a custom Doctrine type for the ID property.
 *
 * @author Paul Dugas <paul@dugasent.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class NestedTreeCustomIdTest extends BaseTestCaseORM
{
    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new TreeListener());

        $this->getMockSqliteEntityManager($evm);

        $this->em->getConnection()
             ->getDatabasePlatform()
             ->registerDoctrineTypeMapping('uuid_binary', 'binary');
    }

    public function testPersist() 
    {
        /** @var NestedTreeRepository */
        $repo = $this->em->getRepository(CustomIdCategory::class);

        // Create a root node, "root1"
        $root1 = new CustomIdCategory();
        $root1->setTitle('root1');
        $this->em->persist($root1);
        $this->em->flush();
        $this->em->clear();

        // Should be able to read it back by title 
        $node = $repo->findOneByTitle($root1->getTitle());
        $this->assertNotNull($node);
        $this->assertInstanceOf(CustomIdCategory::class, $node);
        $this->assertSame($root1->getTitle(), $node->getTitle());
        $this->assertEquals($root1->getId(), $node->getId());

        // Should be able to read it back by ID 
        $this->em->clear();
        $node = $repo->findOneById($root1->getId());
        $this->assertNotNull($node);
        $this->assertInstanceOf(CustomIdCategory::class, $node);
        $this->assertSame('root1', $node->getTitle());
        $this->assertEquals($root1->getId(), $node->getId());

        // The tree properties should be correct; $parent is null, $root is
        // itself, $level is 0, $left is 1 and $right is 2.
        $this->assertNull($node->getParent());
        $this->assertNotNull($node->getRoot());
        $this->assertEquals($node->getId(), $node->getRoot()->getId());
        $this->assertEquals(0, $node->getLevel());
        $this->assertEquals(1, $node->getLeft());
        $this->assertEquals(2, $node->getRight());
    }

    protected function getUsedEntityFixtures()
    {
        return array(CustomIdCategory::class);
    }
}
