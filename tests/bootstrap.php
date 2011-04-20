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
define('TESTS_TEMP_DIR', __DIR__.'/temp');
define('VENDOR_PATH', realpath(__DIR__ . '/../vendor'));

$classLoaderFile = VENDOR_PATH . '/Symfony/Component/ClassLoader/UniversalClassLoader.php';

require_once $classLoaderFile;
$loader = new Symfony\Component\ClassLoader\UniversalClassLoader;
$loader->registerNamespaces(array(
    'Symfony'                    => VENDOR_PATH,
    'Doctrine\\MongoDB'          => VENDOR_PATH.'/doctrine-mongodb/lib',
    'Doctrine\\ODM\\MongoDB'     => VENDOR_PATH.'/doctrine-mongodb-odm/lib',
    'Doctrine\\Common'           => VENDOR_PATH.'/doctrine-common/lib',
    'Doctrine\\DBAL'             => VENDOR_PATH.'/doctrine-dbal/lib',
    'Doctrine\\ORM'              => VENDOR_PATH.'/doctrine-orm/lib',
    'Gedmo\\Mapping\\Mock'       => __DIR__,
    'Gedmo'                      => __DIR__.'/../lib',
    'Tool'                       => __DIR__.'/Gedmo',
    // fixture namespaces
    'Translatable\\Fixture'      => __DIR__.'/Gedmo',
    'Timestampable\\Fixture'     => __DIR__.'/Gedmo',
    'Tree\\Fixture'              => __DIR__.'/Gedmo',
    'Sluggable\\Fixture'         => __DIR__.'/Gedmo',
    'Mapping\\Fixture'           => __DIR__.'/Gedmo',
    'Loggable\\Fixture'          => __DIR__.'/Gedmo',
));
$loader->register();

