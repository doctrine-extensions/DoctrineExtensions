<?php

namespace Gedmo\Tests\Tree;

use Doctrine\Common\EventManager;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Tests\Tree\Fixture\Genealogy\Man;
use Gedmo\Tests\Tree\Fixture\Genealogy\Woman;
use Gedmo\Tree\TreeListener;

/**
 * Additional tests for tree inheritance and in-memory updates
 *
 * @author Illya Klymov <xanf@xanf.me>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class InMemoryUpdatesWithInheritanceTest extends BaseTestCaseORM
{
    public const PERSON = 'Gedmo\\Tests\\Tree\\Fixture\\Genealogy\\Person';
    public const MAN = 'Gedmo\\Tests\\Tree\\Fixture\\Genealogy\\Man';
    public const WOMAN = 'Gedmo\\Tests\\Tree\\Fixture\\Genealogy\\Woman';

    protected function setUp(): void
    {
        parent::setUp();

        $evm = new EventManager();
        $evm->addEventSubscriber(new TreeListener());

        $this->getMockSqliteEntityManager($evm);
    }

    public function testInMemoryTreeInsertsWithInheritance()
    {
        $nodes = [];

        $man1 = new Man('Root - Man1');
        $this->em->persist($man1);

        $woman1 = new Woman('Level 1 - Woman1');
        $this->em->persist($woman1);
        $woman1->setParent($man1);

        $man2 = new Man('Level 2 - Man2');
        $this->em->persist($man2);
        $man2->setParent($woman1);

        $woman2 = new Woman('Level 3 - Woman2');
        $this->em->persist($woman2);
        $woman2->setParent($man2);

        $this->em->flush();

        $left = $man1->getLeft();
        $right = $man1->getRight();
        $level = $man1->getLevel();
        static::assertEquals(1, $left);
        static::assertEquals(8, $right);
        static::assertEquals(0, $level);

        $left = $woman1->getLeft();
        $right = $woman1->getRight();
        $level = $woman1->getLevel();
        static::assertEquals(2, $left);
        static::assertEquals(7, $right);
        static::assertEquals(1, $level);

        $left = $man2->getLeft();
        $right = $man2->getRight();
        $level = $man2->getLevel();
        static::assertEquals(3, $left);
        static::assertEquals(6, $right);
        static::assertEquals(2, $level);

        $left = $woman2->getLeft();
        $right = $woman2->getRight();
        $level = $woman2->getLevel();
        static::assertEquals(4, $left);
        static::assertEquals(5, $right);
        static::assertEquals(3, $level);
    }

    protected function getUsedEntityFixtures()
    {
        return [
            self::PERSON,
            self::MAN,
            self::WOMAN,
        ];
    }
}
