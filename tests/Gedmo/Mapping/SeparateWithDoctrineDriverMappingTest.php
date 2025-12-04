<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Mapping;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Mapping\Driver\YamlDriver;
use Gedmo\Tests\Mapping\Fixture\Category;
use Gedmo\Tests\Mapping\Fixture\Xml\SeparateDoctrineMapping;
use Gedmo\Tests\Mapping\Fixture\Xml\Timestampable;
use Gedmo\Tests\Mapping\ORMMappingTestCase;
use Gedmo\Timestampable\TimestampableListener;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * These are mapping tests for timestampable extension
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class SeparateWithDoctrineDriverMappingTest extends ORMMappingTestCase
{
    private EntityManager $em;
    private EntityManager $emWithForceAttr;
    /**
     * @var CacheItemPoolInterface
     */
    protected $cacheWithForceAttr;

    protected function setUp(): void
    {
        if (PHP_VERSION_ID < 80000) {
            self::markTestSkipped('Test requires PHP 8.');
        }

        parent::setUp();

        // Base Configuration without forceUseAttributeReader option
        $listener = new TimestampableListener();
        $listener->setCacheItemPool($this->cache);

        $this->em = $this->getBasicEntityManager();
        $this->em->getEventManager()->addEventSubscriber($listener);


        // Configuration with forceUseAttributeReader option
        $this->cacheWithForceAttr = new ArrayAdapter();
        $listener = new TimestampableListener();
        $listener->setCacheItemPool($this->cacheWithForceAttr);
        $listener->setForceUseAttributeReader(true);
        $this->emWithForceAttr = $this->getBasicEntityManager();
        $this->emWithForceAttr->getEventManager()->addEventSubscriber($listener);


    }


    /**
     * @return \Generator<string, array{class-string}>
     *
     * @note the XML fixture has a different mapping from the other configs, so it is tested separately
     */
    public static function dataTimestampableObject(): \Generator
    {
        yield 'Model with separated doctrine(XML Driver), gedmo(Attribute Driver)' => [SeparateDoctrineMapping::class, false, true];
        yield 'Model without attributes (XML Driver only)' => [Timestampable::class, false, false];
        yield 'Model with attributes (Attribute Driver only)' => [Category::class, true, true];

    }

    /**
     * @param class-string $className
     *
     * @dataProvider dataTimestampableObject
     */
    public function testForceGedmoInAttributeDriverMapping(string $className, bool $doctrineMappingInAttributes, bool $gedmoMappingInAttributes): void
    {
        // This entityManager configured to enforce the use of attributes for gedmo.
        $em = $this->emWithForceAttr;
        $cache = $this->cacheWithForceAttr;
        $em->getClassMetadata($className);

        $this->em->getClassMetadata($className);
        $cacheId = ExtensionMetadataFactory::getCacheId($className, 'Gedmo\Timestampable');
        $config = $cache->getItem($cacheId)->get();

        if($gedmoMappingInAttributes) {
            static::assertArrayHasKey('create', $config);
            static::assertSame('created', $config['create'][0]);
            static::assertArrayHasKey('update', $config);
            static::assertSame('updated', $config['update'][0]);
        }else{
            static::assertArrayNotHasKey('create', $config);
            static::assertArrayNotHasKey('update', $config);
        }
    }
    /**
     * @param class-string $className
     *
     * @dataProvider dataTimestampableObject
     */
    public function testStandardWayMapping(string $className, bool $doctrineMappingInAttributes, bool $gedmoMappingInAttributes): void
    {
        $em = $this->em;
        $cache = $this->cache;
        $em->getClassMetadata($className);

        $this->em->getClassMetadata($className);
        $cacheId = ExtensionMetadataFactory::getCacheId($className, 'Gedmo\Timestampable');
        $config = $cache->getItem($cacheId)->get();

        if($gedmoMappingInAttributes && !$doctrineMappingInAttributes) {
            // example: doctrine use XmlDriver, Gedmo - use AttributeDriver,
            // Gedmo attributes - unreadable
            static::assertArrayNotHasKey('create', $config);
            static::assertArrayNotHasKey('update', $config);
        }else{
            static::assertArrayHasKey('create', $config);
            static::assertSame('created', $config['create'][0]);
            static::assertArrayHasKey('update', $config);
            static::assertSame('updated', $config['update'][0]);
        }

    }

}
