<?php
/**
 * This is bootstrap for phpUnit unit tests,
 * use README.md for more details
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Tests
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

define('TESTS_PATH', __DIR__);
define('VENDOR_PATH', realpath(__DIR__ . '/../vendor'));

set_include_path(implode(PATH_SEPARATOR, array(
    VENDOR_PATH,
    get_include_path(),
)));

$classLoaderFile = VENDOR_PATH . '/doctrine-common/lib/Doctrine/Common/ClassLoader.php';
if (!file_exists($classLoaderFile)) {
    die('cannot find vendor, git submodule init && git submodule update');
}

require_once $classLoaderFile;
$classLoader = new Doctrine\Common\ClassLoader(
    'Doctrine\ORM', 'doctrine-orm/lib' 
);
$classLoader->register();

$classLoader = new Doctrine\Common\ClassLoader(
    'Doctrine\DBAL', 'doctrine-dbal/lib' 
);
$classLoader->register();

$classLoader = new Doctrine\Common\ClassLoader(
    'Doctrine\MongoDB', 'doctrine-mongodb/lib' 
);
$classLoader->register();

$classLoader = new Doctrine\Common\ClassLoader(
    'Doctrine\ODM', 'doctrine-mongodb-odm/lib' 
);
$classLoader->register();

$classLoader = new Doctrine\Common\ClassLoader(
    'Doctrine', 'doctrine-common/lib' 
);
$classLoader->register();

$classLoader = new Doctrine\Common\ClassLoader('Symfony');
$classLoader->register();

$classLoader = new Doctrine\Common\ClassLoader('Gedmo', __DIR__ . '/../lib');
$classLoader->register();

$classLoader = new Doctrine\Common\ClassLoader('Tool', __DIR__ . '/Gedmo');
$classLoader->register();

// fixture autoloaders
$classLoader = new Doctrine\Common\ClassLoader('Translatable\Fixture', __DIR__ . '/Gedmo');
$classLoader->register();

$classLoader = new Doctrine\Common\ClassLoader('Tree\Fixture', __DIR__ . '/Gedmo');
$classLoader->register();

$classLoader = new Doctrine\Common\ClassLoader('Timestampable\Fixture', __DIR__ . '/Gedmo');
$classLoader->register();

$classLoader = new Doctrine\Common\ClassLoader('Sluggable\Fixture', __DIR__ . '/Gedmo');
$classLoader->register();

$classLoader = new Doctrine\Common\ClassLoader('Mapping\Fixture', __DIR__ . '/Gedmo');
$classLoader->register();

$classLoader = new Doctrine\Common\ClassLoader('Loggable\Fixture', __DIR__ . '/Gedmo');
$classLoader->register();