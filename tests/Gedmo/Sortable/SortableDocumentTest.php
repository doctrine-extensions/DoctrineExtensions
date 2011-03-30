<?php

namespace Gedmo\Sortable;

use Sortable\Fixture\Document\Article;
use Sortable\Fixture\Document\Category;

/**
 * These are tests for sluggable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Sluggable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SortableDocumentTest extends \PHPUnit_Framework_TestCase
{
    const TEST_CLASS_ARTICLE = 'Sortable\Fixture\Document\Article';
    const TEST_CLASS_CATEGORY = 'Sortable\Fixture\Document\Category';

    /**
     * @var DocumentManager
     */
    private $dm;

    public function setUp()
    {
        $config = new \Doctrine\ODM\MongoDB\Configuration();
        $config->setProxyDir(__DIR__ . '/Proxy');
        $config->setProxyNamespace('Gedmo\Sortable\Proxies');
        $config->setHydratorDir(__DIR__ . '/Hydrator');
        $config->setHydratorNamespace('Hydrator');
        $config->setDefaultDB('gedmo_sortable_tests');

   
        $config->setLoggerCallable(function(array $log) {
            print_r($log);
        });


        $reader = new \Doctrine\Common\Annotations\AnnotationReader();
        $reader->setDefaultAnnotationNamespace('Doctrine\ODM\MongoDB\Mapping\\');
        $config->setMetadataDriverImpl(
            new \Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver($reader, __DIR__ . '/Fixture/Document')
        );

        $evm = new \Doctrine\Common\EventManager();
        $sortableListener = new ODM\MongoDB\SortableListener();
        $evm->addEventSubscriber($sortableListener);

        if (!class_exists('Mongo')) {
            $this->markTestSkipped('Missing Mongo extension.');
        }

        try {
            $this->dm = \Doctrine\ODM\MongoDB\DocumentManager::create(
                new \Doctrine\MongoDB\Connection(),
                $config,
                $evm
            );

            $this->populate();
        } catch (\MongoException $e) {
            $this->markTestSkipped('Doctrine MongoDB ODM connection problem.');
        }
    }

    /**
     * With "sort" mapping
     */
    /*public function testSortable()
    {
        $repo = $this->dm->getRepository(self::TEST_CLASS_ARTICLE);
        $art0 = $repo->findOneByTitle('My Title');
        $art1 = $repo->findOneByTitle('My Article');

        $this->assertEquals(0, $art0->getSort(), 'insertion should increment $sort field');
        $this->assertEquals(1, $art1->getSort(), 'insertion should increment $sort field');

        $this->dm->remove($art0);
        $this->dm->flush();
        $this->dm->clear();

        $art1 = $repo->findOneByTitle('My Article');
        $this->assertEquals(0, $art1->getSort(), 'delete should demote all sortable after deleted one');
    }*/

    /**
     * With "sort" and "sort_identifier" mapping
     */
    public function testSortableWithIdentifier()
    {
        $repo = $this->dm->getRepository(self::TEST_CLASS_ARTICLE);
        $catRepo = $this->dm->getRepository(self::TEST_CLASS_CATEGORY);

        $art2 = $repo->findOneByTitle('My Title2');
        $art3 = $repo->findOneByTitle('My Article2');

        $this->assertEquals(0, $art2->getSort(), 'insertion should increment $sort field');
        $this->assertEquals(1, $art3->getSort(), 'insertion should increment $sort field');

        $this->dm->remove($art2);
        $this->dm->flush();
        $this->dm->clear();

        $art3 = $repo->findOneByTitle('My Article2');
        $art4 = $repo->findOneByTitle('My Article3');
        $this->assertEquals(0, $art3->getSort(), 'delete should demote all sortable after deleted one');
        $this->assertEquals(1, $art4->getSort(), 'delete should demote all sortable after deleted one');

        $cat0 = $catRepo->findOneByTitle('Title');
        $art3->setCategory($cat0);

        $this->dm->persist($art3);
        $this->dm->flush();
        $this->dm->clear();

        $art3 = $repo->findOneByTitle('My Article2');
        $art4 = $repo->findOneByTitle('My Article3');
        $this->assertEquals(2, $art3->getSort(), 'move the parent of a sortable should place the new child at the end');
        $this->assertEquals(0, $art4->getSort(), 'move the parent og a sortable should demote all previous child');
    }

    private function populate()
    {
        $this->dm->createQueryBuilder(self::TEST_CLASS_ARTICLE)->remove()->getQuery()->execute();
        $this->dm->createQueryBuilder(self::TEST_CLASS_CATEGORY)->remove()->getQuery()->execute();

        $cat0 = new Category();
        $cat0->setTitle('Title');

        $cat1 = new Category();
        $cat1->setTitle('Title2');

        $art0 = new Article();
        $art0->setTitle('My Title');
        $art0->setCategory($cat0);

        $art1 = new Article();
        $art1->setTitle('My Article');
        $art1->setCategory($cat0);

        $art2 = new Article();
        $art2->setTitle('My Title2');
        $art2->setCategory($cat1);

        $art3 = new Article();
        $art3->setTitle('My Article2');
        $art3->setCategory($cat1);

        $art4 = new Article();
        $art4->setTitle('My Article3');
        $art4->setCategory($cat1);

        $this->dm->persist($cat0);
        $this->dm->persist($cat1);
        $this->dm->persist($art0);
        $this->dm->persist($art1);
        $this->dm->persist($art2);
        $this->dm->persist($art3);
        $this->dm->persist($art4);
        $this->dm->flush();
        $this->dm->clear();
    }
}