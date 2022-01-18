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
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Gedmo\Mapping\ExtensionMetadataFactory;
use Gedmo\Tests\Mapping\Fixture\Yaml\User;
use Gedmo\Tests\Translatable\Fixture\PersonTranslation;
use Gedmo\Translatable\TranslatableListener;

/**
 * These are mapping tests for translatable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class TranslatableMappingTest extends ORMMappingTestCase
{
    public const TEST_YAML_ENTITY_CLASS = User::class;

    /**
     * @var TranslatableListener
     */
    private $translatableListener;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    protected function setUp(): void
    {
        parent::setUp();

        $config = $this->getBasicConfiguration();
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

        $evm = new EventManager();
        $this->translatableListener = new TranslatableListener();
        $this->translatableListener->setCacheItemPool($this->cache);
        $this->translatableListener->setTranslatableLocale('en_us');
        $evm->addEventSubscriber($this->translatableListener);
        $this->em = EntityManager::create($conn, $config, $evm);
    }

    public function testYamlMapping(): void
    {
        $meta = $this->em->getClassMetadata(self::TEST_YAML_ENTITY_CLASS);
        $cacheId = ExtensionMetadataFactory::getCacheId(
            self::TEST_YAML_ENTITY_CLASS,
            'Gedmo\Translatable'
        );
        $config = $this->cache->getItem($cacheId)->get();
        static::assertArrayHasKey('translationClass', $config);
        static::assertSame(PersonTranslation::class, $config['translationClass']);
        static::assertArrayHasKey('fields', $config);
        static::assertCount(3, $config['fields']);
        static::assertSame('password', $config['fields'][0]);
        static::assertSame('username', $config['fields'][1]);
        static::assertArrayHasKey('locale', $config);
        static::assertSame('localeField', $config['locale']);
        static::assertCount(1, $config['fallback']);
        static::assertTrue($config['fallback']['company']);
    }
}
