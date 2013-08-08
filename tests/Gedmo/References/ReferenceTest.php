<?php

namespace Gedmo\References;

use Doctrine\Common\EventManager;
use Gedmo\Fixture\References\ODM\MongoDB\Product;
use Gedmo\Fixture\References\ODM\MongoDB\Metadata;
use Gedmo\Fixture\References\ORM\Category;
use Gedmo\Fixture\References\ORM\StockItem;
use Gedmo\TestTool\ObjectManagerTestCase;
use Gedmo\References\ReferencesListener;
use Doctrine\Common\Collections\Collection;

class ReferenceTest extends ObjectManagerTestCase
{
    private $em;
    private $dm;
    private $listener;

    protected function setUp()
    {
        $evm = new EventManager;
        $evm->addEventSubscriber($this->listener = new ReferencesListener);

        $this->dm = $this->createDocumentManager($evm);
        $this->listener->registerManager('document', $this->dm);

        $this->em = $this->createEntityManager($evm);
        $this->createSchema($this->em, array(
            'Gedmo\Fixture\References\ORM\StockItem',
            'Gedmo\Fixture\References\ORM\Category'
        ));
        $this->listener->registerManager('entity', $this->em);
    }

    protected function tearDown()
    {
        $this->releaseEntityManager($this->em);
        $this->releaseDocumentManager($this->dm);
    }

    /**
     * @test
     */
    function shouldPersistReferencedIdentifiersIntoIdentifierField()
    {
        $stockItem = new StockItem();
        $stockItem->setName('Apple TV');
        $stockItem->setSku('APP-TV');
        $stockItem->setQuantity(25);

        $product = new Product();
        $product->setName('Apple TV');

        $this->dm->persist($product);
        $this->dm->flush();

        $stockItem->setProduct($product);

        $this->em->persist($stockItem);

        $this->assertEquals($product->getId(), $stockItem->getProductId());
    }

    /**
     * @test
     */
    function shouldPopulateReferenceOneWithProxyFromIdentifierField()
    {
        $product = new Product();
        $product->setName('Apple TV');

        $this->dm->persist($product);
        $this->dm->flush();

        $stockItem = new StockItem();
        $stockItem->setName('Apple TV');
        $stockItem->setSku('APP-TV');
        $stockItem->setQuantity(25);
        $stockItem->setProductId($product->getId());

        $this->em->persist($stockItem);
        $this->em->flush();
        $this->em->clear();

        $stockItem = $this->em->find(get_class($stockItem), $stockItem->getId());

        $this->assertSame($product, $stockItem->getProduct());
    }

    /**
     * @test
     */
    function shouldPopulateReferenceManyWithLazyCollectionInstance()
    {
        $product = new Product();
        $product->setName('Apple TV');

        $this->dm->persist($product);
        $this->dm->flush();
        $this->dm->clear();

        $stockItem = new StockItem();
        $stockItem->setName('Apple TV');
        $stockItem->setSku('APP-TV');
        $stockItem->setQuantity(25);
        $stockItem->setProductId($product->getId());

        $this->em->persist($stockItem);

        $stockItem = new StockItem();
        $stockItem->setName('Apple TV');
        $stockItem->setSku('AMZN-APP-TV');
        $stockItem->setQuantity(25);
        $stockItem->setProductId($product->getId());

        $this->em->persist($stockItem);
        $this->em->flush();

        $product = $this->dm->find(get_class($product), $product->getId());

        $this->assertInstanceOf('Doctrine\Common\Collections\Collection', $product->getStockItems());
        $this->assertEquals(2, $product->getStockItems()->count());

        $first = $product->getStockItems()->first();

        $this->assertInstanceOf(get_class($stockItem), $first);
        $this->assertEquals('APP-TV', $first->getSku());

        $last = $product->getStockItems()->last();

        $this->assertInstanceOf(get_class($stockItem), $last);
        $this->assertEquals('AMZN-APP-TV', $last->getSku());
    }

    /**
     * @test
     */
    function shouldPopulateReferenceManyEmbedWithLazyCollectionInstance()
    {
        if ($this->em->getConnection()->getDatabasePlatform()->getName() === 'postgresql') {
            $this->markTestSkipped('Postgresql has some quirks.');
        }
        $tvCategory = new Category();
        $tvCategory->setName("Television");
        $this->em->persist($tvCategory);

        $cellPhoneCategory = new Category();
        $cellPhoneCategory->setName("CellPhone");
        $this->em->persist($cellPhoneCategory);

        $this->em->clear();

        $tvMetadata = new Metadata($tvCategory);

        $appleTV = new Product();
        $appleTV->setName('Apple TV');
        $this->dm->persist($appleTV);
        $this->dm->clear();

        $samsungTV = new Product();
        $samsungTV->setName('Samsung TV');
        $this->dm->persist( $samsungTV );
        $this->dm->flush();

        $iPhone = new Product();
        $iPhone->setName('iPhone');
        $this->dm->persist( $iPhone );
        $this->dm->flush();


        $appleTV->addMetadata( $tvMetadata );
        $samsungTV->addMetadata( $tvMetadata );
        $this->dm->persist( $samsungTV );
        $this->dm->persist($appleTV);
        $this->dm->flush();

        $this->assertEquals($appleTV->getMetadatas()->first(), $tvMetadata);
        $this->assertEquals($samsungTV->getMetadatas()->first(), $tvMetadata);


        $tvs = $tvCategory->getProducts();
        $this->assertNotNull($tvs);
        $this->assertNotNull($item = $this->findByNameFromCollection($tvs, 'Apple TV'));
        $this->assertInstanceOf(get_class($appleTV), $item);
        $this->assertSame($appleTV, $item);
        $this->assertNotNull($item = $this->findByNameFromCollection($tvs, 'Samsung TV'));
        $this->assertInstanceOf(get_class($samsungTV), $item);
        $this->assertSame($samsungTV, $item);
    }

    private function findByNameFromCollection(Collection $col, $name)
    {
        foreach ($col as $item) {
            if ($item->getName() === $name) {
                return $item;
            }
        }
        return null;
    }
}
