<?php

namespace Gedmo\Tree\Document;

use Tool\BaseTestCaseMongoODM;
use Tree\Fixture\Path\Category;

/**
 *
 *
 * @author Michael Williams <michael.williams@funsational.com>
 * @package Gedmo.Tree
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class PathRepositoryTest extends BaseTestCaseMongoODM
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

    public function testCountCorrectDescendents()
    {
    	/**
    	 * Create a tree like
    	 * - a ; 5 descendants
    	 *     - b
    	 *     - c
    	 *     - a ; 2 descendants
    	 *         - e
    	 *         - f
    	 */
    	$a = new Category();
        $a->setTitle('A');

        $ab = new Category();
        $ab->setTitle('B');
        $ab->setParent($a);

        $ac = new Category();
        $ac->setTitle('C');
        $ac->setParent($a);

        $aa = new Category();
        $aa->setTitle('A');
        $aa->setParent($a);

        $aae = new Category();
        $aae->setTitle('E');
        $aae->setParent($aa);

        $aaf = new Category();
        $aaf->setTitle('F');
        $aaf->setParent($aa);

        $this->dm->persist($a);
        $this->dm->persist($ab);
        $this->dm->persist($ac);
        $this->dm->persist($aa);
        $this->dm->persist($aae);
        $this->dm->persist($aaf);

        $this->dm->flush(array('safe' => true));

        $repo = $this->dm->getRepository(self::CATEGORY);

        $aCount = $repo->countDescendants($a->getPath());
        $aaCount = $repo->countDescendants($aa->getPath());

        $this->clearCollection();

        $this->assertEquals(5, $aCount);
        $this->assertEquals(2, $aaCount);
    }

    public function testFetchCorrectDescendents()
    {
    	// Make sure everything is gone before adding new test data
        $this->clearCollection();

        /**
         * Create a tree like
         * - a ; 5 descendants
         *     - b
         *     - c
         *     - a ; 2 descendants
         *         - e
         *         - f
         */
        $a = new Category();
        $a->setTitle('A');

        $ab = new Category();
        $ab->setTitle('B');
        $ab->setParent($a);

        $ac = new Category();
        $ac->setTitle('C');
        $ac->setParent($a);

        $aa = new Category();
        $aa->setTitle('A');
        $aa->setParent($a);

        $aae = new Category();
        $aae->setTitle('E');
        $aae->setParent($aa);

        $aaf = new Category();
        $aaf->setTitle('F');
        $aaf->setParent($aa);

        $this->dm->persist($a);
        $this->dm->persist($ab);
        $this->dm->persist($ac);
        $this->dm->persist($aa);
        $this->dm->persist($aae);
        $this->dm->persist($aaf);

        $this->dm->flush(array('safe' => true));

        $repo = $this->dm->getRepository(self::CATEGORY);

        $aDescendants = $repo->fetchDescendants($a->getPath(), 'sortOrder', 'asc');

        // Fetch a,a descendant
        $aaDescendants = $repo->fetchDescendants($aa->getPath(), 'sortOrder', 'asc');

        // Advance pointer
        $aDescendants->next();
        $abDB = $aDescendants->current(); // a,b
        $this->assertEquals($ab, $abDB);

        // Advance pointer
        $aDescendants->next();
        $acDB = $aDescendants->current(); // a,c
        $this->assertEquals($ac, $acDB);

        // Advance pointer
        $aDescendants->next();
        $aaDB = $aDescendants->current(); // a,a
        $this->assertEquals($aa, $aaDB);

        // Advance pointer
        $aDescendants->next();
        $aaeDB = $aDescendants->current(); // a,a,e
        $this->assertEquals($aae, $aaeDB);

        // Advance pointer
        $aDescendants->next();
        $aafDB = $aDescendants->current(); // a,a,f
        $this->assertEquals($aaf, $aafDB);

        $this->assertFalse($aDescendants->hasNext());

        // Test a,a descendants

        // Advance pointer
        $aaDescendants->next();
        $aaeDB = $aaDescendants->current(); // a,a,e
        $this->assertEquals($aae, $aaeDB);

        // Advance pointer
        $aaDescendants->next();
        $aafDB = $aaDescendants->current(); // a,a,f
        $this->assertEquals($aaf, $aafDB);

        // assure we only have 2
        $this->assertFalse($aaDescendants->hasNext());

        // Remove now that all tests are done
        $this->clearCollection();
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