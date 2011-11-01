<?php

namespace Tool;

// common
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\EventManager;
// orm specific
use Doctrine\ORM\Mapping\Driver\Driver as MappingDriverORM;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver as AnnotationDriverORM;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
// odm specific
use Doctrine\ODM\MongoDB\Mapping\Driver\Driver as MappingDriverODM;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver as AnnotationDriverODM;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\MongoDB\Connection;
// listeners
use Gedmo\Translatable\TranslationListener;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Tree\TreeListener;
use Gedmo\Timestampable\TimestampableListener;
use Gedmo\Loggable\LoggableListener;

/**
 * Base test case contains common mock objects
 * generation methods for multi object manager
 * test cases
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo
 * @subpackage BaseTestCase
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
abstract class BaseTestCaseOM extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EventManager
     */
    protected $evm;

    /**
     * Initialized document managers
     *
     * @var array
     */
    private $dms = array();

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {

    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        foreach ($this->dms as $dm) {
            if ($dm) {
                foreach ($dm->getDocumentDatabases() as $db) {
                    foreach ($db->listCollections() as $collection) {
                        $collection->drop();
                    }
                }
                $dm->getConnection()->close();
                $dm = null;
            }
        }
    }

    /**
     * DocumentManager mock object together with
     * annotation mapping driver and database
     *
     * @param string $dbName
     * @param Doctrine\ODM\MongoDB\Mapping\Driver\Driver $mappingDriver
     * @return DocumentManager
     */
    protected function getMockDocumentManager($dbName, MappingDriverODM $mappingDriver = null)
    {
        if (!class_exists('Mongo')) {
            $this->markTestSkipped('Missing Mongo extension.');
        }
        if (version_compare(\Doctrine\Common\Version::VERSION, '2.2.0-DEV', '>=')) {
            $this->markTestSkipped('ODM does not support version 2.2 of doctrine common.');
        }
        $conn = new Connection;
        $config = $this->getMockAnnotatedODMMongoDBConfig($dbName, $mappingDriver);

        $dm = null;
        try {
            $dm = DocumentManager::create($conn, $config, $this->getEventManager());
            $dm->getConnection()->connect();
        } catch (\MongoException $e) {
            $this->markTestSkipped('Doctrine MongoDB ODM failed to connect');
        }
        return $dm;
    }

    /**
     * DocumentManager mock object with
     * annotation mapping driver
     *
     * @param string $dbName
     * @param Doctrine\ODM\MongoDB\Mapping\Driver\Driver $mappingDriver
     * @return DocumentManager
     */
    protected function getMockMappedDocumentManager($dbName, MappingDriverODM $mappingDriver = null)
    {
        $conn = $this->getMock('Doctrine\\MongoDB\\Connection');
        $config = $this->getMockAnnotatedODMMongoDBConfig($dbName, $mappingDriver);

        $dm = DocumentManager::create($conn, $config, $this->getEventManager());
        return $dm;
    }

    /**
     * EntityManager mock object together with
     * annotation mapping driver and pdo_sqlite
     * database in memory
     *
     * @param array $fixtures
     * @param Doctrine\ORM\Mapping\Driver\Driver $mappingDriver
     * @return EntityManager
     */
    protected function getMockSqliteEntityManager(array $fixtures, MappingDriverORM $mappingDriver = null)
    {
        $conn = array(
            'driver' => 'pdo_sqlite',
            'memory' => true,
        );

        $config = $this->getMockAnnotatedORMConfig($mappingDriver);
        $em = EntityManager::create($conn, $config, $this->getEventManager());

        $schema = array_map(function($class) use ($em) {
            return $em->getClassMetadata($class);
        }, $fixtures);

        $schemaTool = new SchemaTool($em);
        $schemaTool->dropSchema(array());
        $schemaTool->createSchema($schema);
        return $em;
    }

    /**
     * EntityManager mock object with
     * annotation mapping driver
     *
     * @param Doctrine\ORM\Mapping\Driver\Driver $mappingDriver
     * @return EntityManager
     */
    protected function getMockMappedEntityManager(MappingDriverORM $mappingDriver = null)
    {
        $driver = $this->getMock('Doctrine\DBAL\Driver');
        $driver->expects($this->once())
            ->method('getDatabasePlatform')
            ->will($this->returnValue($this->getMock('Doctrine\DBAL\Platforms\MySqlPlatform')));

        $conn = $this->getMock('Doctrine\DBAL\Connection', array(), array(array(), $driver));
        $conn->expects($this->once())
            ->method('getEventManager')
            ->will($this->returnValue($this->getEventManager()));

        $config = $this->getMockAnnotatedORMConfig($mappingDriver);
        $em = EntityManager::create($conn, $config);
        return $em;
    }

    /**
     * Creates default mapping driver
     *
     * @return \Doctrine\ORM\Mapping\Driver\Driver
     */
    protected function getDefaultORMMetadataDriverImplementation()
    {
        return new AnnotationDriverORM($_ENV['annotation_reader']);
    }

    /**
     * Creates default mapping driver
     *
     * @return \Doctrine\ODM\MongoDB\Mapping\Driver\Driver
     */
    protected function getDefaultMongoODMMetadataDriverImplementation()
    {
        return new AnnotationDriverODM($_ENV['annotation_reader']);
    }

    /**
     * Build event manager
     *
     * @return EventManager
     */
    private function getEventManager()
    {
        if (is_null($this->evm)) {
            $this->evm = new EventManager;
            $this->evm->addEventSubscriber(new TreeListener);
            $this->evm->addEventSubscriber(new SluggableListener);
            $this->evm->addEventSubscriber(new LoggableListener);
            $this->evm->addEventSubscriber(new TranslationListener);
            $this->evm->addEventSubscriber(new TimestampableListener);
        }
        return $this->evm;
    }

    /**
     * Get annotation mapping configuration
     *
     * @param string $dbName
     * @param Doctrine\ODM\MongoDB\Mapping\Driver\Driver $mappingDriver
     * @return Doctrine\ORM\Configuration
     */
    private function getMockAnnotatedODMMongoDBConfig($dbName, MappingDriverODM $mappingDriver = null)
    {
        $config = $this->getMock('Doctrine\\ODM\\MongoDB\\Configuration');
        $config->expects($this->once())
            ->method('getProxyDir')
            ->will($this->returnValue(__DIR__.'/../../temp'));

        $config->expects($this->once())
            ->method('getProxyNamespace')
            ->will($this->returnValue('Proxy'));

        $config->expects($this->once())
            ->method('getHydratorDir')
            ->will($this->returnValue(__DIR__.'/../../temp'));

        $config->expects($this->once())
            ->method('getHydratorNamespace')
            ->will($this->returnValue('Hydrator'));

        $config->expects($this->any())
            ->method('getDefaultDB')
            ->will($this->returnValue($dbName));

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

        if (is_null($mappingDriver)) {
            $mappingDriver = $this->getDefaultMongoODMMetadataDriverImplementation();
        }

        $config->expects($this->any())
            ->method('getMetadataDriverImpl')
            ->will($this->returnValue($mappingDriver));

        return $config;
    }

    /**
     * Get annotation mapping configuration for ORM
     *
     * @param Doctrine\ORM\Mapping\Driver\Driver $mappingDriver
     * @return Doctrine\ORM\Configuration
     */
    private function getMockAnnotatedORMConfig(MappingDriverORM $mappingDriver = null)
    {
        $config = $this->getMock('Doctrine\ORM\Configuration');
        $config->expects($this->once())
            ->method('getProxyDir')
            ->will($this->returnValue(__DIR__.'/../../temp'));

        $config->expects($this->once())
            ->method('getProxyNamespace')
            ->will($this->returnValue('Proxy'));

        $config->expects($this->once())
            ->method('getAutoGenerateProxyClasses')
            ->will($this->returnValue(true));

        $config->expects($this->once())
            ->method('getClassMetadataFactoryName')
            ->will($this->returnValue('Doctrine\\ORM\\Mapping\\ClassMetadataFactory'));

        if (is_null($mappingDriver)) {
            $mappingDriver = $this->getDefaultORMMetadataDriverImplementation();
        }

        $config->expects($this->any())
            ->method('getMetadataDriverImpl')
            ->will($this->returnValue($mappingDriver));

        return $config;
    }
}