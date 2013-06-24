<?php

namespace Gedmo\Tree;

use Tool\BaseTestCaseORM;
use Tree\Fixture\Category;
use Gedmo\SoftDeleteable\SoftDeleteableListener;
use Gedmo\Tree\TreeListener;
use Doctrine\Common\EventManager;

/**
 * These are tests for Tree behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class IgnoredFilterTest extends BaseTestCaseORM
{
    const CATEGORY = "Tree\\Fixture\\Category";

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $evm->addEventSubscriber(new TreeListener);
        $evm->addEventSubscriber(new SoftDeleteableListener);

        $this->getMockSqliteEntityManager($evm);
        $this->em->getConfiguration()->addFilter('soft-deleteable', 'Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter');
        $this->em->getFilters()->enable('soft-deleteable');
    }

    /**
     * @test
     */
    function shouldUpdateSoftdeletedNodes() {
        $this->populate();

        $repo = $this->em->getRepository(self::CATEGORY);
        $milk = $repo->findOneByTitle('milk');

        $this->em->remove($milk);
        $this->em->flush();

        $cheese = $repo->findOneByTitle('cheese');
        $camember = new Category;
        $camember->setTitle('camember');
        $camember->setParent($cheese);

        $this->em->persist($camember);
        $this->em->flush();

        $this->assertSame(14, $camember->getLeft());
        $this->assertSame(15, $camember->getRight());
        $this->assertNull($camember->getParent(), 'Cheese must have been softdeleted as branch node');
    }

    private function populate() {
        $tree = array(
            'food' => array(
                'vegetables' => array(
                    'carrots' => null,
                    'cabbages' => null
                ),
                'milk' => array(
                    'cheese' => null,
                    'butter' => null
                )
            )
        );
        $create = function($em, array $tree, Category $parent = null) use(&$create) {
            foreach ($tree as $name => $branch) {
                $node = new Category;
                $node->setTitle($name);
                $node->setParent($parent);
                $em->persist($node);
                if (is_array($branch)) {
                    $create($em, $branch, $node);
                }
            }
        };
        $create($this->em, $tree);
        $this->em->flush();
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::CATEGORY,
        );
    }
}
