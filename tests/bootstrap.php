<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\PsrCachedReader;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Symfony\Bridge\Doctrine\Types\UuidType;
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

require dirname(__DIR__).'/vendor/autoload.php';

if (class_exists(AnnotationReader::class)) {
    $_ENV['annotation_reader'] = new PsrCachedReader(new AnnotationReader(), new ArrayAdapter());
}

Type::addType('uuid', UuidType::class);

// With ORM 3 and `doctrine/annotations` installed together, have the annotations library ignore the ORM's mapping namespace
if (!class_exists(AnnotationDriver::class) && class_exists(AnnotationReader::class)) {
    AnnotationReader::addGlobalIgnoredNamespace('Doctrine\ORM\Mapping');
}
