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
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;
use Doctrine\ODM\MongoDB\Mapping\Driver\AttributeDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Gedmo\Loggable\LoggableListener;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\SoftDeleteable\Filter\ODM\SoftDeleteableFilter;
use Gedmo\SoftDeleteable\SoftDeleteableListener;
use Gedmo\Timestampable\TimestampableListener;
use Gedmo\Translatable\TranslatableListener;
use MongoDB\Client;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * Base test case contains common mock objects
 * and functionality among all extensions using
 * ORM object manager
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
abstract class BaseTestCaseMongoODM extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DocumentManager|null
     */
    protected $dm;

    protected function setUp(): void
    {
        if (!extension_loaded('mongodb')) {
            static::markTestSkipped('Missing Mongo extension.');
        }
    }

    protected function tearDown(): void
    {
        if (null === $this->dm) {
            return;
        }

        foreach ($this->dm->getDocumentDatabases() as $documentDatabase) {
            $documentDatabase->drop();
        }

        $this->dm = null;
    }

    /**
     * DocumentManager mock object together with annotation mapping driver and database.
     */
    protected function getMockDocumentManager(?EventManager $evm = null, ?Configuration $config = null): DocumentManager
    {
        $client = new Client($_ENV['MONGODB_SERVER'], [], ['typeMap' => DocumentManager::CLIENT_TYPEMAP]);

        $config = $config ?: $this->getMockAnnotatedConfig();
        $evm = $evm ?: $this->getEventManager();

        return $this->dm = DocumentManager::create($client, $config, $evm);
    }

    protected function getDefaultDocumentManager(EventManager $evm = null): DocumentManager
    {
        return $this->getMockDocumentManager($evm, $this->getDefaultConfiguration());
    }

    /**
     * DocumentManager mock object with
     * annotation mapping driver
     */
    protected function getMockMappedDocumentManager(EventManager $evm = null, Configuration $config = null): DocumentManager
    {
        $conn = $this->createStub(Client::class);

        $config = $config ?? $this->getMockAnnotatedConfig();

        $this->dm = DocumentManager::create($conn, $config, $evm ?? $this->getEventManager());

        return $this->dm;
    }

    /**
     * Creates default mapping driver
     */
    protected function getMetadataDriverImplementation(): MappingDriver
    {
        return new AnnotationDriver($_ENV['annotation_reader']);
    }

    /**
     * Get annotation mapping configuration
     */
    protected function getMockAnnotatedConfig(): Configuration
    {
        $config = new Configuration();
        $config->addFilter('softdeleteable', SoftDeleteableFilter::class);
        $config->setProxyDir(TESTS_TEMP_DIR);
        $config->setHydratorDir(TESTS_TEMP_DIR);
        $config->setProxyNamespace('Proxy');
        $config->setHydratorNamespace('Hydrator');
        $config->setDefaultDB('gedmo_extensions_test');
        $config->setAutoGenerateProxyClasses(Configuration::AUTOGENERATE_EVAL);
        $config->setAutoGenerateHydratorClasses(Configuration::AUTOGENERATE_EVAL);
        $config->setMetadataDriverImpl($this->getMetadataDriverImplementation());
        $config->setMetadataCache(new ArrayAdapter());

        return $config;
    }

    protected function getDefaultConfiguration(): Configuration
    {
        $config = new Configuration();
        $config->addFilter('softdeleteable', SoftDeleteableFilter::class);
        $config->setProxyDir(TESTS_TEMP_DIR);
        $config->setHydratorDir(TESTS_TEMP_DIR);
        $config->setProxyNamespace('Proxy');
        $config->setHydratorNamespace('Hydrator');
        $config->setDefaultDB('gedmo_extensions_test');
        $config->setAutoGenerateProxyClasses(Configuration::AUTOGENERATE_EVAL);
        $config->setAutoGenerateHydratorClasses(Configuration::AUTOGENERATE_EVAL);
        $config->setMetadataDriverImpl($this->getMetadataDefaultDriverImplementation());
        $config->setMetadataCache(new ArrayAdapter());

        return $config;
    }

    private function getMetadataDefaultDriverImplementation(): MappingDriver
    {
        if (PHP_VERSION_ID >= 80000 && class_exists(AttributeDriver::class)) {
            return new AttributeDriver([]);
        }

        return new AnnotationDriver($_ENV['annotation_reader']);
    }

    /**
     * Build event manager
     */
    private function getEventManager(): EventManager
    {
        $evm = new EventManager();
        $evm->addEventSubscriber(new SluggableListener());
        $evm->addEventSubscriber(new LoggableListener());
        $evm->addEventSubscriber(new TranslatableListener());
        $evm->addEventSubscriber(new TimestampableListener());
        $evm->addEventSubscriber(new SoftDeleteableListener());

        return $evm;
    }
}
