<?php

namespace Gedmo\Tests\Mapping\Xml;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\DriverChain;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
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
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class LoggableMappingTest extends BaseTestCaseOM
{
    /**
     * @var Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var Gedmo\Loggable\LoggableListener
     */
    private $loggable;

    protected function setUp(): void
    {
        parent::setUp();

        $reader = new AnnotationReader();
        $annotationDriver = new AnnotationDriver($reader);

        $xmlDriver = new XmlDriver(__DIR__.'/../Driver/Xml');

        $chain = new DriverChain();
        $chain->addDriver($annotationDriver, 'Gedmo\Loggable');
        $chain->addDriver($xmlDriver, 'Gedmo\Tests\Mapping\Fixture\Xml');

        $this->loggable = new LoggableListener();
        $this->evm = new EventManager();
        $this->evm->addEventSubscriber($this->loggable);

        $this->em = $this->getMockSqliteEntityManager([
            LogEntry::class,
            Loggable::class,
            LoggableWithEmbedded::class,
            Embedded::class,
            Status::class,
        ], $chain);
    }

    public function testLoggableMetadata()
    {
        $meta = $this->em->getClassMetadata(Loggable::class);
        $config = $this->loggable->getConfiguration($this->em, $meta->name);

        static::assertArrayHasKey('logEntryClass', $config);
        static::assertSame(LogEntry::class, $config['logEntryClass']);
        static::assertArrayHasKey('loggable', $config);
        static::assertTrue($config['loggable']);

        static::assertArrayHasKey('versioned', $config);
        static::assertCount(2, $config['versioned']);
        static::assertContains('title', $config['versioned']);
        static::assertContains('status', $config['versioned']);
    }

    public function testLoggableMetadataWithEmbedded()
    {
        $meta = $this->em->getClassMetadata(LoggableWithEmbedded::class);
        $config = $this->loggable->getConfiguration($this->em, $meta->name);

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
