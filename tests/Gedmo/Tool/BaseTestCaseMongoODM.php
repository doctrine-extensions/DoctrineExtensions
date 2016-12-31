<?php

namespace Tool;

use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Common\EventManager;
use Doctrine\MongoDB\Connection;
use Doctrine\ODM\MongoDB\Repository\DefaultRepositoryFactory;
use Gedmo\Translatable\TranslatableListener;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Timestampable\TimestampableListener;
use Gedmo\SoftDeleteable\SoftDeleteableListener;
use Gedmo\Loggable\LoggableListener;

/**
 * Base test case contains common mock objects
 * and functionality among all extensions using
 * ORM object manager
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
abstract class BaseTestCaseMongoODM extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DocumentManager
     */
    protected $dm;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        if (!class_exists('Mongo')) {
            $this->markTestSkipped('Missing Mongo extension.');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        if ($this->dm) {
            foreach ($this->dm->getDocumentDatabases() as $db) {
                foreach ($db->listCollections() as $collection) {
                    $collection->drop();
                }
            }
            $this->dm->getConnection()->close();
            $this->dm = null;
        }
    }

    /**
     * DocumentManager mock object together with
     * annotation mapping driver and database
     *
     * @param EventManager $evm
     *
     * @return DocumentManager
     */
    protected function getMockDocumentManager(EventManager $evm = null, $config = null)
    {
        $conn = new Connection();

        $config = $config ? $config : $this->getMockAnnotatedConfig();

        try {
            $this->dm = DocumentManager::create($conn, $config, $evm ?: $this->getEventManager());
            $this->dm->getConnection()->connect();
        } catch (\MongoException $e) {
            $this->markTestSkipped('Doctrine MongoDB ODM failed to connect');
        }

        return $this->dm;
    }

    /**
     * DocumentManager mock object with
     * annotation mapping driver
     *
     * @param EventManager $evm
     *
     * @return DocumentManager
     */
    protected function getMockMappedDocumentManager(EventManager $evm = null, $config = null)
    {
        $conn = $this->getMockBuilder('Doctrine\\MongoDB\\Connection')->getMock();

        $config = $config ? $config : $this->getMockAnnotatedConfig();

        $this->dm = DocumentManager::create($conn, $config, $evm ?: $this->getEventManager());

        return $this->dm;
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
     * Build event manager
     *
     * @return EventManager
     */
    private function getEventManager()
    {
        $evm = new EventManager();
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
     * @return Doctrine\ORM\Configuration
     */
    protected function getMockAnnotatedConfig()
    {
        $config = new \Doctrine\ODM\MongoDB\Configuration();
        $config->addFilter("softdeleteable", 'Gedmo\\SoftDeleteable\\Filter\\ODM\\SoftDeleteableFilter');
        $config->setProxyDir(__DIR__."/../../temp");
        $config->setHydratorDir(__DIR__."/../../temp");
        $config->setProxyNamespace("Proxy");
        $config->setHydratorNamespace("Hydrator");
        $config->setDefaultDB("gedmo_extensions_test");
        $config->setAutoGenerateProxyClasses(true);
        $config->setAutoGenerateHydratorClasses(true);
        $config->setMetadataDriverImpl($this->getMetadataDriverImplementation());
        return $config;
    }
}
