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
 * @package Gedmo.Mapping
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
    }
}
