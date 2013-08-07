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

if (!is_dir(TESTS_TEMP_DIR)) {
    mkdir(TESTS_TEMP_DIR);
}

if (!class_exists('PHPUnit_Framework_TestCase') ||
    version_compare(PHPUnit_Runner_Version::id(), '3.7') < 0
) {
    die('PHPUnit framework is required, at least 3.7 version');
}

if (!class_exists('PHPUnit_Framework_MockObject_MockBuilder')) {
    die('PHPUnit MockObject plugin is required, at least 1.0.9 version');
}

/** @var $loader ClassLoader */
$loader = require __DIR__ . '/../vendor/autoload.php';

// test tools
$loader->add('Gedmo\TestTool', __DIR__);
// fixtures
$loader->add('Gedmo\Fixture', __DIR__);

AnnotationRegistry::registerLoader(array($loader, 'loadClass'));
Gedmo\DoctrineExtensions::registerAnnotations();

$reader = new AnnotationReader();
$reader = new CachedReader($reader, new ArrayCache());
$_ENV['annotation_reader'] = $reader;

