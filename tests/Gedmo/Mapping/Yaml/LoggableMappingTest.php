<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping\Yaml;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Gedmo\Loggable\Entity\LogEntry;
use Gedmo\Loggable\LoggableListener;
use Gedmo\Tests\Mapping\Fixture\Yaml\Embedded;
use Gedmo\Tests\Mapping\Fixture\Yaml\LoggableComposite;
use Gedmo\Tests\Mapping\Fixture\Yaml\LoggableCompositeRelation;
use Gedmo\Tests\Mapping\Fixture\Yaml\LoggableWithEmbedded;
use Gedmo\Tests\Tool\BaseTestCaseOM;

/**
 * These are mapping extension tests
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class LoggableMappingTest extends BaseTestCaseOM
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var LoggableListener
     *
     * @phpstan-var LoggableListener<LoggableWithEmbedded>
     */
    private $loggable;

    protected function setUp(): void
    {
        parent::setUp();

        $reader = new AnnotationReader();
        $annotationDriver = new AnnotationDriver($reader);

        $yamlDriver = new YamlDriver(__DIR__.'/../Driver/Yaml');

        $chain = new MappingDriverChain();
        $chain->addDriver($annotationDriver, 'Gedmo\Loggable');
        $chain->addDriver($yamlDriver, 'Gedmo\Tests\Mapping\Fixture\Yaml');

        $this->loggable = new LoggableListener();
        $this->evm = new EventManager();
        $this->evm->addEventSubscriber($this->loggable);

        $this->em = $this->getDefaultMockSqliteEntityManager([
            LogEntry::class,
            LoggableWithEmbedded::class,
            Embedded::class,
        ], $chain);
    }

    public function testLoggableCompositeMetadata(): void
    {
        $meta = $this->em->getClassMetadata(LoggableComposite::class);
        $config = $this->loggable->getConfiguration($this->em, $meta->name);

        static::assertArrayHasKey('logEntryClass', $config);
        static::assertSame(LogEntry::class, $config['logEntryClass']);
        static::assertArrayHasKey('loggable', $config);
        static::assertTrue($config['loggable']);

        static::assertArrayHasKey('versioned', $config);
        static::assertCount(1, $config['versioned']);
        static::assertContains('title', $config['versioned']);
    }

    public function testLoggableCompositeRelationMetadata(): void
    {
        $meta = $this->em->getClassMetadata(LoggableCompositeRelation::class);
        $config = $this->loggable->getConfiguration($this->em, $meta->name);

        static::assertArrayHasKey('logEntryClass', $config);
        static::assertSame(LogEntry::class, $config['logEntryClass']);
        static::assertArrayHasKey('loggable', $config);
        static::assertTrue($config['loggable']);

        static::assertArrayHasKey('versioned', $config);
        static::assertCount(1, $config['versioned']);
        static::assertContains('title', $config['versioned']);
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
        static::assertCount(2, $config['versioned']);
        static::assertContains('title', $config['versioned']);
        static::assertContains('embedded.subtitle', $config['versioned']);
    }
}
