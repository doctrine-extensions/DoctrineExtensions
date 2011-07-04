#!/usr/bin/env php
<?php

// dependent libraries for test environment

define('VENDOR_PATH', __DIR__ . '/../vendor');

if (!is_dir(VENDOR_PATH)) {
    mkdir(VENDOR_PATH, 0775, true);
}

$deps = array(
    array('doctrine-orm', 'http://github.com/doctrine/doctrine2.git', '550fcbc17fc9d927edf3'),
    array('doctrine-dbal', 'http://github.com/doctrine/dbal.git', 'eb80a3797e80fbaa024bb0a1ef01c3d81bb68a76'),
    array('doctrine-common', 'http://github.com/doctrine/common.git', '73b61b50782640358940'),
    array('doctrine-mongodb', 'http://github.com/doctrine/mongodb.git', '4109734e249a951f270c531999871bfe9eeed843'),
    array('doctrine-mongodb-odm', 'http://github.com/doctrine/mongodb-odm.git', '8fb97a4740c2c12a2a5a4e7d78f0717847c39691'),

    array('Symfony/Component/ClassLoader', 'http://github.com/symfony/ClassLoader.git', '6894a17bb88831f2d260c7b9897862e5ccf35bae'),
    array('Symfony/Component/Console', 'http://github.com/symfony/Console.git', '55344823ce1c2a780c9137d86143d9084209a02d'),
    array('Symfony/Component/Finder', 'http://github.com/symfony/Finder.git', '83d148b10f3acf2a1d1cc427386a1d3d1a125206'),
    array('Symfony/Component/Yaml', 'http://github.com/symfony/Yaml.git', '2b858b077d1e6748569fd143ae16da44b541d3f3'),
);

foreach ($deps as $dep) {
    list($name, $url, $rev) = $dep;

    echo "> Installing/Updating $name\n";

    $installDir = VENDOR_PATH.'/'.$name;
    if (!is_dir($installDir)) {
        system(sprintf('git clone %s %s', $url, $installDir));
    }

    system(sprintf('cd %s && git fetch origin && git reset --hard %s', $installDir, $rev));
}
