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
use Gedmo\SoftDeleteable\SoftDeleteableListener;
use Gedmo\Tests\Mapping\Fixture\SoftDeleteable as AnnotatedSoftDeleteable;
use Gedmo\Tests\Mapping\Fixture\Xml\SoftDeleteable as XmlSoftDeleteable;

/**
 * These are mapping tests for SoftDeleteable extension
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class SoftDeleteableMappingTest extends ORMMappingTestCase
{
    private EntityManager $em;

    protected function setUp(): void
    {
        parent::setUp();

        $listener = new SoftDeleteableListener();
        $listener->setCacheItemPool($this->cache);

        $this->em = $this->getBasicEntityManager();
        $this->em->getEventManager()->addEventSubscriber($listener);
    }

    /**
     * @return \Generator<string, array{class-string}>
     */
    public static function dataSoftDeleteableObject(): \Generator
    {
        yield 'Model with XML mapping' => [XmlSoftDeleteable::class];
        yield 'Model with attributes' => [AnnotatedSoftDeleteable::class];
    }

    /**
     * @param class-string $className
     *
     * @dataProvider dataSoftDeleteableObject
     */
    public function testSoftDeleteableMapping(string $className): void
    {
        // Force metadata class loading.
        $this->em->getClassMetadata($className);
        $cacheId = ExtensionMetadataFactory::getCacheId($className, 'Gedmo\SoftDeleteable');
        $config = $this->cache->getItem($cacheId)->get();

        static::assertArrayHasKey('softDeleteable', $config);
        static::assertTrue($config['softDeleteable']);
        static::assertArrayHasKey('timeAware', $config);
        static::assertFalse($config['timeAware']);
        static::assertArrayHasKey('fieldName', $config);
        static::assertSame('deletedAt', $config['fieldName']);
    }
}
