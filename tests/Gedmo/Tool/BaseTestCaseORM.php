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
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Logging\Middleware;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Gedmo\Loggable\LoggableListener;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\SoftDeleteable\SoftDeleteableListener;
use Gedmo\Timestampable\TimestampableListener;
use Gedmo\Translatable\TranslatableListener;
use Gedmo\Tree\TreeListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Base test case contains common mock objects
 * and functionality among all extensions using
 * ORM object manager
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
abstract class BaseTestCaseORM extends TestCase
{
    /**
     * @var EntityManager|null
     */
    protected $em;

    /**
     * @var QueryAnalyzer
     */
    protected $queryAnalyzer;

    /**
     * @var MockObject&LoggerInterface
     */
    protected $queryLogger;

    protected function setUp(): void
    {
        $this->queryLogger = $this->createMock(LoggerInterface::class);
    }

    /**
     * EntityManager mock object together with
     * annotation mapping driver and pdo_sqlite
     * database in memory
     */
    protected function getDefaultMockSqliteEntityManager(EventManager $evm = null, Configuration $config = null): EntityManager
    {
        $conn = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        $config = null === $config ? $this->getDefaultConfiguration() : $config;
        $em = EntityManager::create($conn, $config, $evm ?: $this->getEventManager());

        $schema = array_map(static function ($class) use ($em) {
            return $em->getClassMetadata($class);
        }, $this->getUsedEntityFixtures());

        $schemaTool = new SchemaTool($em);
        $schemaTool->dropSchema([]);
        $schemaTool->createSchema($schema);

        return $this->em = $em;
    }

    /**
     * TODO: Remove this method when dropping support of doctrine/dbal 2.
     *
     * Starts query statistic log
     *
     * @throws \RuntimeException
     */
    protected function startQueryLog(): void
    {
        if (null === $this->em) {
            throw new \RuntimeException('EntityManager must be initialized.');
        }
        $this->queryAnalyzer = new QueryAnalyzer($this->em->getConnection()->getDatabasePlatform());
        $this->em->getConfiguration()->setSQLLogger($this->queryAnalyzer);
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
     * @phpstan-return list<class-string>
     */
    abstract protected function getUsedEntityFixtures(): array;

    protected function getDefaultConfiguration(): Configuration
    {
        $config = new Configuration();
        $config->setProxyDir(TESTS_TEMP_DIR);
        $config->setProxyNamespace('Proxy');
        $config->setMetadataDriverImpl($this->getMetadataDriverImplementation());

        // TODO: Remove the "if" check when dropping support of doctrine/dbal 2.
        if (class_exists(Middleware::class)) {
            $config->setMiddlewares([
                new Middleware($this->queryLogger),
            ]);
        }

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
        $evm->addEventSubscriber(new TranslatableListener());
        $evm->addEventSubscriber(new TimestampableListener());
        $evm->addEventSubscriber(new SoftDeleteableListener());

        return $evm;
    }
}
