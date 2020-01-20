<?php

namespace Tool;

use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Common\EventManager;
use Doctrine\MongoDB\Connection;
use Doctrine\ODM\MongoDB\Repository\DefaultRepositoryFactory;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Gedmo\Translatable\TranslatableListener;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Timestampable\TimestampableListener;
use Gedmo\SoftDeleteable\SoftDeleteableListener;
use Gedmo\Loggable\LoggableListener;
use MongoDB\Client;

/**
 * Base test case contains common mock objects
 * and functionality among all extensions using
 * ORM object manager
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
abstract class BaseTestCaseMongoODM extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DocumentManager
     */
    protected $dm;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        if (!class_exists('Mongo')) {
            $this->markTestSkipped('Missing Mongo extension.');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        if (null === $this->dm) {
            return;
        }

        foreach ($this->dm->getDocumentDatabases() as $documentDatabase) {
            $documentDatabase->drop();
        }

        unset($this->dm);
    }

    /**
     * DocumentManager mock object together with annotation mapping driver and database.
     *
     * @param EventManager       $evm
     * @param Configuration|null $config
     *
     * @return DocumentManager
     */
    protected function getMockDocumentManager(?EventManager $evm = null, ?Configuration $config = null): DocumentManager
    {
        $client = new Client($_ENV['MONGODB_SERVER'], [], ['typeMap' => DocumentManager::CLIENT_TYPEMAP]);

        $config = $config ?: $this->getMockAnnotatedConfig();
        $evm = $evm ?: $this->getEventManager();

        return $this->dm = DocumentManager::create($client, $config, $evm);
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
     * @return MappingDriver
     */
    protected function getMetadataDriverImplementation(): MappingDriver
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
     * @return Configuration
     */
    protected function getMockAnnotatedConfig(): Configuration
    {
        $config = new Configuration();
        $config->addFilter("softdeleteable", 'Gedmo\\SoftDeleteable\\Filter\\ODM\\SoftDeleteableFilter');
        $config->setProxyDir(__DIR__."/../../temp");
        $config->setHydratorDir(__DIR__."/../../temp");
        $config->setProxyNamespace("Proxy");
        $config->setHydratorNamespace("Hydrator");
        $config->setDefaultDB("gedmo_extensions_test");
        $config->setAutoGenerateProxyClasses(Configuration::AUTOGENERATE_EVAL);
        $config->setAutoGenerateHydratorClasses(true);
        $config->setMetadataDriverImpl($this->getMetadataDriverImplementation());
        return $config;
    }
}
