<?php

namespace Gedmo\Tests\Mapping\Xml\Simplified;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\DriverChain;
use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use Gedmo\Tests\Mapping\Fixture\Xml\Status;
use Gedmo\Tests\Mapping\Fixture\Xml\Timestampable;
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
final class TimestampableMappingTest extends BaseTestCaseORM
{
    /**
     * @var Gedmo\Timestampable\TimestampableListener
     */
    private $timestampable;

    protected function setUp(): void
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
            Timestampable::class,
            Status::class,
        ];
    }

    public function testTimestampableMetadata()
    {
        $meta = $this->em->getClassMetadata(Timestampable::class);
        $config = $this->timestampable->getConfiguration($this->em, $meta->name);

        static::assertArrayHasKey('create', $config);
        static::assertSame('created', $config['create'][0]);
        static::assertArrayHasKey('update', $config);
        static::assertSame('updated', $config['update'][0]);
        static::assertArrayHasKey('change', $config);
        $onChange = $config['change'][0];

        static::assertSame('published', $onChange['field']);
        static::assertSame('status.title', $onChange['trackedField']);
        static::assertSame('Published', $onChange['value']);
    }
}
