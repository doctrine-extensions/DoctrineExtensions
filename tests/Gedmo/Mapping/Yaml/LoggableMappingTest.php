<?php

namespace Gedmo\Tests\Mapping\Yaml;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\EventManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\DriverChain;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
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

        $yamlDriver = new YamlDriver(__DIR__.'/../Driver/Yaml');

        $chain = new DriverChain();
        $chain->addDriver($annotationDriver, 'Gedmo\Loggable');
        $chain->addDriver($yamlDriver, 'Gedmo\Tests\Mapping\Fixture\Yaml');

        $this->loggable = new LoggableListener();
        $this->evm = new EventManager();
        $this->evm->addEventSubscriber($this->loggable);

        $this->em = $this->getMockSqliteEntityManager([
            'Gedmo\Loggable\Entity\LogEntry',
            'Gedmo\Tests\Mapping\Fixture\Yaml\LoggableWithEmbedded',
            'Gedmo\Tests\Mapping\Fixture\Yaml\Embedded',
        ], $chain);
    }

    public function testLoggableMetadataWithEmbedded()
    {
        $meta = $this->em->getClassMetadata('Gedmo\Tests\Mapping\Fixture\Yaml\LoggableWithEmbedded');
        $config = $this->loggable->getConfiguration($this->em, $meta->name);

        $this->assertArrayHasKey('logEntryClass', $config);
        $this->assertEquals('Gedmo\Loggable\Entity\LogEntry', $config['logEntryClass']);
        $this->assertArrayHasKey('loggable', $config);
        $this->assertTrue($config['loggable']);

        $this->assertArrayHasKey('versioned', $config);
        $this->assertCount(2, $config['versioned']);
        $this->assertContains('title', $config['versioned']);
        $this->assertContains('embedded.subtitle', $config['versioned']);
    }
}
