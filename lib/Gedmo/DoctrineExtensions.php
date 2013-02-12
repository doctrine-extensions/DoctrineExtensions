<?php

namespace Gedmo;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\ORM\Mapping\Driver as DriverORM;
use Doctrine\ODM\MongoDB\Mapping\Driver as DriverMongodbODM;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Cache\ArrayCache;

/**
 * Version class allows to checking the dependencies required
 * and the current version of doctrine extensions
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @subpackage DoctrineExtensions
 * @package Gedmo
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
final class DoctrineExtensions
{
    /**
     * Current version of extensions
     */
    const VERSION = '2.3.0-DEV';

    /**
     * Flag if annotations were included into registry
     * already
     *
     * @var boolean
     */
    private static $autoloaded = false;

    /**
     * Hooks all extensions metadata mapping drivers
     * into given $driverChain of drivers for ORM
     *
     * @param \Doctrine\ORM\Mapping\Driver\DriverChain $driverChain
     * @param \Doctrine\Common\Annotations\Reader $reader
     */
    public static function registerMappingIntoDriverChainORM(DriverORM\DriverChain $driverChain, Reader $reader = null)
    {
        self::registerAnnotations();
        if (!$reader) {
            $reader = new CachedReader(new AnnotationReader, new ArrayCache);
        }
        $annotationDriver = new DriverORM\AnnotationDriver($reader, array(
            __DIR__.'/Translatable/Entity',
            __DIR__.'/Loggable/Entity',
            __DIR__.'/Tree/Entity',
        ));
        $driverChain->addDriver($annotationDriver, 'Gedmo');
    }

    /**
     * Hooks only superclass metadata mapping drivers
     * into given $driverChain of drivers for ORM
     *
     * @param \Doctrine\ORM\Mapping\Driver\DriverChain $chain
     * @param \Doctrine\Common\Annotations\Reader $reader
     */
    public static function registerAbstractMappingIntoDriverChainORM(DriverORM\DriverChain $driverChain, Reader $reader = null)
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
     * @param \Doctrine\ODM\MongoDB\Mapping\Driver\DriverChain $driverChain
     * @param \Doctrine\Common\Annotations\Reader $reader
     */
    public static function registerMappingIntoDriverChainMongodbODM(DriverMongodbODM\DriverChain $driverChain, Reader $reader = null)
    {
        self::registerAnnotations();
        if (!$reader) {
            $reader = new CachedReader(new AnnotationReader, new ArrayCache);
        }
        $annotationDriver = new DriverMongodbODM\AnnotationDriver($reader, array(
            __DIR__.'/Translatable/Document',
            __DIR__.'/Loggable/Document',
        ));
        $driverChain->addDriver($annotationDriver, 'Gedmo');
    }

    /**
     * Hooks only superclass metadata mapping drivers
     * into given $driverChain of drivers for ODM MongoDB
     *
     * @param \Doctrine\ODM\MongoDB\Mapping\Driver\DriverChain $driverChain
     * @param \Doctrine\Common\Annotations\Reader $reader
     */
    public static function registerAbstractMappingIntoDriverChainMongodbODM(DriverMongodbODM\DriverChain $driverChain, Reader $reader = null)
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
        if (!self::$autoloaded) {
            AnnotationRegistry::registerFile(__DIR__.'/Mapping/Annotation/All.php');
            self::$autoloaded = true;
        }
    }
}