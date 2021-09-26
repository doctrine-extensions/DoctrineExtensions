<?php

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
 * Version class allows to checking the dependencies required
 * and the current version of doctrine extensions
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class DoctrineExtensions
{
    /**
     * Current version of extensions
     */
    public const VERSION = '3.1.0';

    /**
     * Hooks all extensions metadata mapping drivers
     * into given $driverChain of drivers for ORM
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
     * Hooks only superclass metadata mapping drivers
     * into given $driverChain of drivers for ORM
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
     * Hooks all extensions metadata mapping drivers
     * into given $driverChain of drivers for ODM MongoDB
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
     * Hooks only superclass metadata mapping drivers
     * into given $driverChain of drivers for ODM MongoDB
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
     * Includes all extension annotations once
     */
    public static function registerAnnotations()
    {
        AnnotationRegistry::registerFile(__DIR__.'/Mapping/Annotation/All.php');
    }

    private static function createAnnotationReader()
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
