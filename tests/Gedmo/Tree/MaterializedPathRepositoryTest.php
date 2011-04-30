<?php

namespace Gedmo\Tree;

use Tool\BaseTestCaseMongoODM;
use Doctrine\Common\EventManager;
use Translatable\Fixture\Document\SimpleArticle as Article;
use Tree\Fixture\Path\Category;
use Gedmo\Tree\Strategy\ODM\Path;

/**
 *
 *
 * @author Michael Williams <michael.williams@funsational.com>
 * @package Gedmo.Tree
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class MaterializedPathRepositoryTest extends BaseTestCaseMongoODM
{
    const CATEGORY = 'Tree\\Fixture\\Path\\Category';

    private $listener;

    public function setUp()
    {
        parent::setUp();

        $this->getMockDocumentManager(null, true);

        $treeListener = null;
        foreach ($this->dm->getEventManager()->getListeners() as $event => $listeners) {
            foreach ($listeners as $hash => $listener) {
                if ($listener instanceof \Gedmo\Tree\TreeListener) {
                    $treeListener = $listener;
                    break;
                }
            }
            if ($treeListener) {
                break;
            }
        }

        if (is_null($treeListener)) {
            $this->markTestSkipped('Can not find the tree listener. Did you attach it to the Doctrine Event Manager?');
        }

        $this->listener = $treeListener;

        // @todo Remove test code
        $this->skipCollectionsOnDrop[] = 'Category';
    }

    public function testRepositoryMagicMethods()
    {
    	$repo = $this->dm->getRepository(self::CATEGORY);

        $child1 = new Category;
        $child1->setTitle('Child 1');

        $child2 = new Category;
        $child2->setTitle('Child 2');

        $subChild = new Category;
        $subChild->setTitle('Sub Child');

        $lastChild1 = new Category;
        $lastChild1->setTitle('Child 3');

        $lastChild2 = new Category;
        $lastChild2->setTitle('Child 4');

        $repo
            ->persistAsFirstChild($child2)
            ->persistAsFirstChildOf($subChild, $child2)
            ->persistAsFirstChild($child1)
            ->persistAsLastChild($lastChild1)
            ->persistAsLastChild($lastChild2)
        ;

        $this->dm->flush();

        $this->assertEquals(1, $child1->getSortOrder());
        $this->assertEquals(0, $child1->getChildCount());

        $this->assertEquals(2, $child2->getSortOrder());
        $this->assertEquals(1, $child2->getChildCount());

        $this->assertEquals(3, $subChild->getSortOrder());
        $this->assertEquals(0, $subChild->getChildCount());

        $this->assertEquals(4, $lastChild1->getSortOrder());
        $this->assertEquals(0, $lastChild1->getChildCount());

        $this->assertEquals(5, $lastChild2->getSortOrder());
        $this->assertEquals(0, $lastChild2->getChildCount());
    }

    /**
     * Remove all nodes from the collection so a different test does not
     * have to deal with them
     */
    private function clearCollection()
    {
    	$this->dm->createQueryBuilder(self::CATEGORY)->remove()->getQuery()->execute();
    }
}