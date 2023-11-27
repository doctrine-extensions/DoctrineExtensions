<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ODM\MongoDB\Mapping\Driver as DriverMongodbODM;
use Doctrine\ORM\Mapping\Driver as DriverORM;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Gedmo\DoctrineExtensions;
use PHPUnit\Framework\TestCase;

/**
 * This test covers the driver registration helpers in the {@see DoctrineExtensions} class.
 */
final class DoctrineExtensionsTest extends TestCase
{
    /**
     * @requires PHP >= 8.0
     */
    public function testRegistersAttributeDriverForConcreteOrmEntitiesToChain(): void
    {
        $chain = new MappingDriverChain();

        DoctrineExtensions::registerMappingIntoDriverChainORM($chain);

        $drivers = $chain->getDrivers();

        static::assertArrayHasKey('Gedmo', $drivers);
        static::assertInstanceOf(DriverORM\AttributeDriver::class, $drivers['Gedmo'], 'The attribute driver should be registered to the chain on PHP 8');
    }

    public function testRegistersAnnotationDriverForConcreteOrmEntitiesToChain(): void
    {
        if (\PHP_VERSION_ID >= 80000 || !class_exists(AnnotationReader::class)) {
            static::markTestSkipped('Test only applies to PHP 7 and requires the doctrine/annotations package');
        }

        $chain = new MappingDriverChain();

        DoctrineExtensions::registerMappingIntoDriverChainORM($chain);

        $drivers = $chain->getDrivers();

        static::assertArrayHasKey('Gedmo', $drivers);
        static::assertInstanceOf(DriverORM\AnnotationDriver::class, $drivers['Gedmo'], 'The annotations driver should be registered to the chain on PHP 7');
    }

    /**
     * @requires PHP >= 8.0
     */
    public function testRegistersAttributeDriverForAbstractOrmSuperclassesToChain(): void
    {
        $chain = new MappingDriverChain();

        DoctrineExtensions::registerAbstractMappingIntoDriverChainORM($chain);

        $drivers = $chain->getDrivers();

        static::assertArrayHasKey('Gedmo', $drivers);
        static::assertInstanceOf(DriverORM\AttributeDriver::class, $drivers['Gedmo'], 'The attribute driver should be registered to the chain on PHP 8');
    }

    public function testRegistersAnnotationDriverForAbstractOrmSuperclassesToChain(): void
    {
        if (\PHP_VERSION_ID >= 80000 || !class_exists(AnnotationReader::class)) {
            static::markTestSkipped('Test only applies to PHP 7 and requires the doctrine/annotations package');
        }

        $chain = new MappingDriverChain();

        DoctrineExtensions::registerAbstractMappingIntoDriverChainORM($chain);

        $drivers = $chain->getDrivers();

        static::assertArrayHasKey('Gedmo', $drivers);
        static::assertInstanceOf(DriverORM\AnnotationDriver::class, $drivers['Gedmo'], 'The annotations driver should be registered to the chain on PHP 7');
    }

    /**
     * @requires PHP >= 8.0
     */
    public function testRegistersAttributeDriverForConcreteOdmDocumentsToChain(): void
    {
        if (!class_exists(DriverMongodbODM\AttributeDriver::class)) {
            static::markTestSkipped('Test requires the attribute mapping driver from the doctrine/mongodb-odm package');
        }

        $chain = new MappingDriverChain();

        DoctrineExtensions::registerMappingIntoDriverChainMongodbODM($chain);

        $drivers = $chain->getDrivers();

        static::assertArrayHasKey('Gedmo', $drivers);
        static::assertInstanceOf(DriverMongodbODM\AttributeDriver::class, $drivers['Gedmo'], 'The attribute driver should be registered to the chain on PHP 8');
    }

    public function testRegistersAnnotationDriverForConcreteOdmDocumentsToChain(): void
    {
        if (\PHP_VERSION_ID >= 80000 || !class_exists(AnnotationReader::class)) {
            static::markTestSkipped('Test only applies to PHP 7 and requires the doctrine/annotations package');
        }

        $chain = new MappingDriverChain();

        DoctrineExtensions::registerMappingIntoDriverChainMongodbODM($chain);

        $drivers = $chain->getDrivers();

        static::assertArrayHasKey('Gedmo', $drivers);
        static::assertInstanceOf(DriverMongodbODM\AnnotationDriver::class, $drivers['Gedmo'], 'The annotations driver should be registered to the chain on PHP 7');
    }

    /**
     * @requires PHP >= 8.0
     */
    public function testRegistersAttributeDriverForAbstractOdmSuperclassesToChain(): void
    {
        if (!class_exists(DriverMongodbODM\AttributeDriver::class)) {
            static::markTestSkipped('Test requires the attribute mapping driver from the doctrine/mongodb-odm package');
        }

        $chain = new MappingDriverChain();

        DoctrineExtensions::registerAbstractMappingIntoDriverChainMongodbODM($chain);

        $drivers = $chain->getDrivers();

        static::assertArrayHasKey('Gedmo', $drivers);
        static::assertInstanceOf(DriverMongodbODM\AttributeDriver::class, $drivers['Gedmo'], 'The attribute driver should be registered to the chain on PHP 8');
    }

    public function testRegistersAnnotationDriverForAbstractOdmSuperclassesToChain(): void
    {
        if (\PHP_VERSION_ID >= 80000 || !class_exists(AnnotationReader::class)) {
            static::markTestSkipped('Test only applies to PHP 7 and requires the doctrine/annotations package');
        }

        $chain = new MappingDriverChain();

        DoctrineExtensions::registerAbstractMappingIntoDriverChainMongodbODM($chain);

        $drivers = $chain->getDrivers();

        static::assertArrayHasKey('Gedmo', $drivers);
        static::assertInstanceOf(DriverMongodbODM\AnnotationDriver::class, $drivers['Gedmo'], 'The annotations driver should be registered to the chain on PHP 7');
    }
}
