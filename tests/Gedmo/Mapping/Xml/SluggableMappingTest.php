<?php

namespace Gedmo\Mapping\Xml;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\DriverChain;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Gedmo\Sluggable\SluggableListener;
use Tool\BaseTestCaseOM;

/**
 * These are mapping extension tests
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Mapping
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SluggableMappingTest extends BaseTestCaseOM
{
    /**
     * @var Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var Gedmo\Sluggable\SluggableListener
     */
    private $sluggable;

    public function setUp()
    {
        parent::setUp();

        $xmlDriver = new XmlDriver(__DIR__.'/../Driver/Xml');

        $chain = new DriverChain;
        $chain->addDriver($xmlDriver, 'Mapping\Fixture\Xml');

        $this->sluggable = new SluggableListener;
        $this->evm = new EventManager;
        $this->evm->addEventSubscriber($this->sluggable);

        $this->em = $this->getMockSqliteEntityManager(array(
            'Mapping\Fixture\Xml\Sluggable',
        ), $chain);
    }

    public function testSluggableMetadata()
    {
        $meta = $this->em->getClassMetadata('Mapping\Fixture\Xml\Sluggable');
        $config = $this->sluggable->getConfiguration($this->em, $meta->name);

        $this->assertArrayHasKey('slug', $config);
        $this->assertEquals('slug', $config['slug']);
        $this->assertArrayHasKey('style', $config);
        $this->assertEquals('camel', $config['style']);
        $this->assertArrayHasKey('updatable', $config);
        $this->assertTrue($config['updatable']);
        $this->assertArrayHasKey('unique', $config);
        $this->assertTrue($config['unique']);
        $this->assertArrayHasKey('separator', $config);
        $this->assertEquals('_', $config['separator']);

        $this->assertArrayHasKey('fields', $config);
        $this->assertEquals(3, count($config['fields']));
        $fields = $config['fields'];

        $this->assertEquals('title', $fields[0]['field']);
        $this->assertEquals(0, $fields[0]['position']);
        $this->assertEquals('code', $fields[1]['field']);
        $this->assertEquals(false, $fields[1]['position']);
        $this->assertEquals('ean', $fields[2]['field']);
        $this->assertEquals(1, $fields[2]['position']);
    }
}
