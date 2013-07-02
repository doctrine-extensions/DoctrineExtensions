<?php

namespace Gedmo;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\ORM\Mapping\Driver as DriverORM;
use Doctrine\ODM\MongoDB\Mapping\Driver as DriverMongodbODM;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Cache\ArrayCache;

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
    const VERSION = '2.3.6';

    /**
     * Hooks all extensions metadata mapping drivers
     * into given $driverChain of drivers for ORM
     *
     * @param \Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain $driverChain
     * @param \Doctrine\Common\Annotations\Reader $reader
     */
    public static function registerMappingIntoDriverChainORM(MappingDriverChain $driverChain, Reader $reader = null)
    {
        self::registerAnnotations();
        if (!$reader) {
            $reader = new CachedReader(new AnnotationReader, new ArrayCache);
        }
        $annotationDriver = new DriverORM\AnnotationDriver($reader, array(
            __DIR__.'/Loggable/Entity',
            __DIR__.'/Tree/Entity',
        ));
        $driverChain->addDriver($annotationDriver, 'Gedmo');
    }

    /**
     * Hooks only superclass metadata mapping drivers
     * into given $driverChain of drivers for ORM
     *
     * @param \Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain $driverChain
     * @param \Doctrine\Common\Annotations\Reader $reader
     */
    public static function registerAbstractMappingIntoDriverChainORM(MappingDriverChain $driverChain, Reader $reader = null)
    {
        self::registerAnnotations();
        if (!$reader) {
            $reader = new CachedReader(new AnnotationReader, new ArrayCache);
        }
        $annotationDriver = new DriverORM\AnnotationDriver($reader, array(
            __DIR__.'/Translatable/Entity/MappedSuperclass',
            __DIR__.'/Loggable/Entity/MappedSuperclass',
            __DIR__.'/Tree/Entity/MappedSuperclass',
        ));
        $driverChain->addDriver($annotationDriver, 'Gedmo');
    }

    /**
     * Hooks all extensions metadata mapping drivers
     * into given $driverChain of drivers for ODM MongoDB
     *
     * @param \Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain $driverChain
     * @param \Doctrine\Common\Annotations\Reader $reader
     */
    public static function registerMappingIntoDriverChainMongodbODM(MappingDriverChain $driverChain, Reader $reader = null)
    {
        self::registerAnnotations();
        if (!$reader) {
            $reader = new CachedReader(new AnnotationReader, new ArrayCache);
        }
        $annotationDriver = new DriverMongodbODM\AnnotationDriver($reader, array(
            __DIR__.'/Loggable/Document',
        ));
        $driverChain->addDriver($annotationDriver, 'Gedmo');
    }

    /**
     * Hooks only superclass metadata mapping drivers
     * into given $driverChain of drivers for ODM MongoDB
     *
     * @param \Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain $driverChain
     * @param \Doctrine\Common\Annotations\Reader $reader
     */
    public static function registerAbstractMappingIntoDriverChainMongodbODM(MappingDriverChain $driverChain, Reader $reader = null)
    {
        self::registerAnnotations();
        if (!$reader) {
            $reader = new CachedReader(new AnnotationReader, new ArrayCache);
        }
        $annotationDriver = new DriverMongodbODM\AnnotationDriver($reader, array(
            __DIR__.'/Translatable/Document/MappedSuperclass',
            __DIR__.'/Loggable/Document/MappedSuperclass',
        ));
        $driverChain->addDriver($annotationDriver, 'Gedmo');
    }

    /**
     * Includes all extension annotations once
     */
    public static function registerAnnotations()
    {
        AnnotationRegistry::registerFile(__DIR__.'/Mapping/Annotation/All.php');
    }
}
