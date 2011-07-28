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

        $this->assertArrayHasKey('slugFields', $config);
        $this->assertEquals('slug', $config['slugFields']['slug']['slug']);
        $this->assertArrayHasKey('style', $config['slugFields']['slug']);
        $this->assertEquals('camel', $config['slugFields']['slug']['style']);
        $this->assertArrayHasKey('updatable', $config['slugFields']['slug']);
        $this->assertTrue($config['slugFields']['slug']['updatable']);
        $this->assertArrayHasKey('unique', $config['slugFields']['slug']);
        $this->assertTrue($config['slugFields']['slug']['unique']);
        $this->assertArrayHasKey('separator', $config['slugFields']['slug']);
        $this->assertEquals('_', $config['slugFields']['slug']['separator']);

        $this->assertArrayHasKey('fields', $config);
        $this->assertEquals(3, count($config['fields']['slug']));
        $fields = $config['fields'];

        $this->assertEquals('title', $fields['slug'][0]['field']);
        $this->assertEquals(0, $fields['slug'][0]['position']);
        $this->assertEquals('code', $fields['slug'][1]['field']);
        $this->assertEquals(false, $fields['slug'][1]['position']);
        $this->assertEquals('ean', $fields['slug'][2]['field']);
        $this->assertEquals(1, $fields['slug'][2]['position']);
    }
}
