<?php

namespace Gedmo\Tests\Tool;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Platforms\MySqlPlatform;
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
use Gedmo\Tool\Logging\DBAL\QueryAnalyzer;
use Gedmo\Translatable\TranslatableListener;
use Gedmo\Tree\TreeListener;

/**
 * Base test case contains common mock objects
 * and functionality among all extensions using
 * ORM object manager
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
abstract class BaseTestCaseORM extends \PHPUnit\Framework\TestCase
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var QueryAnalyzer
     */
    protected $queryAnalyzer;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
    }

    /**
     * EntityManager mock object together with
     * annotation mapping driver and pdo_sqlite
     * database in memory
     *
     * @param EventManager $evm
     *
     * @return EntityManager
     */
    protected function getMockSqliteEntityManager(EventManager $evm = null, Configuration $config = null)
    {
        $conn = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        $config = null === $config ? $this->getMockAnnotatedConfig() : $config;
        $em = EntityManager::create($conn, $config, $evm ?: $this->getEventManager());

        $schema = array_map(static function ($class) use ($em) {
            return $em->getClassMetadata($class);
        }, (array) $this->getUsedEntityFixtures());

        $schemaTool = new SchemaTool($em);
        $schemaTool->dropSchema([]);
        $schemaTool->createSchema($schema);

        return $this->em = $em;
    }

    protected function getDefaultMockSqliteEntityManager(EventManager $evm = null): EntityManager
    {
        return $this->getMockSqliteEntityManager($evm, $this->getDefaultConfiguration());
    }

    private function getDefaultConfiguration(): Configuration
    {
        $config = new Configuration();
        $config->setProxyDir(TESTS_TEMP_DIR);
        $config->setProxyNamespace('Proxy');
        $config->setMetadataDriverImpl($this->getMetadataDefaultDriverImplementation());

        return $config;
    }

    private function getMetadataDefaultDriverImplementation(): MappingDriver
    {
        if (PHP_VERSION_ID >= 80000) {
            return new AttributeDriver([]);
        }

        return new AnnotationDriver($_ENV['annotation_reader']);
    }

    /**
     * EntityManager mock object together with
     * annotation mapping driver and custom
     * connection
     *
     * @param EventManager $evm
     *
     * @return EntityManager
     */
    protected function getMockCustomEntityManager(array $conn, EventManager $evm = null)
    {
        $config = $this->getMockAnnotatedConfig();
        $em = EntityManager::create($conn, $config, $evm ?: $this->getEventManager());

        $schema = array_map(static function ($class) use ($em) {
            return $em->getClassMetadata($class);
        }, (array) $this->getUsedEntityFixtures());

        $schemaTool = new SchemaTool($em);
        $schemaTool->dropSchema([]);
        $schemaTool->createSchema($schema);

        return $this->em = $em;
    }

    /**
     * EntityManager mock object with
     * annotation mapping driver
     *
     * @param EventManager $evm
     *
     * @return EntityManager
     */
    protected function getMockMappedEntityManager(EventManager $evm = null)
    {
        $driver = $this->getMockBuilder(Driver::class)->getMock();
        $driver->expects(static::once())
            ->method('getDatabasePlatform')
            ->willReturn($this->getMockBuilder(MySqlPlatform::class)->getMock());

        $conn = $this->getMockBuilder(Connection::class)
            ->setConstructorArgs([], $driver)
            ->getMock();

        $conn->expects(static::once())
            ->method('getEventManager')
            ->willReturn($evm ?: $this->getEventManager());

        $config = $this->getMockAnnotatedConfig();
        $this->em = EntityManager::create($conn, $config);

        return $this->em;
    }

    /**
     * Starts query statistic log
     *
     * @throws \RuntimeException
     */
    protected function startQueryLog()
    {
        if (!$this->em || !$this->em->getConnection()->getDatabasePlatform()) {
            throw new \RuntimeException('EntityManager and database platform must be initialized');
        }
        $this->queryAnalyzer = new QueryAnalyzer($this->em->getConnection()->getDatabasePlatform());
        $this->em->getConfiguration()->setSQLLogger($this->queryAnalyzer);
    }

    /**
     * Stops query statistic log and outputs
     * the data to screen or file
     *
     * @param bool $dumpOnlySql
     * @param bool $writeToLog
     *
     * @throws \RuntimeException
     */
    protected function stopQueryLog($dumpOnlySql = false, $writeToLog = false)
    {
        if ($this->queryAnalyzer) {
            $output = $this->queryAnalyzer->getOutput($dumpOnlySql);
            if ($writeToLog) {
                $fileName = TESTS_TEMP_DIR.'/query_debug_'.time().'.log';
                if (false !== ($file = fopen($fileName, 'w+'))) {
                    fwrite($file, $output);
                    fclose($file);
                } else {
                    throw new \RuntimeException('Unable to write to the log file');
                }
            } else {
                echo $output;
            }
        }
    }

    /**
     * Creates default mapping driver
     *
     * @return \Doctrine\ORM\Mapping\Driver\Driver
     */
    protected function getMetadataDriverImplementation()
    {
        return new AnnotationDriver($_ENV['annotation_reader']);
    }

    /**
     * Get a list of used fixture classes
     *
     * @return array
     */
    abstract protected function getUsedEntityFixtures();

    /**
     * Build event manager
     *
     * @return EventManager
     */
    private function getEventManager()
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

    /**
     * Get annotation mapping configuration
     *
     * @return \Doctrine\ORM\Configuration
     */
    protected function getMockAnnotatedConfig()
    {
        $config = new Configuration();
        $config->setProxyDir(TESTS_TEMP_DIR);
        $config->setProxyNamespace('Proxy');
        $config->setMetadataDriverImpl($this->getMetadataDriverImplementation());

        return $config;
    }
}
