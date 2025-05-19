<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping;

use Gedmo\Mapping\ExtensionMetadataFactory;
use Gedmo\References\ReferencesListener;
use Gedmo\Tests\Mapping\Fixture\Referenced;
use Gedmo\Tests\Mapping\Fixture\Referencer;

/**
 * These are mapping tests for the references extension
 *
 * @requires extension mongodb
 */
final class ReferencesMappingMongoDBODMTest extends MongoDBODMMappingTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $listener = new ReferencesListener();
        $listener->setCacheItemPool($this->cache);

        $this->dm->getEventManager()->addEventSubscriber($listener);
    }

    public function testMapping(): void
    {
        // Force metadata class loading.
        $this->dm->getClassMetadata(Referencer::class);
        $cacheId = ExtensionMetadataFactory::getCacheId(Referencer::class, 'Gedmo\References');

        $config = $this->cache->getItem($cacheId)->get();

        static::assertArrayHasKey('referenceOne', $config);
        static::assertArrayHasKey('referenceMany', $config);
        static::assertArrayHasKey('referencedEntities', $config['referenceMany']);
        static::assertArrayHasKey('field', $config['referenceMany']['referencedEntities']);
        static::assertSame('referencedEntities', $config['referenceMany']['referencedEntities']['field']);
        static::assertArrayHasKey('type', $config['referenceMany']['referencedEntities']);
        static::assertSame('entity', $config['referenceMany']['referencedEntities']['type']);
        static::assertArrayHasKey('class', $config['referenceMany']['referencedEntities']);
        static::assertSame(Referenced::class, $config['referenceMany']['referencedEntities']['class']);
        static::assertArrayHasKey('identifier', $config['referenceMany']['referencedEntities']);
        static::assertNull($config['referenceMany']['referencedEntities']['identifier']);
        static::assertArrayHasKey('mappedBy', $config['referenceMany']['referencedEntities']);
        static::assertSame('referencer', $config['referenceMany']['referencedEntities']['mappedBy']);
        static::assertArrayHasKey('inversedBy', $config['referenceMany']['referencedEntities']);
        static::assertNull($config['referenceMany']['referencedEntities']['inversedBy']);
        static::assertArrayHasKey('referenceManyEmbed', $config);
        static::assertArrayHasKey('useObjectClass', $config);
        static::assertSame(Referencer::class, $config['useObjectClass']);
    }
}
