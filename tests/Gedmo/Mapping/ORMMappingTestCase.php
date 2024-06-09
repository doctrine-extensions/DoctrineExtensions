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
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

abstract class ORMMappingTestCase extends TestCase
{
    /**
     * @var CacheItemPoolInterface
     */
    protected $cache;

    protected function setUp(): void
    {
        $this->cache = new ArrayAdapter();
    }

    final protected function getBasicConfiguration(): Configuration
    {
        $config = new Configuration();
        $config->setMetadataCache(new ArrayAdapter());
        $config->setQueryCache(new ArrayAdapter());
        $config->setProxyDir(TESTS_TEMP_DIR);
        $config->setProxyNamespace('Gedmo\Mapping\Proxy');

        return $config;
    }

    final protected function getBasicEntityManager(?Configuration $config = null, ?Connection $connection = null, ?EventManager $evm = null): EntityManager
    {
        if (null === $config) {
            $config = $this->getBasicConfiguration();
            $config->setMetadataDriverImpl($this->createChainedMappingDriver());
        }

        $connection ??= DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ], $config);

        return new EntityManager($connection, $config, $evm);
    }

    final protected function createChainedMappingDriver(): MappingDriverChain
    {
        $chain = new MappingDriverChain();

        $chain->addDriver(new XmlDriver(__DIR__.'/Driver/Xml', XmlDriver::DEFAULT_FILE_EXTENSION, false), 'Gedmo\Tests\Mapping\Fixture\Xml');

        if (class_exists(YamlDriver::class)) {
            $chain->addDriver(new YamlDriver(__DIR__.'/Driver/Yaml'), 'Gedmo\Tests\Mapping\Fixture\Yaml');
        }

        if (PHP_VERSION_ID >= 80000) {
            $chain->addDriver(new AttributeDriver([]), 'Gedmo\Tests\Mapping\Fixture');
        } elseif (class_exists(AnnotationDriver::class) && class_exists(AnnotationReader::class)) {
            $chain->addDriver(new AnnotationDriver(new AnnotationReader()), 'Gedmo\Tests\Mapping\Fixture');
        }

        return $chain;
    }
}
