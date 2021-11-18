<?php

namespace Gedmo\Tests\Mapping\Xml;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\DriverChain;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Gedmo\Sluggable\Handler\RelativeSlugHandler;
use Gedmo\Sluggable\Handler\TreeSlugHandler;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Tests\Mapping\Fixture\Xml\Sluggable;
use Gedmo\Tests\Tool\BaseTestCaseORM;

/**
 * These are mapping extension tests
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class SluggableMappingTest extends BaseTestCaseORM
{
    /**
     * @var Gedmo\Sluggable\SluggableListener
     */
    private $sluggable;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sluggable = new SluggableListener();
        $evm = new EventManager();
        $evm->addEventSubscriber($this->sluggable);

        $this->getMockSqliteEntityManager($evm);
    }

    protected function getUsedEntityFixtures()
    {
        return [Sluggable::class];
    }

    protected function getMetadataDriverImplementation()
    {
        $xmlDriver = new XmlDriver(__DIR__.'/../Driver/Xml');

        $chain = new DriverChain();
        $chain->addDriver($xmlDriver, 'Gedmo\Tests\Mapping\Fixture\Xml');

        return $chain;
    }

    /**
     * @test
     */
    public function shouldBeAbleToMapSluggableMetadata()
    {
        $meta = $this->em->getClassMetadata(Sluggable::class);
        $config = $this->sluggable->getConfiguration($this->em, $meta->name);

        static::assertArrayHasKey('slug', $config['slugs']);
        static::assertCount(1, $config['slugs']);
        $config = $config['slugs']['slug'];

        static::assertSame('slug', $config['slug']);
        static::assertArrayHasKey('style', $config);
        static::assertSame('camel', $config['style']);
        static::assertArrayHasKey('updatable', $config);
        static::assertFalse($config['updatable']);
        static::assertArrayHasKey('unique', $config);
        static::assertTrue($config['unique']);
        static::assertArrayHasKey('separator', $config);
        static::assertSame('_', $config['separator']);

        static::assertArrayHasKey('fields', $config);
        static::assertCount(3, $config['fields']);
        $fields = $config['fields'];

        static::assertSame('title', $fields[0]);
        static::assertSame('ean', $fields[1]);
        static::assertSame('code', $fields[2]);

        static::assertArrayHasKey('handlers', $config);
        static::assertCount(2, $config['handlers']);
        $handlers = $config['handlers'];

        static::assertArrayHasKey(TreeSlugHandler::class, $handlers);
        static::assertArrayHasKey(RelativeSlugHandler::class, $handlers);

        $first = $handlers[TreeSlugHandler::class];
        static::assertCount(2, $first);
        static::assertArrayHasKey('parentRelationField', $first);
        static::assertArrayHasKey('separator', $first);
        static::assertSame('parent', $first['parentRelationField']);
        static::assertSame('/', $first['separator']);

        $second = $handlers[RelativeSlugHandler::class];
        static::assertCount(3, $second);
        static::assertArrayHasKey('relationField', $second);
        static::assertArrayHasKey('relationSlugField', $second);
        static::assertArrayHasKey('separator', $second);
        static::assertSame('parent', $second['relationField']);
        static::assertSame('test', $second['relationSlugField']);
        static::assertSame('-', $second['separator']);
    }
}
