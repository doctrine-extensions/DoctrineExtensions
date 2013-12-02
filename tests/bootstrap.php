<?php

use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Cache\ArrayCache;
use Composer\Autoload\ClassLoader;

/**
 * This is bootstrap for phpUnit unit tests,
 * use README.md for more details
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Christoph Krämer <cevou@gmx.de>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

define('TESTS_PATH', __DIR__);
define('TESTS_TEMP_DIR', __DIR__ . '/temp');
define('VENDOR_PATH', realpath(__DIR__ . '/../vendor'));

if (!class_exists('PHPUnit_Framework_TestCase') ||
    version_compare(PHPUnit_Runner_Version::id(), '3.5') < 0
) {
    die('PHPUnit framework is required, at least 3.5 version');
}

if (!class_exists('PHPUnit_Framework_MockObject_MockBuilder')) {
    die('PHPUnit MockObject plugin is required, at least 1.0.8 version');
}

/** @var $loader ClassLoader */
$loader = require __DIR__ . '/../vendor/autoload.php';

$loader->add('Gedmo\\Mapping\\Mock', __DIR__);
$loader->add('Tool', __DIR__ . '/Gedmo');
// fixture namespaces
$loader->add('Translator\\Fixture', __DIR__ . '/Gedmo');
$loader->add('Translatable\\Fixture', __DIR__ . '/Gedmo');
$loader->add('Timestampable\\Fixture', __DIR__ . '/Gedmo');
$loader->add('Blameable\\Fixture', __DIR__.'/Gedmo');
$loader->add('IpTraceable\\Fixture', __DIR__.'/Gedmo');
$loader->add('Tree\\Fixture', __DIR__ . '/Gedmo');
$loader->add('Sluggable\\Fixture', __DIR__ . '/Gedmo');
$loader->add('Sortable\\Fixture', __DIR__ . '/Gedmo');
$loader->add('Mapping\\Fixture', __DIR__ . '/Gedmo');
$loader->add('Loggable\\Fixture', __DIR__ . '/Gedmo');
$loader->add('SoftDeleteable\\Fixture', __DIR__ . '/Gedmo');
$loader->add('Uploadable\\Fixture', __DIR__ . '/Gedmo');
$loader->add('Wrapper\\Fixture', __DIR__ . '/Gedmo');
$loader->add('ReferenceIntegrity\\Fixture', __DIR__ . '/Gedmo');
$loader->add('References\\Fixture', __DIR__ . '/Gedmo');
// stubs
$loader->add('Gedmo\\Uploadable\\Stub', __DIR__);

AnnotationRegistry::registerLoader(array($loader, 'loadClass'));
Gedmo\DoctrineExtensions::registerAnnotations();

$reader = new AnnotationReader();
$reader = new CachedReader($reader, new ArrayCache());
$_ENV['annotation_reader'] = $reader;
