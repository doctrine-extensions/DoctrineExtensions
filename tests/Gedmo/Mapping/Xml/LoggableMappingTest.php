<?php

namespace Gedmo\Tests\Mapping\Xml;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\DriverChain;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Gedmo\Loggable\LoggableListener;
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
class LoggableMappingTest extends BaseTestCaseOM
{
    /**
     * @var Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var Gedmo\Loggable\LoggableListener
     */
    private $loggable;

    public function setUp(): void
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
            'Gedmo\Loggable\Entity\LogEntry',
            'Gedmo\Tests\Mapping\Fixture\Xml\Loggable',
            'Gedmo\Tests\Mapping\Fixture\Xml\LoggableWithEmbedded',
            'Gedmo\Tests\Mapping\Fixture\Xml\Embedded',
            'Gedmo\Tests\Mapping\Fixture\Xml\Status',
        ], $chain);
    }

    public function testLoggableMetadata()
    {
        $meta = $this->em->getClassMetadata('Gedmo\Tests\Mapping\Fixture\Xml\Loggable');
        $config = $this->loggable->getConfiguration($this->em, $meta->name);

        $this->assertArrayHasKey('logEntryClass', $config);
        $this->assertEquals('Gedmo\Loggable\Entity\LogEntry', $config['logEntryClass']);
        $this->assertArrayHasKey('loggable', $config);
        $this->assertTrue($config['loggable']);

        $this->assertArrayHasKey('versioned', $config);
        $this->assertCount(2, $config['versioned']);
        $this->assertContains('title', $config['versioned']);
        $this->assertContains('status', $config['versioned']);
    }

    public function testLoggableMetadataWithEmbedded()
    {
        $meta = $this->em->getClassMetadata('Gedmo\Tests\Mapping\Fixture\Xml\LoggableWithEmbedded');
        $config = $this->loggable->getConfiguration($this->em, $meta->name);

        $this->assertArrayHasKey('logEntryClass', $config);
        $this->assertEquals('Gedmo\Loggable\Entity\LogEntry', $config['logEntryClass']);
        $this->assertArrayHasKey('loggable', $config);
        $this->assertTrue($config['loggable']);

        $this->assertArrayHasKey('versioned', $config);
        $this->assertCount(3, $config['versioned']);
        $this->assertContains('title', $config['versioned']);
        $this->assertContains('status', $config['versioned']);
        $this->assertContains('embedded', $config['versioned']);
    }
}
