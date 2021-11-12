<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Timestampable;

use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Gedmo\Mapping\ExtensionMetadataFactory;
use Gedmo\Tests\Mapping\Fixture\Yaml\Category;
use Gedmo\Timestampable\TimestampableListener;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * These are mapping tests for timestampable extension
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class TimestampableMappingTest extends \PHPUnit\Framework\TestCase
{
    public const TEST_YAML_ENTITY_CLASS = Category::class;
    private $em;

    protected function setUp(): void
    {
        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCache(new ArrayAdapter());
        $config->setQueryCache(new ArrayAdapter());
        $config->setProxyDir(TESTS_TEMP_DIR);
        $config->setProxyNamespace('Gedmo\Mapping\Proxy');
        $chainDriverImpl = new MappingDriverChain();
        $chainDriverImpl->addDriver(
            new YamlDriver([__DIR__.'/Driver/Yaml']),
            'Gedmo\Tests\Mapping\Fixture\Yaml'
        );
        $config->setMetadataDriverImpl($chainDriverImpl);

        $conn = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        //$config->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());

        $evm = new \Doctrine\Common\EventManager();
        $evm->addEventSubscriber(new TimestampableListener());
        $this->em = \Doctrine\ORM\EntityManager::create($conn, $config, $evm);
    }

    public function testYamlMapping()
    {
        $meta = $this->em->getClassMetadata(self::TEST_YAML_ENTITY_CLASS);
        $cacheId = ExtensionMetadataFactory::getCacheId(
            self::TEST_YAML_ENTITY_CLASS,
            'Gedmo\Timestampable'
        );
        $config = $this->em->getMetadataFactory()->getCacheDriver()->fetch($cacheId);
        static::assertArrayHasKey('create', $config);
        static::assertSame('created', $config['create'][0]);
        static::assertArrayHasKey('update', $config);
        static::assertSame('updated', $config['update'][0]);
        static::assertArrayHasKey('change', $config);
        $onChange = $config['change'][0];

        static::assertSame('changed', $onChange['field']);
        static::assertSame('title', $onChange['trackedField']);
        static::assertSame('Test', $onChange['value']);
    }
}
