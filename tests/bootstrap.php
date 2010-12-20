<?php
/**
 * This is bootstrap for phpUnit unit tests,
 * make sure that your doctrine library structure looks like:
 * /Doctrine
 *      /ORM
 *      /DBAL
 *      /Common
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Tests
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

if (!defined('DOCTRINE_LIBRARY_PATH')) {
    die('path to doctrine library must be defined in phpunit.xml configuration');
}

// if empty string given, assume its in include path allready
if (strlen(DOCTRINE_LIBRARY_PATH)) {
    set_include_path(implode(PATH_SEPARATOR, array(
        realpath(DOCTRINE_LIBRARY_PATH),
        get_include_path(),
    )));
}

!defined('DS') && define('DS', DIRECTORY_SEPARATOR);
!defined('TESTS_PATH') && define('TESTS_PATH', __DIR__);

$classLoaderFile = 'Doctrine/Common/ClassLoader.php';
if (strlen(DOCTRINE_LIBRARY_PATH)) {
    $classLoaderFile = DOCTRINE_LIBRARY_PATH . DS . $classLoaderFile;
    if (!file_exists($classLoaderFile)) {
        die('cannot find doctrine classloader, check the library path');
    }
}
require_once $classLoaderFile;
$classLoader = new Doctrine\Common\ClassLoader('Doctrine');
$classLoader->register();

$classLoader = new Doctrine\Common\ClassLoader('Symfony', 'Doctrine');
$classLoader->register();
      
$classLoader = new Doctrine\Common\ClassLoader('Gedmo', __DIR__ . '/../lib');
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