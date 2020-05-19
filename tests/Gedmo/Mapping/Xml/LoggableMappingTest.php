<?php

namespace Gedmo\Mapping\Xml;

use Doctrine\Common\EventManager;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\DriverChain;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Gedmo\Loggable\LoggableListener;
use Tool\BaseTestCaseOM;

/**
 * These are mapping extension tests
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class LoggableMappingTest extends BaseTestCaseOM
{
    const COMPOSITE = 'Mapping\\Fixture\\Xml\\LoggableComposite';
    const COMPOSITE_RELATION = 'Mapping\\Fixture\\Xml\\LoggableCompositeRelation';

    /**
     * @var Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var Gedmo\Loggable\LoggableListener
     */
    private $loggable;

    public function setUp()
    {
        parent::setUp();

        $reader = new AnnotationReader();
        $annotationDriver = new AnnotationDriver($reader);

        $xmlDriver = new XmlDriver(__DIR__.'/../Driver/Xml');

        $chain = new DriverChain();
        $chain->addDriver($annotationDriver, 'Gedmo\Loggable');
        $chain->addDriver($xmlDriver, 'Mapping\Fixture\Xml');

        $this->loggable = new LoggableListener();
        $this->evm = new EventManager();
        $this->evm->addEventSubscriber($this->loggable);

        $this->em = $this->getMockSqliteEntityManager(array(
            'Gedmo\Loggable\Entity\LogEntry',
            'Mapping\Fixture\Xml\Loggable',
            'Mapping\Fixture\Xml\LoggableWithEmbedded',
            'Mapping\Fixture\Xml\Embedded',
            'Mapping\Fixture\Xml\Status',
        ), $chain);
    }

    public function testLoggableMetadata()
    {
        $meta = $this->em->getClassMetadata('Mapping\Fixture\Xml\Loggable');
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

    public function testLoggableCompositeMetadata()
    {
        $meta = $this->em->getClassMetadata(self::COMPOSITE);
        $config = $this->loggable->getConfiguration($this->em, $meta->name);

        $this->assertArrayHasKey('logEntryClass', $config);
        $this->assertEquals('Gedmo\Loggable\Entity\LogEntry', $config['logEntryClass']);
        $this->assertArrayHasKey('loggable', $config);
        $this->assertTrue($config['loggable']);

        $this->assertArrayHasKey('versioned', $config);
        $this->assertCount(1, $config['versioned']);
        $this->assertContains('title', $config['versioned']);
    }

    /**
     * @expectedException \Gedmo\Exception\InvalidMappingException
     * @expectedExceptionMessage Loggable does not support composite foreign identifiers with ORM < 2.6
     */
    public function testORMBelow26ThrowsExceptionWithLoggableCompositeRelationMapping()
    {
        if (1 > \Doctrine\ORM\Version::compare('2.6.0')) {
            $this->markTestSkipped('ORM < 2.6 version required for this test.');
        }
        $this->em->getClassMetadata(self::COMPOSITE_RELATION);
    }

    public function testLoggableCompositeRelationMetadata()
    {
        if (1 === \Doctrine\ORM\Version::compare('2.6.0')) {
            $this->markTestSkipped('ORM >= 2.6 version required for this test.');
        }
        $meta = $this->em->getClassMetadata(self::COMPOSITE_RELATION);
        $config = $this->loggable->getConfiguration($this->em, $meta->name);

        $this->assertArrayHasKey('logEntryClass', $config);
        $this->assertEquals('Gedmo\Loggable\Entity\LogEntry', $config['logEntryClass']);
        $this->assertArrayHasKey('loggable', $config);
        $this->assertTrue($config['loggable']);

        $this->assertArrayHasKey('versioned', $config);
        $this->assertCount(1, $config['versioned']);
        $this->assertContains('title', $config['versioned']);
    }

    public function testLoggableMetadataWithEmbedded()
    {
        $meta = $this->em->getClassMetadata('Mapping\Fixture\Xml\LoggableWithEmbedded');
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
