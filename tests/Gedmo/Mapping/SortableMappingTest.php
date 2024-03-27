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
use Gedmo\Sortable\SortableListener;
use Gedmo\Tests\Mapping\Fixture\Sortable as AnnotatedSortable;
use Gedmo\Tests\Mapping\Fixture\Xml\Sortable as XmlSortable;

/**
 * These are mapping tests for sortable extension
 *
 * @author Lukas Botsch <lukas.botsch@gmail.com>
 */
final class SortableMappingTest extends ORMMappingTestCase
{
    private EntityManager $em;

    protected function setUp(): void
    {
        parent::setUp();

        $listener = new SortableListener();
        $listener->setCacheItemPool($this->cache);

        $this->em = $this->getBasicEntityManager();
        $this->em->getEventManager()->addEventSubscriber($listener);
    }

    /**
     * @return \Generator<string, array{class-string}>
     */
    public static function dataSortableObject(): \Generator
    {
        yield 'Model with XML mapping' => [XmlSortable::class];
        yield 'Model with attributes' => [AnnotatedSortable::class];
    }

    /**
     * @param class-string $className
     *
     * @dataProvider dataSortableObject
     */
    public function testSortableMapping(string $className): void
    {
        // Force metadata class loading.
        $this->em->getClassMetadata($className);
        $cacheId = ExtensionMetadataFactory::getCacheId($className, 'Gedmo\Sortable');
        $config = $this->cache->getItem($cacheId)->get();

        static::assertArrayHasKey('position', $config);
        static::assertSame('position', $config['position']);
        static::assertArrayHasKey('groups', $config);
        static::assertCount(3, $config['groups']);
        static::assertSame('grouping', $config['groups'][0]);
        static::assertSame('sortable_group', $config['groups'][1]);
        static::assertSame('sortable_groups', $config['groups'][2]);
    }
}
