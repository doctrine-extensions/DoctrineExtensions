<?php

namespace Gedmo\Tests\Mapping\Xml;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\DriverChain;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Gedmo\Sluggable\SluggableListener;
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
class SluggableMappingTest extends BaseTestCaseORM
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
        return ['Gedmo\Tests\Mapping\Fixture\Xml\Sluggable'];
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
        $meta = $this->em->getClassMetadata('Gedmo\Tests\Mapping\Fixture\Xml\Sluggable');
        $config = $this->sluggable->getConfiguration($this->em, $meta->name);

        static::assertArrayHasKey('slug', $config['slugs']);
        static::assertCount(1, $config['slugs']);
        $config = $config['slugs']['slug'];

        static::assertEquals('slug', $config['slug']);
        static::assertArrayHasKey('style', $config);
        static::assertEquals('camel', $config['style']);
        static::assertArrayHasKey('updatable', $config);
        static::assertFalse($config['updatable']);
        static::assertArrayHasKey('unique', $config);
        static::assertTrue($config['unique']);
        static::assertArrayHasKey('separator', $config);
        static::assertEquals('_', $config['separator']);

        static::assertArrayHasKey('fields', $config);
        static::assertCount(3, $config['fields']);
        $fields = $config['fields'];

        static::assertEquals('title', $fields[0]);
        static::assertEquals('ean', $fields[1]);
        static::assertEquals('code', $fields[2]);

        static::assertArrayHasKey('handlers', $config);
        static::assertCount(2, $config['handlers']);
        $handlers = $config['handlers'];

        static::assertArrayHasKey('Gedmo\Sluggable\Handler\TreeSlugHandler', $handlers);
        static::assertArrayHasKey('Gedmo\Sluggable\Handler\RelativeSlugHandler', $handlers);

        $first = $handlers['Gedmo\Sluggable\Handler\TreeSlugHandler'];
        static::assertCount(2, $first);
        static::assertArrayHasKey('parentRelationField', $first);
        static::assertArrayHasKey('separator', $first);
        static::assertEquals('parent', $first['parentRelationField']);
        static::assertEquals('/', $first['separator']);

        $second = $handlers['Gedmo\Sluggable\Handler\RelativeSlugHandler'];
        static::assertCount(3, $second);
        static::assertArrayHasKey('relationField', $second);
        static::assertArrayHasKey('relationSlugField', $second);
        static::assertArrayHasKey('separator', $second);
        static::assertEquals('parent', $second['relationField']);
        static::assertEquals('test', $second['relationSlugField']);
        static::assertEquals('-', $second['separator']);
    }
}
