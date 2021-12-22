<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping\Xml;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Gedmo\Loggable\Entity\LogEntry;
use Gedmo\Loggable\LoggableListener;
use Gedmo\Tests\Mapping\Fixture\Xml\Embedded;
use Gedmo\Tests\Mapping\Fixture\Xml\Loggable;
use Gedmo\Tests\Mapping\Fixture\Xml\LoggableWithEmbedded;
use Gedmo\Tests\Mapping\Fixture\Xml\Status;
use Gedmo\Tests\Tool\BaseTestCaseOM;

/**
 * These are mapping extension tests
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class LoggableMappingTest extends BaseTestCaseOM
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var \Gedmo\Loggable\LoggableListener
     */
    private $loggable;

    protected function setUp(): void
    {
        parent::setUp();

        $reader = new AnnotationReader();
        $annotationDriver = new AnnotationDriver($reader);

        $xmlDriver = new XmlDriver(__DIR__.'/../Driver/Xml');

        $chain = new MappingDriverChain();
        $chain->addDriver($annotationDriver, 'Gedmo\Loggable');
        $chain->addDriver($xmlDriver, 'Gedmo\Tests\Mapping\Fixture\Xml');

        $this->loggable = new LoggableListener();
        $this->evm = new EventManager();
        $this->evm->addEventSubscriber($this->loggable);

        $this->em = $this->getDefaultMockSqliteEntityManager([
            LogEntry::class,
            Loggable::class,
            LoggableWithEmbedded::class,
            Embedded::class,
            Status::class,
        ], $chain);
    }

    public function testLoggableMetadata(): void
    {
        $meta = $this->em->getClassMetadata(Loggable::class);
        $config = $this->loggable->getConfiguration($this->em, $meta->getName());

        static::assertArrayHasKey('logEntryClass', $config);
        static::assertSame(LogEntry::class, $config['logEntryClass']);
        static::assertArrayHasKey('loggable', $config);
        static::assertTrue($config['loggable']);

        static::assertArrayHasKey('versioned', $config);
        static::assertCount(2, $config['versioned']);
        static::assertContains('title', $config['versioned']);
        static::assertContains('status', $config['versioned']);
    }

    public function testLoggableMetadataWithEmbedded(): void
    {
        $meta = $this->em->getClassMetadata(LoggableWithEmbedded::class);
        $config = $this->loggable->getConfiguration($this->em, $meta->getName());

        static::assertArrayHasKey('logEntryClass', $config);
        static::assertSame(LogEntry::class, $config['logEntryClass']);
        static::assertArrayHasKey('loggable', $config);
        static::assertTrue($config['loggable']);

        static::assertArrayHasKey('versioned', $config);
        static::assertCount(3, $config['versioned']);
        static::assertContains('title', $config['versioned']);
        static::assertContains('status', $config['versioned']);
        static::assertContains('embedded', $config['versioned']);
    }
}
