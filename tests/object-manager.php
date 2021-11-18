<?php

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\PsrCachedReader;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/*
 * This is bootstrap for phpUnit unit tests,
 * use README.md for more details
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Christoph Krämer <cevou@gmx.de>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

$reader = new AnnotationReader();
$reader = new PsrCachedReader($reader, new ArrayAdapter());

$config = new Configuration();
$config->setProxyDir(TESTS_TEMP_DIR);
$config->setProxyNamespace('Proxy');
$config->setMetadataDriverImpl(new AnnotationDriver($reader));

$conn = [
    'driver' => 'pdo_sqlite',
    'memory' => true,
];

return EntityManager::create($conn, $config);
