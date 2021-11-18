<?php

namespace Gedmo\Tests\Translatable;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\DriverChain;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Gedmo\Mapping\ExtensionMetadataFactory;
use Gedmo\Tests\Mapping\Fixture\Yaml\User;
use Gedmo\Tests\Translatable\Fixture\PersonTranslation;
use Gedmo\Translatable\TranslatableListener;

/**
 * These are mapping tests for translatable behavior
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @see http://www.gediminasm.org
 *
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class TranslatableMappingTest extends \PHPUnit\Framework\TestCase
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
        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache());
        $config->setQueryCacheImpl(new \Doctrine\Common\Cache\ArrayCache());
        $config->setProxyDir(TESTS_TEMP_DIR);
        $config->setProxyNamespace('Gedmo\Mapping\Proxy');
        $chainDriverImpl = new DriverChain();
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
        $this->translatableListener = new TranslatableListener();
        $this->translatableListener->setTranslatableLocale('en_us');
        $evm->addEventSubscriber($this->translatableListener);
        $this->em = \Doctrine\ORM\EntityManager::create($conn, $config, $evm);
    }

    public function testYamlMapping()
    {
        $meta = $this->em->getClassMetadata(self::TEST_YAML_ENTITY_CLASS);
        $cacheId = ExtensionMetadataFactory::getCacheId(
            self::TEST_YAML_ENTITY_CLASS,
            'Gedmo\Translatable'
        );
        $config = $this->em->getMetadataFactory()->getCacheDriver()->fetch($cacheId);
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
