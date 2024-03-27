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
use Gedmo\Tests\Mapping\Fixture\Uploadable as AnnotatedUploadable;
use Gedmo\Tests\Mapping\Fixture\Xml\Uploadable as XmlUploadable;
use Gedmo\Uploadable\Mapping\Validator;
use Gedmo\Uploadable\UploadableListener;

/**
 * These are mapping tests for Uploadable extension
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class UploadableMappingTest extends ORMMappingTestCase
{
    private EntityManager $em;

    protected function setUp(): void
    {
        parent::setUp();

        // TODO - This should be reset to default (true) after each test case
        Validator::$enableMimeTypesConfigException = false;

        $listener = new UploadableListener();
        $listener->setCacheItemPool($this->cache);

        $this->em = $this->getBasicEntityManager();
        $this->em->getEventManager()->addEventSubscriber($listener);
    }

    /**
     * @return \Generator<string, array{class-string}>
     */
    public static function dataUploadableObject(): \Generator
    {
        yield 'Model with XML mapping' => [XmlUploadable::class];
        yield 'Model with attributes' => [AnnotatedUploadable::class];
    }

    /**
     * @param class-string $className
     *
     * @dataProvider dataUploadableObject
     */
    public function testUploadableMapping(string $className): void
    {
        // Force metadata class loading.
        $this->em->getClassMetadata($className);
        $cacheId = ExtensionMetadataFactory::getCacheId($className, 'Gedmo\Uploadable');
        $config = $this->cache->getItem($cacheId)->get();

        static::assertTrue($config['uploadable']);
        static::assertTrue($config['allowOverwrite']);
        static::assertTrue($config['appendNumber']);
        static::assertSame('/my/path', $config['path']);
        static::assertSame('getPath', $config['pathMethod']);
        static::assertSame('mimeType', $config['fileMimeTypeField']);
        static::assertSame('path', $config['filePathField']);
        static::assertSame('size', $config['fileSizeField']);
        static::assertSame('callbackMethod', $config['callback']);
        static::assertSame(Validator::FILENAME_GENERATOR_SHA1, $config['filenameGenerator']);
        static::assertSame(1500.0, $config['maxSize']);
        static::assertContains('text/plain', $config['allowedTypes']);
        static::assertContains('text/css', $config['allowedTypes']);
        static::assertContains('video/jpeg', $config['disallowedTypes']);
        static::assertContains('text/html', $config['disallowedTypes']);
    }
}
