<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo;

use function class_exists;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\PsrCachedReader;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Psr6\CacheAdapter;
use Doctrine\ODM\MongoDB\Mapping\Driver as DriverMongodbODM;
use Doctrine\ORM\Mapping\Driver as DriverORM;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * Version class allows checking the required dependencies
 * and the current version of the Doctrine Extensions library.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 */
final class DoctrineExtensions
{
    /**
     * Current version of extensions
     */
    public const VERSION = '3.6.0';

    /**
     * Hooks all extension metadata mapping drivers into
     * the given driver chain of drivers for the ORM.
     */
    public static function registerMappingIntoDriverChainORM(MappingDriverChain $driverChain, Reader $reader = null)
    {
        self::registerAnnotations();
        if (!$reader) {
            $reader = self::createAnnotationReader();
        }
        $annotationDriver = new DriverORM\AnnotationDriver($reader, [
            __DIR__.'/Translatable/Entity',
            __DIR__.'/Loggable/Entity',
            __DIR__.'/Tree/Entity',
        ]);
        $driverChain->addDriver($annotationDriver, 'Gedmo');
    }

    /**
     * Hooks only superclass extension metadata mapping drivers into
     * the given driver chain of drivers for the ORM.
     */
    public static function registerAbstractMappingIntoDriverChainORM(MappingDriverChain $driverChain, Reader $reader = null)
    {
        self::registerAnnotations();
        if (!$reader) {
            $reader = self::createAnnotationReader();
        }
        $annotationDriver = new DriverORM\AnnotationDriver($reader, [
            __DIR__.'/Translatable/Entity/MappedSuperclass',
            __DIR__.'/Loggable/Entity/MappedSuperclass',
            __DIR__.'/Tree/Entity/MappedSuperclass',
        ]);
        $driverChain->addDriver($annotationDriver, 'Gedmo');
    }

    /**
     * Hooks all extension metadata mapping drivers into
     * the given driver chain of drivers for the MongoDB ODM.
     */
    public static function registerMappingIntoDriverChainMongodbODM(MappingDriverChain $driverChain, Reader $reader = null)
    {
        self::registerAnnotations();
        if (!$reader) {
            $reader = self::createAnnotationReader();
        }
        $annotationDriver = new DriverMongodbODM\AnnotationDriver($reader, [
            __DIR__.'/Translatable/Document',
            __DIR__.'/Loggable/Document',
        ]);
        $driverChain->addDriver($annotationDriver, 'Gedmo');
    }

    /**
     * Hooks only superclass extension metadata mapping drivers into
     * the given driver chain of drivers for the MongoDB ODM.
     */
    public static function registerAbstractMappingIntoDriverChainMongodbODM(MappingDriverChain $driverChain, Reader $reader = null)
    {
        self::registerAnnotations();
        if (!$reader) {
            $reader = self::createAnnotationReader();
        }
        $annotationDriver = new DriverMongodbODM\AnnotationDriver($reader, [
            __DIR__.'/Translatable/Document/MappedSuperclass',
            __DIR__.'/Loggable/Document/MappedSuperclass',
        ]);
        $driverChain->addDriver($annotationDriver, 'Gedmo');
    }

    /**
     * Registers all extension annotations.
     */
    public static function registerAnnotations()
    {
        AnnotationRegistry::registerFile(__DIR__.'/Mapping/Annotation/All.php');
    }

    private static function createAnnotationReader(): AnnotationReader
    {
        $reader = new AnnotationReader();

        if (class_exists(ArrayAdapter::class)) {
            $reader = new PsrCachedReader($reader, new ArrayAdapter());
        } elseif (class_exists(ArrayCache::class)) {
            $reader = new PsrCachedReader($reader, CacheAdapter::wrap(new ArrayCache()));
        }

        return $reader;
    }
}
