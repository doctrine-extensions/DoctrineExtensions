<?php

namespace Gedmo\Tests\References;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver as MongoDBAnnotationDriver;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver as ORMAnnotationDriver;
use Gedmo\References\ReferencesListener;
use Gedmo\Tests\References\Fixture\ODM\MongoDB\Metadata;
use Gedmo\Tests\References\Fixture\ODM\MongoDB\Product;
use Gedmo\Tests\References\Fixture\ORM\Category;
use Gedmo\Tests\References\Fixture\ORM\StockItem;
use Gedmo\Tests\Tool\BaseTestCaseOM;

final class ReferencesListenerTest extends BaseTestCaseOM
{
    private $em;
    private $dm;

    protected function setUp(): void
    {
        parent::setUp();

        if (!extension_loaded('mongodb')) {
            static::markTestSkipped('Missing Mongo extension.');
        }

        $reader = new AnnotationReader();

        $this->dm = $this->getMockDocumentManager('test', new MongoDBAnnotationDriver($reader, __DIR__.'/Fixture/ODM/MongoDB'));

        $listener = new ReferencesListener([
            'document' => $this->dm,
        ]);

        $this->evm->addEventSubscriber($listener);

        $reader = new AnnotationReader();

        $this->em = $this->getMockSqliteEntityManager(
            [
                StockItem::class,
                Category::class,
            ],
            new ORMAnnotationDriver($reader, __DIR__.'/Fixture/ORM')
        );
        $listener->registerManager('entity', $this->em);
    }

    public function testShouldPersistReferencedIdentifiersIntoIdentifierField()
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

        static::assertSame($product->getId(), $stockItem->getProductId());
    }

    public function testShouldPopulateReferenceOneWithProxyFromIdentifierField()
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

        static::assertSame($product, $stockItem->getProduct());
    }

    public function testShouldPopulateReferenceManyWithLazyCollectionInstance()
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

        static::assertInstanceOf(Collection::class, $product->getStockItems());
        static::assertSame(2, $product->getStockItems()->count());

        $first = $product->getStockItems()->first();

        static::assertInstanceOf(get_class($stockItem), $first);
        static::assertSame('APP-TV', $first->getSku());

        $last = $product->getStockItems()->last();

        static::assertInstanceOf(get_class($stockItem), $last);
        static::assertSame('AMZN-APP-TV', $last->getSku());
    }

    public function testShouldPopulateReferenceManyEmbedWithLazyCollectionInstance()
    {
        $tvCategory = new Category();
        $tvCategory->setName('Television');
        $this->em->persist($tvCategory);

        $cellPhoneCategory = new Category();
        $cellPhoneCategory->setName('CellPhone');
        $this->em->persist($cellPhoneCategory);

        $this->em->clear();

        $tvMetadata = new Metadata($tvCategory);

        $appleTV = new Product();
        $appleTV->setName('Apple TV');
        $this->dm->persist($appleTV);
        $this->dm->clear();

        $samsungTV = new Product();
        $samsungTV->setName('Samsung TV');
        $this->dm->persist($samsungTV);
        $this->dm->flush();

        $iPhone = new Product();
        $iPhone->setName('iPhone');
        $this->dm->persist($iPhone);
        $this->dm->flush();

        $appleTV->addMetadata($tvMetadata);
        $samsungTV->addMetadata($tvMetadata);
        $this->dm->persist($samsungTV);
        $this->dm->persist($appleTV);
        $this->dm->flush();

        static::assertSame($appleTV->getMetadatas()->first()->getCategoryId(), $tvMetadata->getCategoryId());
        static::assertSame($appleTV->getMetadatas()->first()->getCategory()->getName(), $tvMetadata->getCategory()->getName());
        static::assertSame($samsungTV->getMetadatas()->first()->getCategoryId(), $tvMetadata->getCategoryId());
        static::assertSame($samsungTV->getMetadatas()->first()->getCategory()->getName(), $tvMetadata->getCategory()->getName());

        $tvs = $tvCategory->getProducts();
        static::assertNotNull($tvs);
        static::assertContainsOnlyInstancesOf(Product::class, $tvs);
    }
}
