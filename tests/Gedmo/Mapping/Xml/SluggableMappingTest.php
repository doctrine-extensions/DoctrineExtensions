<?php

namespace Gedmo\Mapping\Xml;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\DriverChain;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Gedmo\Sluggable\SluggableListener;
use Tool\BaseTestCaseORM;

/**
 * These are mapping extension tests
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SluggableMappingTest extends BaseTestCaseORM
{
    /**
     * @var Gedmo\Sluggable\SluggableListener
     */
    private $sluggable;

    public function setUp()
    {
        parent::setUp();

        $this->sluggable = new SluggableListener;
        $evm = new EventManager;
        $evm->addEventSubscriber($this->sluggable);

        $this->getMockSqliteEntityManager($evm);
    }

    protected function getUsedEntityFixtures()
    {
        return array('Mapping\Fixture\Xml\Sluggable');
    }

    protected function getMetadataDriverImplementation()
    {
        $xmlDriver = new XmlDriver(__DIR__.'/../Driver/Xml');

        $chain = new DriverChain;
        $chain->addDriver($xmlDriver, 'Mapping\Fixture\Xml');
        return $chain;
    }

    /**
     * @test
     */
    public function shouldBeAbleToMapSluggableMetadata()
    {
        $meta = $this->em->getClassMetadata('Mapping\Fixture\Xml\Sluggable');
        $config = $this->sluggable->getConfiguration($this->em, $meta->name);

        $this->assertArrayHasKey('slug', $config['slugs']);
        $this->assertCount(1, $config['slugs']);
        $config = $config['slugs']['slug'];

        $this->assertEquals('slug', $config['slug']);
        $this->assertArrayHasKey('style', $config);
        $this->assertEquals('camel', $config['style']);
        $this->assertArrayHasKey('updatable', $config);
        $this->assertFalse($config['updatable']);
        $this->assertArrayHasKey('unique', $config);
        $this->assertTrue($config['unique']);
        $this->assertArrayHasKey('separator', $config);
        $this->assertEquals('_', $config['separator']);

        $this->assertArrayHasKey('fields', $config);
        $this->assertCount(3, $config['fields']);
        $fields = $config['fields'];

        $this->assertEquals('title', $fields[0]);
        $this->assertEquals('ean', $fields[1]);
        $this->assertEquals('code', $fields[2]);

        $this->assertArrayHasKey('handlers', $config);
        $this->assertEquals(2, count($config['handlers']));
        $handlers = $config['handlers'];

        $this->assertArrayHasKey('Gedmo\Sluggable\Handler\TreeSlugHandler', $handlers);
        $this->assertArrayHasKey('Gedmo\Sluggable\Handler\RelativeSlugHandler', $handlers);

        $first = $handlers['Gedmo\Sluggable\Handler\TreeSlugHandler'];
        $this->assertEquals(2, count($first));
        $this->assertArrayHasKey('parentRelationField', $first);
        $this->assertArrayHasKey('separator', $first);
        $this->assertEquals('parent', $first['parentRelationField']);
        $this->assertEquals('/', $first['separator']);

        $second = $handlers['Gedmo\Sluggable\Handler\RelativeSlugHandler'];
        $this->assertEquals(3, count($second));
        $this->assertArrayHasKey('relationField', $second);
        $this->assertArrayHasKey('relationSlugField', $second);
        $this->assertArrayHasKey('separator', $second);
        $this->assertEquals('parent', $second['relationField']);
        $this->assertEquals('test', $second['relationSlugField']);
        $this->assertEquals('-', $second['separator']);
    }
}
