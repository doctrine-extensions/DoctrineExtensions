<?php

namespace Gedmo\Tree;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseMongoODM;
use Doctrine\Common\Util\Debug;
use Tree\Fixture\RootCategory;

/**
 * These are tests for Tree behavior
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Tree
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class MaterializedPathODMMongoDBTest extends BaseTestCaseMongoODM
{
    const CATEGORY = "Tree\\Fixture\\Document\\Category";

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $evm->addEventSubscriber(new TreeListener);

        $this->getMockDocumentManager($evm);
    }

    /**
     * @test
     */
    function insertUpdateAndRemove()
    {
        // Insert
        $category = $this->createCategory();
        $category->setTitle('1');
        $category2 = $this->createCategory();
        $category2->setTitle('2');
        $category3 = $this->createCategory();
        $category3->setTitle('3');
        $category4 = $this->createCategory();
        $category4->setTitle('4');

        $category2->setParent($category);
        $category3->setParent($category2);

        $this->dm->persist($category4);
        $this->dm->persist($category3);
        $this->dm->persist($category2);
        $this->dm->persist($category);
        $this->dm->flush();

        $this->dm->refresh($category);
        $this->dm->refresh($category2);
        $this->dm->refresh($category3);
        $this->dm->refresh($category4);

        $this->assertEquals('1,', $category->getPath());
        $this->assertEquals('1,2,', $category2->getPath());
        $this->assertEquals('1,2,3,', $category3->getPath());
        $this->assertEquals('4,', $category4->getPath());

        // Update
        $category2->setParent(null);

        $this->dm->persist($category2);
        $this->dm->flush();

        $this->dm->refresh($category);
        $this->dm->refresh($category2);
        $this->dm->refresh($category3);

        $this->assertEquals('1,', $category->getPath());
        $this->assertEquals('2,', $category2->getPath());
        $this->assertEquals('2,3,', $category3->getPath());

        // Remove
        $this->dm->remove($category);
        $this->dm->remove($category2);
        $this->dm->flush();

        $result = $this->dm->createQueryBuilder()->find(self::CATEGORY)->getQuery()->execute();

        $this->assertEquals(1, $result->count());
        $this->assertEquals('4', $result->getNext()->getTitle());
    }

    public function createCategory()
    {
        $class = self::CATEGORY;
        return new $class;
    }
}
