<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\Persistence\Mapping\AbstractClassMetadataFactory;
use Gedmo\Mapping\ExtensionMetadataFactory;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Tests\Mapping\Fixture\Sluggable;
use Gedmo\Tests\Mapping\Fixture\SuperClassExtension;
use Gedmo\Tests\Mapping\Mock\Extension\Encoder\EncoderListener;
use Psr\Cache\CacheItemPoolInterface;

final class MappingEventSubscriberTest extends ORMMappingTestCase
{
    private EntityManager $em;

    protected function setUp(): void
    {
        parent::setUp();

        $config = $this->getBasicConfiguration();

        $config->setMetadataDriverImpl(new AttributeDriver([]));

        $this->em = $this->getBasicEntityManager($config);
    }

    public function testGetMetadataFactoryCacheFromDoctrineForSluggable(): void
    {
        $metadataFactory = $this->em->getMetadataFactory();
        $getCache = \Closure::bind(static fn (AbstractClassMetadataFactory $metadataFactory): ?CacheItemPoolInterface => $metadataFactory->getCache(), null, $metadataFactory::class);

        $cache = $getCache($metadataFactory);

        $cacheKey = ExtensionMetadataFactory::getCacheId(Sluggable::class, 'Gedmo\Sluggable');

        static::assertFalse($cache->hasItem($cacheKey));

        $subscriber = new SluggableListener();
        $classMetadata = $this->em->getClassMetadata(Sluggable::class);
        $subscriber->getExtensionMetadataFactory($this->em)->getExtensionMetadata($classMetadata);

        static::assertTrue($cache->hasItem($cacheKey));
    }

    public function testGetMetadataFactoryCacheFromDoctrineForSuperClassExtension(): void
    {
        $metadataFactory = $this->em->getMetadataFactory();
        $getCache = \Closure::bind(static fn (AbstractClassMetadataFactory $metadataFactory): ?CacheItemPoolInterface => $metadataFactory->getCache(), null, $metadataFactory::class);

        /** @var CacheItemPoolInterface $cache */
        $cache = $getCache($metadataFactory);

        $cacheKey = ExtensionMetadataFactory::getCacheId(SuperClassExtension::class, 'Gedmo\Tests\Mapping\Mock\Extension\Encoder');

        static::assertFalse($cache->hasItem($cacheKey));

        $subscriber = new EncoderListener();
        $classMetadata = $this->em->getClassMetadata(SuperClassExtension::class);

        $config = $subscriber->getExtensionMetadataFactory($this->em)->getExtensionMetadata($classMetadata);

        static::assertSame([
            'content' => [
                'type' => 'md5',
                'secret' => null,
            ],
        ], $config['encode']);

        // Create new configuration to use new array cache
        $config = $this->getBasicConfiguration();

        $config->setMetadataDriverImpl(new AttributeDriver([]));

        $this->em = $this->getBasicEntityManager($config);

        $config = $subscriber->getExtensionMetadataFactory($this->em)->getExtensionMetadata($classMetadata);

        static::assertSame([
            'content' => [
                'type' => 'md5',
                'secret' => null,
            ],
        ], $config['encode']);
    }
}
