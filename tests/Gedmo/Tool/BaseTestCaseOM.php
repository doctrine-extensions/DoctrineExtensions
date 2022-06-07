<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Tool;

// common
use Doctrine\Common\EventManager;
// orm specific
use Doctrine\DBAL\Driver;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver as AnnotationDriverODM;
// odm specific
use Doctrine\ODM\MongoDB\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
// listeners
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\DefaultNamingStrategy;
use Doctrine\ORM\Mapping\DefaultQuoteStrategy;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver as AnnotationDriverORM;
use Doctrine\ORM\Repository\DefaultRepositoryFactory as DefaultRepositoryFactoryORM;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Gedmo\Loggable\LoggableListener;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\SoftDeleteable\Filter\ODM\SoftDeleteableFilter;
use Gedmo\Timestampable\TimestampableListener;
use Gedmo\Translatable\TranslatableListener;
use Gedmo\Tree\TreeListener;
use MongoDB\Client;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * Base test case contains common mock objects
 * generation methods for multi object manager
 * test cases
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
abstract class BaseTestCaseOM extends \PHPUnit\Framework\TestCase
{
    /**
     * @var EventManager
     */
    protected $evm;

    /**
     * Initialized document managers
     *
     * @var DocumentManager[]
     */
    private $dms = [];

    protected function setUp(): void
    {
    }

    protected function tearDown(): void
    {
        foreach ($this->dms as $documentManager) {
            foreach ($documentManager->getDocumentDatabases() as $documentDatabase) {
                $documentDatabase->drop();
            }
        }
    }

    public function getMongoDBDriver(array $paths = []): MappingDriver
    {
        if (PHP_VERSION_ID >= 80000 && class_exists(AttributeDriver::class)) {
            return new AttributeDriver($paths);
        }

        return new AnnotationDriverODM($_ENV['annotation_reader'], $paths);
    }

    public function getORMDriver(array $paths = []): MappingDriver
    {
        if (PHP_VERSION_ID >= 80000) {
            return new \Doctrine\ORM\Mapping\Driver\AttributeDriver($paths);
        }

        return new AnnotationDriverORM($_ENV['annotation_reader'], $paths);
    }

    /**
     * DocumentManager mock object together with
     * annotation mapping driver and database
     */
    protected function getMockDocumentManager(string $dbName, MappingDriver $mappingDriver = null): DocumentManager
    {
        if (!extension_loaded('mongodb')) {
            static::markTestSkipped('Missing Mongo extension.');
        }

        $client = new Client($_ENV['MONGODB_SERVER'], [], ['typeMap' => DocumentManager::CLIENT_TYPEMAP]);
        $config = $this->getMockODMMongoDBConfig($dbName, $mappingDriver);

        return DocumentManager::create($client, $config, $this->getEventManager());
    }

    /**
     * EntityManager mock object together with
     * annotation mapping driver and pdo_sqlite
     * database in memory
     */
    protected function getDefaultMockSqliteEntityManager(array $fixtures, MappingDriver $mappingDriver = null): EntityManager
    {
        $conn = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        $config = $this->getMockORMConfig($mappingDriver);
        $em = EntityManager::create($conn, $config, $this->getEventManager());

        $schema = array_map(static function (string $class) use ($em) {
            return $em->getClassMetadata($class);
        }, $fixtures);

        $schemaTool = new SchemaTool($em);
        $schemaTool->dropSchema([]);
        $schemaTool->createSchema($schema);

        return $em;
    }

    /**
     * Build event manager
     */
    private function getEventManager(): EventManager
    {
        if (null === $this->evm) {
            $this->evm = new EventManager();
            $this->evm->addEventSubscriber(new TreeListener());
            $this->evm->addEventSubscriber(new SluggableListener());
            $this->evm->addEventSubscriber(new LoggableListener());
            $this->evm->addEventSubscriber(new TranslatableListener());
            $this->evm->addEventSubscriber(new TimestampableListener());
        }

        return $this->evm;
    }

    /**
     * Get annotation mapping configuration
     */
    private function getMockODMMongoDBConfig(string $dbName, MappingDriver $mappingDriver = null): Configuration
    {
        if (null === $mappingDriver) {
            $mappingDriver = $this->getMongoDBDriver();
        }
        $config = new Configuration();
        $config->addFilter('softdeleteable', SoftDeleteableFilter::class);
        $config->setProxyDir(TESTS_TEMP_DIR);
        $config->setHydratorDir(TESTS_TEMP_DIR);
        $config->setProxyNamespace('Proxy');
        $config->setHydratorNamespace('Hydrator');
        $config->setDefaultDB('gedmo_extensions_test');
        $config->setAutoGenerateProxyClasses(Configuration::AUTOGENERATE_EVAL);
        $config->setAutoGenerateHydratorClasses(Configuration::AUTOGENERATE_EVAL);
        $config->setMetadataDriverImpl($mappingDriver);
        $config->setMetadataCache(new ArrayAdapter());

        return $config;
    }

    /**
     * Get annotation mapping configuration for ORM
     */
    private function getMockORMConfig(MappingDriver $mappingDriver = null): \Doctrine\ORM\Configuration
    {
        $config = new \Doctrine\ORM\Configuration();
        $config->setProxyDir(TESTS_TEMP_DIR);
        $config->setProxyNamespace('Proxy');
        $config->setDefaultQueryHints([]);
        $config->setAutoGenerateProxyClasses(true);
        $config->setClassMetadataFactoryName(ClassMetadataFactory::class);
        $config->setDefaultRepositoryClassName(EntityRepository::class);
        $config->setQuoteStrategy(new DefaultQuoteStrategy());
        $config->setNamingStrategy(new DefaultNamingStrategy());
        $config->setMetadataDriverImpl($mappingDriver ?? $this->getORMDriver());
        $config->setRepositoryFactory(new DefaultRepositoryFactoryORM());
        $config->setMetadataCache(new ArrayAdapter());

        return $config;
    }
}
