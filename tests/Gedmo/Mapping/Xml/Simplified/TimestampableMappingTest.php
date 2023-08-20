<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping\Xml\Simplified;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Gedmo\Mapping\Annotation\TrackingAwareAnnotationInterface;
use Gedmo\Tests\Mapping\Fixture\Xml\Status;
use Gedmo\Tests\Mapping\Fixture\Xml\Timestampable;
use Gedmo\Tests\Tool\BaseTestCaseORM;
use Gedmo\Timestampable\TimestampableListener;

/**
 * These are mapping extension tests
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class TimestampableMappingTest extends BaseTestCaseORM
{
    /**
     * @var TimestampableListener
     */
    private $timestampable;

    protected function setUp(): void
    {
        parent::setUp();

        $this->timestampable = new TimestampableListener();
        $evm = new EventManager();
        $evm->addEventSubscriber($this->timestampable);

        $this->getDefaultMockSqliteEntityManager($evm);
    }

    public function testTimestampableMetadata(): void
    {
        $meta = $this->em->getClassMetadata(Timestampable::class);
        $config = $this->timestampable->getConfiguration($this->em, $meta->getName());

        static::assertArrayHasKey(TrackingAwareAnnotationInterface::EVENT_CREATE, $config);
        static::assertSame('created', $config[TrackingAwareAnnotationInterface::EVENT_CREATE][0]);
        static::assertArrayHasKey(TrackingAwareAnnotationInterface::EVENT_UPDATE, $config);
        static::assertSame('updated', $config[TrackingAwareAnnotationInterface::EVENT_UPDATE][0]);
        static::assertArrayHasKey(TrackingAwareAnnotationInterface::EVENT_CHANGE, $config);
        $onChange = $config[TrackingAwareAnnotationInterface::EVENT_CHANGE][0];

        static::assertSame('published', $onChange['field']);
        static::assertSame('status.title', $onChange['trackedField']);
        static::assertSame('Published', $onChange['value']);
    }

    protected function getMetadataDriverImplementation(): MappingDriver
    {
        $xmlDriver = new SimplifiedXmlDriver([
            __DIR__.'/../../Driver/Xml' => 'Gedmo\Tests\Mapping\Fixture\Xml',
        ]);

        $chain = new MappingDriverChain();
        $chain->addDriver($xmlDriver, 'Gedmo\Tests\Mapping\Fixture\Xml');

        return $chain;
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            Timestampable::class,
            Status::class,
        ];
    }
}
