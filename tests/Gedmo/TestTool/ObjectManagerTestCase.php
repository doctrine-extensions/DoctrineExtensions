<?php

namespace Gedmo\TestTool;

use Doctrine\Common\EventManager;

use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Configuration as EntityManagerConfiguration;
use Doctrine\ORM\Mapping\DefaultQuoteStrategy;
use Doctrine\ORM\Mapping\DefaultNamingStrategy;
use Doctrine\ORM\Repository\DefaultRepositoryFactory;
use Doctrine\DBAL\DriverManager;

use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver as MongoAnnotationDriver;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Configuration as MongoDBDocumentManagerConfiguration;
use Doctrine\MongoDB\Connection;

use Gedmo\Translatable\TranslatableListener;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Tree\TreeListener;
use Gedmo\Timestampable\TimestampableListener;
use Gedmo\Loggable\LoggableListener;
use Gedmo\SoftDeleteable\SoftDeleteableListener;
use Doctrine\DBAL\DBALException;

/**
 * Base test case contains common mock objects
 * and functionality among all extensions.
 *
 * Supports inititialization of all supported object managers
 * to ease the tests
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
abstract class ObjectManagerTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * Get EntityManager instance for tests
     *
     * @param EventManager $evm - defaults to event manager hooked with all extensions
     * @param array $conn - connection parameters, defaults to memory sqlite
     * @param array $config - ORM configuration, defaults to annotation driver based mock
     * @return EntityManager
     */
    protected function createEntityManager(EventManager $evm = null, $conn = null, EntityManagerConfiguration $config = null)
    {
        $em = EntityManager::create(
            $conn ?: $this->getDefaultDbalConnectionParams(),
            $config ?: $this->getEntityManagerConfiguration(),
            $evm ?: $this->getEventManager()
        );

        return $em;
    }

    /**
     * DocumentManager mock object together with
     * annotation mapping driver and database
     *
     * @param EventManager $evm - defaults to event manager hooked with all extensions
     * @param \Doctrine\MongoDB\Connection $conn - mongodb connection
     * @param array $config - ODM mongodb configuration, defaults to annotation driver based mock
     * @return DocumentManager
     */
    protected function createDocumentManager(EventManager $evm = null, Connection $conn = null, MongoDBDocumentManagerConfiguration $config = null)
    {
        if (!class_exists('Mongo')) {
            $this->markTestSkipped('Missing PHP Mongo database extension.');
        }
        $dm = DocumentManager::create(
            $conn ?: new Connection,
            $config ?: $this->getMongoDBDocumentManagerConfiguration(),
            $evm ?: $this->getEventManager()
        );
        $dm->getConnection()->connect();
        return $dm;
    }

    /**
     * Creates database schema for EntityManager $em
     *
     * @param EntityManager $em
     * @param array $entityClassNames
     */
    protected function createSchema(EntityManager $em, array $entityClassNames)
    {
        $params = $em->getConnection()->getParams();
        $name = isset($params['path']) ? $params['path'] : (isset($params['dbname']) ? $params['dbname'] : false);
        unset($params['dbname']);
        if ($name) {
            $tmpConnection = DriverManager::getConnection($params);
            $tmpConnection->getSchemaManager()->createDatabase($name);
        }

        $schema = array_map(function($class) use ($em) {
            return $em->getClassMetadata($class);
        }, $entityClassNames);

        $schemaTool = new SchemaTool($em);
        $schemaTool->dropSchema(array());
        $schemaTool->createSchema($schema);
    }

    /**
     * Drops a database for EntityManager $em
     *
     * @param EntityManager $em
     */
    protected function releaseEntityManager(EntityManager $em)
    {
        $conn = $em->getConnection();
        if ($dbname = $conn->getDatabase()) {
            try {
                if ($conn->getDatabasePlatform()->getName() === 'postgresql') {
                    $params = $conn->getParams();
                    $params["dbname"] = "postgres";
                    $conn->close();
                    $conn = DriverManager::getConnection($params);
                }
                $conn->getSchemaManager()->dropDatabase($dbname);
                $conn->close();
            } catch(DBALException $e) {
                // none
            } catch(\PDOException $e) {
                // none
            }
        }
    }

    /**
     * Drops all database collections for DocumentManager $dm
     *
     * @param DocumentManager $dm
     */
    protected function releaseDocumentManager(DocumentManager $dm)
    {
        foreach ($dm->getDocumentDatabases() as $db) {
            foreach ($db->listCollections() as $collection) {
                $collection->drop();
            }
        }
        $dm->getConnection()->close();
    }

    /**
     * Starts query statistic log and returns analyzer instance
     *
     * @throws \RuntimeException
     * @param EntityManager $em
     * @return DbalQueryAnalyzer
     */
    protected function startQueryLog(EntityManager $em)
    {
        if (!$platform = $em->getConnection()->getDatabasePlatform()) {
            throw new \RuntimeException('EntityManager and database platform must be initialized');
        }
        $analyzer = new DbalQueryAnalyzer($platform);
        $conf = $em->getConfiguration();
        if ($conf instanceof \PHPUnit_Framework_MockObject_MockObject) {
            $conf
                ->expects($this->any())
                ->method('getSQLLogger')
                ->will($this->returnValue($analyzer));
        } else {
            $conf->setSQLLogger($analyzer);
        }
        return $analyzer;
    }

    /**
     * Build event manager
     *
     * @return EventManager
     */
    private function getEventManager()
    {
        $evm = new EventManager;
        $evm->addEventSubscriber(new TreeListener);
        $evm->addEventSubscriber(new SluggableListener);
        $evm->addEventSubscriber(new LoggableListener);
        $evm->addEventSubscriber(new TranslatableListener);
        $evm->addEventSubscriber(new TimestampableListener);
        $evm->addEventSubscriber(new SoftDeleteableListener);
        return $evm;
    }

    /**
     * Get temporary directory used for any caches
     * proxies or other temp files for tests
     *
     * @return string
     */
    protected function getTempDir()
    {
        return realpath(__DIR__ . '/../../temp');
    }

    /**
     * Get root project directory
     *
     * @return string
     */
    protected function getRootDir()
    {
        return realpath(__DIR__ . '/../../..');
    }

    /**
     * Get tests directory
     *
     * @return string
     */
    protected function getTestsDir()
    {
        return realpath($this->getRootDir() . '/tests');
    }

    /**
     * Get annotation mapping configuration
     *
     * @return Doctrine\ORM\Configuration
     */
    protected function getEntityManagerConfiguration()
    {
        $config = new EntityManagerConfiguration;
        $config->setProxyDir($this->getTempDir());
        $config->setProxyNamespace('ProxyORM');
        $config->setAutoGenerateProxyClasses(true);
        $config->setMetadataDriverImpl(new AnnotationDriver($_ENV['annotation_reader']));

        return $config;
    }

    /**
     * Get annotation mapping configuration
     *
     * @return Doctrine\ODM\MongoDB\Configuration
     */
    protected function getMongoDBDocumentManagerConfiguration()
    {
        $config = new MongoDBDocumentManagerConfiguration;
        $config->setProxyDir($this->getTempDir());
        $config->setProxyNamespace('ProxyDM');
        $config->setHydratorDir($this->getTempDir());
        $config->setHydratorNamespace('HydratorDM');
        $config->setDefaultDB('gedmo_extensions_test');
        $config->setAutoGenerateProxyClasses(true);
        $config->setAutoGenerateHydratorClasses(true);
        $config->setMetadataDriverImpl(new MongoAnnotationDriver($_ENV['annotation_reader']));

        return $config;
    }

    /**
     * If custom phpunit global database variables are defined
     * it returns array of connection parameters. Otherwise returns
     * sqlite memory
     *
     * @return mixed - null if not configured to use specific database, or a connection value or parameters
     */
    protected function getDefaultDbalConnectionParams()
    {
        $useGlobal = isset($GLOBALS['db_type'], $GLOBALS['db_username'], $GLOBALS['db_password']);
        $useGlobal = $useGlobal && isset($GLOBALS['db_host'], $GLOBALS['db_name'], $GLOBALS['db_port']);
        if ($useGlobal) {
            return array(
                'driver' => $GLOBALS['db_type'],
                'user' => $GLOBALS['db_username'],
                'password' => $GLOBALS['db_password'],
                'host' => $GLOBALS['db_host'],
                'dbname' => $GLOBALS['db_name'],
                'port' => $GLOBALS['db_port']
            );
        }
        return array('driver' => 'pdo_sqlite', 'memory' => true);
    }
}
