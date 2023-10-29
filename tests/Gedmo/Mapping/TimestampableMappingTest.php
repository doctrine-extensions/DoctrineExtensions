<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Gedmo\Mapping\ExtensionMetadataFactory;
use Gedmo\Tests\Mapping\Fixture\Yaml\Category;
use Gedmo\Timestampable\TimestampableListener;

/**
 * These are mapping tests for timestampable extension
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class TimestampableMappingTest extends ORMMappingTestCase
{
    private const TEST_YAML_ENTITY_CLASS = Category::class;

    private EntityManager $em;

    protected function setUp(): void
    {
        parent::setUp();

        $config = $this->getBasicConfiguration();

        $chain = new MappingDriverChain();

        // TODO - The ORM's YAML mapping is deprecated and removed in 3.0
        $chain->addDriver(new YamlDriver(__DIR__.'/Driver/Yaml'), 'Gedmo\Tests\Mapping\Fixture\Yaml');

        $config->setMetadataDriverImpl($chain);

        $conn = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        $evm = new EventManager();
        $listener = new TimestampableListener();
        $listener->setCacheItemPool($this->cache);
        $evm->addEventSubscriber($listener);
        $connection = DriverManager::getConnection($conn, $config);
        $this->em = new EntityManager($connection, $config, $evm);
    }

    public function testYamlMapping(): void
    {
        // Force metadata class loading.
        $this->em->getClassMetadata(self::TEST_YAML_ENTITY_CLASS);
        $cacheId = ExtensionMetadataFactory::getCacheId(
            self::TEST_YAML_ENTITY_CLASS,
            'Gedmo\Timestampable'
        );
        $config = $this->cache->getItem($cacheId)->get();
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
