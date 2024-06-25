<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\EventManager;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;
use Doctrine\ODM\MongoDB\Mapping\Driver\AttributeDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use MongoDB\Client;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

abstract class MongoDBODMMappingTestCase extends TestCase
{
    protected CacheItemPoolInterface $cache;

    protected DocumentManager $dm;

    protected function setUp(): void
    {
        $this->cache = new ArrayAdapter();
        $this->dm = $this->getBasicDocumentManager();
    }

    protected function tearDown(): void
    {
        foreach ($this->dm->getDocumentDatabases() as $documentDatabase) {
            $documentDatabase->drop();
        }
    }

    final protected function getBasicConfiguration(): Configuration
    {
        $config = new Configuration();
        $config->setProxyDir(TESTS_TEMP_DIR);
        $config->setHydratorDir(TESTS_TEMP_DIR);
        $config->setProxyNamespace('Proxy');
        $config->setHydratorNamespace('Hydrator');
        $config->setDefaultDB('gedmo_extensions_test');
        $config->setAutoGenerateProxyClasses(Configuration::AUTOGENERATE_EVAL);
        $config->setAutoGenerateHydratorClasses(Configuration::AUTOGENERATE_EVAL);
        $config->setMetadataCache(new ArrayAdapter());

        return $config;
    }

    final protected function getBasicDocumentManager(?Configuration $config = null, ?Client $client = null, ?EventManager $evm = null): DocumentManager
    {
        if (null === $config) {
            $config = $this->getBasicConfiguration();
            $config->setMetadataDriverImpl($this->createChainedMappingDriver());
        }

        $client = new Client($_ENV['MONGODB_SERVER'], [], ['typeMap' => DocumentManager::CLIENT_TYPEMAP]);

        return DocumentManager::create($client, $config, $evm);
    }

    final protected function createChainedMappingDriver(): MappingDriverChain
    {
        $chain = new MappingDriverChain();

        if (PHP_VERSION_ID >= 80000 && class_exists(AttributeDriver::class)) {
            $chain->addDriver(new AttributeDriver([]), 'Gedmo\Tests\Mapping\Fixture');
        } elseif (class_exists(AnnotationDriver::class) && class_exists(AnnotationReader::class)) {
            $chain->addDriver(new AnnotationDriver(new AnnotationReader()), 'Gedmo\Tests\Mapping\Fixture');
        }

        return $chain;
    }
}
