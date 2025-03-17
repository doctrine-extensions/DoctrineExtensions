<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Gedmo\Exception\RuntimeException;
use Gedmo\Mapping\ExtensionMetadataFactory;

/**
 * Ensures the library guards the unsupported configuration where an annotation or
 * attribute based mapping driver is used but no reader can be provided (i.e. neither
 * attribute mapping nor the doctrine/annotations package is available).
 *
 * This protects the runtime contract implemented in {@see ExtensionMetadataFactory}:
 * a clear {@see RuntimeException} MUST be raised instead of failing later with an
 * unexpected error.
 */
final class ExtensionMetadataFactoryReaderTest extends ORMMappingTestCase
{
    public function testThrowsWhenNoReaderIsProvidedForAnAttributeOrAnnotationDriver(): void
    {
        $config = $this->getBasicConfiguration();
        $config->setMetadataDriverImpl(new AttributeDriver([]));

        $connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ], $config);

        $em = new EntityManager($connection, $config);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('an annotation or attribute reader was not provided');

        new ExtensionMetadataFactory($em, 'Gedmo\Timestampable', null, $this->cache);
    }
}
