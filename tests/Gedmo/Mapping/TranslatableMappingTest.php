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
use Gedmo\Tests\Mapping\Fixture\User as AnnotatedUser;
use Gedmo\Tests\Mapping\Fixture\Xml\User as XmlUser;
use Gedmo\Tests\Translatable\Fixture\PersonTranslation;
use Gedmo\Translatable\TranslatableListener;

/**
 * These are mapping tests for translatable behavior
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
        $listener->setTranslatableLocale('en_us');

        $this->em = $this->getBasicEntityManager();
        $this->em->getEventManager()->addEventSubscriber($listener);
    }

    /**
     * @return \Generator<string, array{class-string}>
     */
    public static function dataSortableObject(): \Generator
    {
        yield 'Model with XML mapping' => [XmlUser::class];
        yield 'Model with attributes' => [AnnotatedUser::class];
    }

    /**
     * @param class-string $className
     *
     * @dataProvider dataSortableObject
     */
    public function testTranslatableMapping(string $className): void
    {
        // Force metadata class loading.
        $this->em->getClassMetadata($className);
        $cacheId = ExtensionMetadataFactory::getCacheId($className, 'Gedmo\Translatable');
        $config = $this->cache->getItem($cacheId)->get();

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
