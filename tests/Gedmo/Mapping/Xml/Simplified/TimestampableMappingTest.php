<?php

namespace Gedmo\Tests\Mapping\Xml\Simplified;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\DriverChain;
use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Timestampable\TimestampableListener;

/**
 * These are mapping extension tests
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TimestampableMappingTest extends BaseTestCaseORM
{
    /**
     * @var Gedmo\Timestampable\TimestampableListener
     */
    private $timestampable;

    public function setUp(): void
    {
        parent::setUp();

        $this->timestampable = new TimestampableListener();
        $evm = new EventManager();
        $evm->addEventSubscriber($this->timestampable);

        $this->getMockSqliteEntityManager($evm);
    }

    protected function getMetadataDriverImplementation()
    {
        $xmlDriver = new SimplifiedXmlDriver([
            __DIR__.'/../../Driver/Xml' => 'Gedmo\Tests\Mapping\Fixture\Xml',
        ]);

        $chain = new DriverChain();
        $chain->addDriver($xmlDriver, 'Gedmo\Tests\Mapping\Fixture\Xml');

        return $chain;
    }

    protected function getUsedEntityFixtures()
    {
        return [
            'Gedmo\Tests\Mapping\Fixture\Xml\Timestampable',
            'Gedmo\Tests\Mapping\Fixture\Xml\Status',
        ];
    }

    public function testTimestampableMetadata()
    {
        $meta = $this->em->getClassMetadata('Gedmo\Tests\Mapping\Fixture\Xml\Timestampable');
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
