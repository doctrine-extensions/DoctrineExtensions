<?php

namespace Gedmo\Tree\NestedSet;

use Gedmo\TestTool\ObjectManagerTestCase;
use Gedmo\Tree\TreeListener;
use Gedmo\Fixture\Tree\NestedSet\Category;
use Gedmo\SoftDeleteable\SoftDeleteableListener;
use Doctrine\Common\EventManager;

class IgnoredFilterTest extends ObjectManagerTestCase
{
    const CATEGORY = "Gedmo\Fixture\Tree\NestedSet\Category";

    private $em;

    protected function setUp()
    {
        $evm = new EventManager;
        $evm->addEventSubscriber(new TreeListener);
        $evm->addEventSubscriber(new SoftDeleteableListener);

        $this->em = $this->createEntityManager($evm);
        $this->em->getConfiguration()->addFilter('soft-deleteable', 'Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter');
        $this->em->getFilters()->enable('soft-deleteable');
        $this->createSchema($this->em, array(
            self::CATEGORY,
        ));
    }

    protected function tearDown()
    {
        $this->releaseEntityManager($this->em);
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
        $this->em->clear();

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
}
