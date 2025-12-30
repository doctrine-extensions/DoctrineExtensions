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
use Gedmo\Mapping\ExtensionMetadataFactory;
use Gedmo\References\ReferencesListener;
use Gedmo\Tests\Mapping\Fixture\Referenced;
use Gedmo\Tests\Mapping\Fixture\Referencer;

/**
 * These are mapping tests for the references extension
 */
final class ReferencesMappingORMTest extends ORMMappingTestCase
{
    private EntityManager $em;

    protected function setUp(): void
    {
        parent::setUp();

        $listener = new ReferencesListener();
        $listener->setCacheItemPool($this->cache);

        $this->em = $this->getBasicEntityManager();
        $this->em->getEventManager()->addEventSubscriber($listener);
    }

    public function testMapping(): void
    {
        // Force metadata class loading.
        $this->em->getClassMetadata(Referenced::class);
        $cacheId = ExtensionMetadataFactory::getCacheId(Referenced::class, 'Gedmo\References');

        $config = $this->cache->getItem($cacheId)->get();

        static::assertArrayHasKey('referenceOne', $config);
        static::assertArrayHasKey('referencer', $config['referenceOne']);
        static::assertArrayHasKey('field', $config['referenceOne']['referencer']);
        static::assertSame('referencer', $config['referenceOne']['referencer']['field']);
        static::assertArrayHasKey('type', $config['referenceOne']['referencer']);
        static::assertSame('document', $config['referenceOne']['referencer']['type']);
        static::assertArrayHasKey('class', $config['referenceOne']['referencer']);
        static::assertSame(Referencer::class, $config['referenceOne']['referencer']['class']);
        static::assertArrayHasKey('identifier', $config['referenceOne']['referencer']);
        static::assertSame('referencerId', $config['referenceOne']['referencer']['identifier']);
        static::assertArrayHasKey('mappedBy', $config['referenceOne']['referencer']);
        static::assertNull($config['referenceOne']['referencer']['mappedBy']);
        static::assertArrayHasKey('inversedBy', $config['referenceOne']['referencer']);
        static::assertSame('referencedEntities', $config['referenceOne']['referencer']['inversedBy']);
        static::assertArrayHasKey('referenceMany', $config);
        static::assertArrayHasKey('referenceManyEmbed', $config);
        static::assertArrayHasKey('useObjectClass', $config);
        static::assertSame(Referenced::class, $config['useObjectClass']);
    }
}
