<?php

namespace Gedmo\Mapping\Xml;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\DriverChain;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Gedmo\Timestampable\TimestampableListener;
use Tool\BaseTestCaseOM;

/**
 * These are mapping extension tests
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TimestampableMappingTest extends BaseTestCaseOM
{
    /**
     * @var Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var Gedmo\Timestampable\TimestampableListener
     */
    private $timestampable;

    public function setUp()
    {
        parent::setUp();

        $xmlDriver = new XmlDriver(__DIR__.'/../Driver/Xml');

        $chain = new DriverChain();
        $chain->addDriver($xmlDriver, 'Mapping\Fixture\Xml');

        $this->timestampable = new TimestampableListener();
        $this->evm = new EventManager();
        $this->evm->addEventSubscriber($this->timestampable);

        $this->em = $this->getMockSqliteEntityManager(array(
            'Mapping\Fixture\Xml\Timestampable',
            'Mapping\Fixture\Xml\Status',
        ), $chain);
    }

    public function testTimestampableMetadata()
    {
        $meta = $this->em->getClassMetadata('Mapping\Fixture\Xml\Timestampable');
        $config = $this->timestampable->getConfiguration($this->em, $meta->name);

        $this->assertArrayHasKey('create', $config);
        $this->assertEquals('created', $config['create'][0]);
        $this->assertArrayHasKey('update', $config);
        $this->assertEquals('updated', $config['update'][0]);
        $this->assertArrayHasKey('change', $config);
        $onChange = $config['change'][0];

        $this->assertEquals('published', $onChange['field']);
        $this->assertEquals('status.title', $onChange['trackedField']);
        $this->assertEquals('Published', $onChange['value']);
    }
}
