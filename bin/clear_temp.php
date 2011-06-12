<?php

$location = __DIR__ . '/../tests/temp';
define('VENDOR_PATH', realpath(__DIR__ . '/../vendor'));

set_include_path(implode(PATH_SEPARATOR, array(
    VENDOR_PATH,
    get_include_path(),
)));

$classLoaderFile = VENDOR_PATH . '/doctrine-common/lib/Doctrine/Common/ClassLoader.php';
if (!file_exists($classLoaderFile)) {
    die('cannot find vendor, run: php bin/vendors.php');
}

require_once $classLoaderFile;
$classLoader = new Doctrine\Common\ClassLoader('Symfony');
$classLoader->register();

$finder = new Symfony\Component\Finder\Finder;
$finder->files()
    ->name('*')
    ->in(__DIR__ . '/../tests/temp');

foreach ($finder as $fileInfo) {
    if (!$fileInfo->isWritable()) {
        continue;
    }
    echo 'removing: ' . $fileInfo->getRealPath() . PHP_EOL;
    @unlink($fileInfo->getRealPath());
}