<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Tool;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Logging\Middleware;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Gedmo\Loggable\LoggableListener;
use Gedmo\Revisionable\RevisionableListener;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\SoftDeleteable\SoftDeleteableListener;
use Gedmo\Timestampable\TimestampableListener;
use Gedmo\Translatable\TranslatableListener;
use Gedmo\Tree\TreeListener;
use PHPUnit\Framework\TestCase;

/**
 * Base test case contains common mock objects
 * and functionality among all extensions using
 * ORM object manager
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
abstract class BaseTestCaseORM extends TestCase
{
    protected ?EntityManager $em = null;

    protected QueryLogger $queryLogger;

    protected function setUp(): void
    {
        $this->queryLogger = new QueryLogger();
    }

    /**
     * EntityManager mock object together with
     * annotation mapping driver and pdo_sqlite
     * database in memory
     */
    protected function getDefaultMockSqliteEntityManager(?EventManager $evm = null, ?Configuration $config = null): EntityManager
    {
        $conn = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        $config ??= $this->getDefaultConfiguration();
        $connection = DriverManager::getConnection($conn, $config);
        $em = new EntityManager($connection, $config, $evm ?? $this->getEventManager());

        $schema = array_map(static fn (string $class): ClassMetadata => $em->getClassMetadata($class), $this->getUsedEntityFixtures());

        $schemaTool = new SchemaTool($em);
        $schemaTool->dropSchema([]);
        $schemaTool->createSchema($schema);

        return $this->em = $em;
    }

    /**
     * Creates default mapping driver
     */
    protected function getMetadataDriverImplementation(): MappingDriver
    {
        if (PHP_VERSION_ID >= 80000) {
            return new AttributeDriver([]);
        }

        return new AnnotationDriver($_ENV['annotation_reader']);
    }

    /**
     * Get a list of used fixture classes
     *
     * @return array<int, string>
     *
     * @phpstan-return list<class-string>
     */
    abstract protected function getUsedEntityFixtures(): array;

    protected function getDefaultConfiguration(): Configuration
    {
        $config = new Configuration();
        $config->setProxyDir(TESTS_TEMP_DIR);
        $config->setProxyNamespace('Proxy');
        $config->setMetadataDriverImpl($this->getMetadataDriverImplementation());
        $config->setMiddlewares([
            new Middleware($this->queryLogger),
        ]);

        return $config;
    }

    /**
     * Build event manager
     */
    private function getEventManager(): EventManager
    {
        $evm = new EventManager();
        $evm->addEventSubscriber(new TreeListener());
        $evm->addEventSubscriber(new SluggableListener());
        $evm->addEventSubscriber(new LoggableListener());
        $evm->addEventSubscriber(new RevisionableListener());
        $evm->addEventSubscriber(new TranslatableListener());
        $evm->addEventSubscriber(new TimestampableListener());
        $evm->addEventSubscriber(new SoftDeleteableListener());

        return $evm;
    }
}
