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
use Gedmo\ReferenceIntegrity\ReferenceIntegrityListener;
use Gedmo\Tests\Mapping\Fixture\Referencing;

/**
 * These are mapping tests for ReferenceIntegrity extension
 *
 * @author Jonathan Eskew <jonathan@jeskew.net>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @requires extension mongodb
 */
final class ReferenceIntegrityMappingTest extends MongoDBODMMappingTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $listener = new ReferenceIntegrityListener();
        $listener->setCacheItemPool($this->cache);

        $this->dm->getEventManager()->addEventSubscriber($listener);
    }

    public function testMapping(): void
    {
        // Force metadata class loading.
        $this->dm->getClassMetadata(Referencing::class);
        $cacheId = ExtensionMetadataFactory::getCacheId(Referencing::class, 'Gedmo\ReferenceIntegrity');

        $config = $this->cache->getItem($cacheId)->get();

        static::assertArrayHasKey('referenceIntegrity', $config);
        static::assertArrayHasKey('referencer', $config['referenceIntegrity']);
        static::assertSame('nullify', $config['referenceIntegrity']['referencer']);
    }
}
