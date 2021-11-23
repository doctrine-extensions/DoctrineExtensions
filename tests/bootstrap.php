<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\PsrCachedReader;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/*
 * This is bootstrap for phpUnit unit tests,
 * use README.md for more details
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Christoph Krämer <cevou@gmx.de>
 * @link http://www.gediminasm.org
 */

define('TESTS_PATH', __DIR__);
define('TESTS_TEMP_DIR', sys_get_temp_dir().'/doctrine-extension-tests');
define('VENDOR_PATH', realpath(dirname(__DIR__).'/vendor'));

$loader = require dirname(__DIR__).'/vendor/autoload.php';

AnnotationRegistry::registerLoader([$loader, 'loadClass']);
Gedmo\DoctrineExtensions::registerAnnotations();

$reader = new AnnotationReader();
$reader = new PsrCachedReader($reader, new ArrayAdapter());
$_ENV['annotation_reader'] = $reader;
