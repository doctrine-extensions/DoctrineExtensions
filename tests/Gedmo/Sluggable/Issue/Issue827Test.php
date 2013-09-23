<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventManager;
use Tool\BaseTestCaseORM;
use Sluggable\Fixture\Issue827\Article;
use Sluggable\Fixture\Issue827\Category;

/**
 * These are tests for Sluggable behavior
 *
 * @author Anders S. Øfsdahl <anders@aloof.no>
 * @link http://www.aloof.no
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Issue827Test extends BaseTestCaseORM
{
    const ARTICLE = 'Sluggable\\Fixture\\Issue827\\Article';
    const CATEGORY = 'Sluggable\\Fixture\\Issue827\\Category';

    protected function setUp()
    {
        parent::setUp();

        $evm = new EventManager;
        $evm->addEventSubscriber(new SluggableListener);

        $this->getMockSqliteEntityManager($evm);
    }

    /**
     * @test
     * @group issue827
     */
    function shouldHandleForeignKeyUniqueBasedSlug()
    {
        // Creating categories
      
        $testCat1 = new Category();
        $testCat1->setTitle('Category1');
        $this->em->persist($testCat1);
        $this->em->flush();
        
        $this->assertEquals('category1', $testCat1->getSlug());
      
        $testCat11 = new Category();
        $testCat11->setTitle('Category1');
        $this->em->persist($testCat11);
        $this->em->flush();
        
        $this->assertEquals('category1-1', $testCat11->getSlug());
        
        $testCat2 = new Category();
        $testCat2->setTitle('Category2');
        $this->em->persist($testCat2);
        $this->em->flush();
        
        $this->assertEquals('category2', $testCat2->getSlug());

        // Creating articles
        
        $test = new Article;
        $test->setTitle('Unique to category 1');
        $test->setCategory($testCat1);
        $this->em->persist($test);
        $this->em->flush();

        $this->assertEquals('unique-to-category-1', $test->getSlug());

        $test2 = new Article;
        $test2->setTitle('Unique to category 2');
        $test2->setCategory($testCat2);
        $this->em->persist($test2);
        $this->em->flush();

        $this->assertEquals('unique-to-category-2', $test2->getSlug());

        $test3 = new Article;
        $test3->setTitle('Unique to category 1');
        $test3->setCategory($testCat1);
        $this->em->persist($test3);
        $this->em->flush();

        $this->assertEquals('unique-to-category-1-1', $test3->getSlug());
    }

    /**
     * @test
     * @group issue827
     */
    function handlePersistedSlugsForForeignKeyUniqueBased()
    {
        // Creating categories
      
        $testCat1 = new Category();
        $testCat1->setTitle('Category1');
        $this->em->persist($testCat1);
        
        $testCat11 = new Category();
        $testCat11->setTitle('Category1');
        $this->em->persist($testCat11);
        
        
        $testCat2 = new Category();
        $testCat2->setTitle('Category2');
        $this->em->persist($testCat2);
        
        // Creating articles
        
        $test = new Article;
        $test->setTitle('Unique to category 1');
        $test->setCategory($testCat1);
        $this->em->persist($test);

        $test2 = new Article;
        $test2->setTitle('Unique to category 2');
        $test2->setCategory($testCat2);
        $this->em->persist($test2);

        $test3 = new Article;
        $test3->setTitle('Unique to category 1');
        $test3->setCategory($testCat1);
        $this->em->persist($test3);
        
        $this->em->flush();

        $this->assertEquals('category1', $testCat1->getSlug());
        $this->assertEquals('category1-1', $testCat11->getSlug());
        $this->assertEquals('category2', $testCat2->getSlug());
        $this->assertEquals('unique-to-category-1', $test->getSlug());
        $this->assertEquals('unique-to-category-2', $test2->getSlug());
        $this->assertEquals('unique-to-category-1-1', $test3->getSlug());
    }

    protected function getUsedEntityFixtures()
    {
        return array(
            self::ARTICLE,
            self::CATEGORY
        );
    }
}
