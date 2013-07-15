<?php

namespace TestTool;

use TestTool\DbalQueryAnalyzer;

use Doctrine\Common\EventManager;

use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping\DefaultQuoteStrategy;
use Doctrine\ORM\Mapping\DefaultNamingStrategy;
use Doctrine\ORM\Repository\DefaultRepositoryFactory;
use Doctrine\DBAL\DriverManager;

use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver as MongoAnnationDriver;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\MongoDB\Connection;

use Gedmo\Translatable\TranslatableListener;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Tree\TreeListener;
use Gedmo\Timestampable\TimestampableListener;
use Gedmo\Loggable\LoggableListener;
use Gedmo\SoftDeleteable\SoftDeleteableListener;

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
    protected function createEntityManager(EventManager $evm = null, $conn = null, Configuration $config = null)
    {
        $conn = $conn ?: $this->getGlobalDbalConn(); // try a global connection first on fallback

        $em = EntityManager::create(
            $conn ?: array('driver' => 'pdo_sqlite', 'memory' => true), // fallback to sqlite if theres no global or dirrect
            $config ?: $this->getMockAnnotatedEntityConfig(),
            $evm ?: $this->getEventManager()
        );

        if ($fixtures = $this->getUsedEntityFixtures()) {
            $params = $em->getConnection()->getParams();
            $name = isset($params['path']) ? $params['path'] : (isset($params['dbname']) ? $params['dbname'] : false);
            unset($params['dbname']);
            if ($name) {
                $tmpConnection = DriverManager::getConnection($params);
                $tmpConnection->getSchemaManager()->createDatabase($name);
            }

            $schema = array_map(function($class) use ($em) {
                return $em->getClassMetadata($class);
            }, $fixtures);

            $schemaTool = new SchemaTool($em);
            $schemaTool->dropSchema(array());
            $schemaTool->createSchema($schema);
        }
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
    protected function createDocumentManager(EventManager $evm = null, Connection $conn = null, $config = null)
    {
        if (!class_exists('Mongo')) {
            $this->markTestSkipped('Missing PHP Mongo database extension.');
        }
        $dm = DocumentManager::create(
            $conn ?: new Connection,
            $config ?: $this->getMockAnnotatedMongoDocumentConfig(),
            $evm ?: $this->getEventManager()
        );
        $dm->getConnection()->connect();
        return $dm;
    }

    /**
     * Drops a database for EntityManager $em
     *
     * @param EntityManager $em
     */
    protected function releaseEntityManager(EntityManager $em)
    {
        if ($dbname = $em->getConnection()->getDatabase()) {
            try {
                $em->getConnection()->getSchemaManager()->dropDatabase($dbname);
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
        $analyzer = new QueryAnalyzer($platform);
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
     * Get a list of used fixture classes,
     * if empty, schema will not be built
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
        return realpath(__DIR__ . '/../temp');
    }

    /**
     * Get root project directory
     *
     * @return string
     */
    protected function getRootDir()
    {
        return realpath(__DIR__ . '/../..');
    }

    /**
     * Get annotation mapping configuration
     *
     * @return Doctrine\ORM\Configuration
     */
    private function getMockAnnotatedEntityConfig()
    {
        // We need to mock every method except the ones which
        // handle the filters
        $configurationClass = 'Doctrine\ORM\Configuration';
        $refl = new \ReflectionClass($configurationClass);
        $methods = $refl->getMethods();

        $mockMethods = array();

        foreach ($methods as $method) {
            if ($method->name !== 'addFilter' && $method->name !== 'getFilterClassName') {
                $mockMethods[] = $method->name;
            }
        }

        $config = $this->getMock($configurationClass, $mockMethods);

        $config
            ->expects($this->once())
            ->method('getProxyDir')
            ->will($this->returnValue($this->getTempDir()));

        $config
            ->expects($this->once())
            ->method('getProxyNamespace')
            ->will($this->returnValue('Proxy'));

        $config
            ->expects($this->once())
            ->method('getAutoGenerateProxyClasses')
            ->will($this->returnValue(true));

        $config
            ->expects($this->once())
            ->method('getClassMetadataFactoryName')
            ->will($this->returnValue('Doctrine\\ORM\\Mapping\\ClassMetadataFactory'));

        $config
            ->expects($this->any())
            ->method('getMetadataDriverImpl')
            ->will($this->returnValue(new AnnotationDriver($_ENV['annotation_reader'])));

        $config
            ->expects($this->any())
            ->method('getDefaultRepositoryClassName')
            ->will($this->returnValue('Doctrine\\ORM\\EntityRepository'));

        $config
            ->expects($this->any())
            ->method('getQuoteStrategy')
            ->will($this->returnValue(new DefaultQuoteStrategy));

        $config
            ->expects($this->any())
            ->method('getNamingStrategy')
            ->will($this->returnValue(new DefaultNamingStrategy));

        $config
            ->expects($this->once())
            ->method('getRepositoryFactory')
            ->will($this->returnValue(new DefaultRepositoryFactory));

        return $config;
    }

    /**
     * Get annotation mapping configuration
     *
     * @return Doctrine\ODM\MongoDB\Configuration
     */
    private function getMockAnnotatedMongoDocumentConfig()
    {
        $config = $this->getMock('Doctrine\\ODM\\MongoDB\\Configuration');

        $config->expects($this->any())
            ->method('getFilterClassName')
            ->will($this->returnValue('Gedmo\\SoftDeleteable\\Filter\\ODM\\SoftDeleteableFilter'));

        $config->expects($this->once())
            ->method('getProxyDir')
            ->will($this->returnValue($this->getTempDir()));

        $config->expects($this->once())
            ->method('getProxyNamespace')
            ->will($this->returnValue('Proxy'));

        $config->expects($this->once())
            ->method('getHydratorDir')
            ->will($this->returnValue($this->getTempDir()));

        $config->expects($this->once())
            ->method('getHydratorNamespace')
            ->will($this->returnValue('Hydrator'));

        $config->expects($this->any())
            ->method('getDefaultDB')
            ->will($this->returnValue('gedmo_extensions_test'));

        $config->expects($this->once())
            ->method('getAutoGenerateProxyClasses')
            ->will($this->returnValue(true));

        $config->expects($this->once())
            ->method('getAutoGenerateHydratorClasses')
            ->will($this->returnValue(true));

        $config->expects($this->once())
            ->method('getClassMetadataFactoryName')
            ->will($this->returnValue('Doctrine\\ODM\\MongoDB\\Mapping\\ClassMetadataFactory'));

        $config->expects($this->any())
            ->method('getMongoCmd')
            ->will($this->returnValue('$'));

        $config->expects($this->any())
            ->method('getDefaultCommitOptions')
            ->will($this->returnValue(array('safe' => true)));

        $config->expects($this->any())
            ->method('getMetadataDriverImpl')
            ->will($this->returnValue(new MongoAnnotationDriver($_ENV['annotation_reader'])));

        return $config;
    }

    /**
     * If db_inc_file is defined in phpunit global variable
     * it returns a result of it. Can be either dbal connection instance
     * or an array of connection parameters
     *
     * @return mixed - null if not configured to use specific database, or a connection value or parameters
     */
    private function getGlobalDbalConn()
    {
        if (isset($GLOBALS['db_inc_file'])) {
            return include $GLOBALS['db_inc_file'];
        }
        return null;
    }
}
