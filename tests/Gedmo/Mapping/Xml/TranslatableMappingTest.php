<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping\Xml;

use Doctrine\ORM\EntityManager;
use Gedmo\Mapping\ExtensionMetadataFactory;
use Gedmo\Tests\Mapping\Fixture\Xml\TranslatableWithEmbedded;
use Gedmo\Tests\Mapping\ORMMappingTestCase;
use Gedmo\Translatable\TranslatableListener;

/**
 * These are mapping extension tests
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class TranslatableMappingTest extends ORMMappingTestCase
{
    private EntityManager $em;

    protected function setUp(): void
    {
        parent::setUp();

        $listener = new TranslatableListener();
        $listener->setCacheItemPool($this->cache);

        $this->em = $this->getBasicEntityManager();
        $this->em->getEventManager()->addEventSubscriber($listener);
    }

    public function testTranslatableMetadataWithEmbedded(): void
    {
        // Force metadata class loading.
        $this->em->getClassMetadata(TranslatableWithEmbedded::class);
        $cacheId = ExtensionMetadataFactory::getCacheId(TranslatableWithEmbedded::class, 'Gedmo\Translatable');

        $config = $this->cache->getItem($cacheId)->get();

        static::assertContains('embedded.subtitle', $config['fields']);
    }
}
