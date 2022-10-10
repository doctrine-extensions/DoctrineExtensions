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
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\Persistence\Mapping\AbstractClassMetadataFactory;
use Gedmo\Mapping\ExtensionMetadataFactory;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Tests\Mapping\Fixture\Sluggable;
use Psr\Cache\CacheItemPoolInterface;

final class MappingEventSubscriberTest extends ORMMappingTestCase
{
    /**
     * @var EntityManager
     */
    private $em;

    protected function setUp(): void
    {
        parent::setUp();

        $config = $this->getBasicConfiguration();

        $config->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader()));

        $conn = [
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ];

        $this->em = EntityManager::create($conn, $config, new EventManager());
    }

    public function testGetMetadataFactoryCacheFromDoctrine(): void
    {
        $metadataFactory = $this->em->getMetadataFactory();
        $getCache = \Closure::bind(static function (AbstractClassMetadataFactory $metadataFactory): ?CacheItemPoolInterface {
            return $metadataFactory->getCache();
        }, null, \get_class($metadataFactory));

        $cache = $getCache($metadataFactory);

        $cacheKey = ExtensionMetadataFactory::getCacheId(Sluggable::class, 'Gedmo\Sluggable');

        static::assertFalse($cache->hasItem($cacheKey));

        $subscriber = new SluggableListener();
        $classMetadata = $this->em->getClassMetadata(Sluggable::class);
        $subscriber->getExtensionMetadataFactory($this->em)->getExtensionMetadata($classMetadata);

        static::assertTrue($cache->hasItem($cacheKey));
    }

    protected function getUsedEntityFixtures(): array
    {
        return [
            Sluggable::class,
        ];
    }
}
