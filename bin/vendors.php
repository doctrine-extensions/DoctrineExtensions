#!/usr/bin/env php
<?php

// dependent libraries for test environment

define('VENDOR_PATH', __DIR__ . '/../vendor');

if (!is_dir(VENDOR_PATH)) {
    mkdir(VENDOR_PATH, 0775, true);
}

$deps21x = array(
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
$deps22x = array(
    array('doctrine-orm', 'http://github.com/doctrine/doctrine2.git', 'cfe1259400'),
    array('doctrine-dbal', 'http://github.com/doctrine/dbal.git', '5a827d7c18'),
    array('doctrine-common', 'http://github.com/doctrine/common.git', '06e9f72342'),
    array('doctrine-mongodb', 'http://github.com/doctrine/mongodb.git', 'e8e1e8e474'),
    array('doctrine-mongodb-odm', 'http://github.com/doctrine/mongodb-odm.git', '5a4076ec9c'),

    array('Symfony/Component/ClassLoader', 'http://github.com/symfony/ClassLoader.git', 'v2.0.7'),
    array('Symfony/Component/Console', 'http://github.com/symfony/Console.git', 'v2.0.7'),
    array('Symfony/Component/Finder', 'http://github.com/symfony/Finder.git', 'v2.0.7'),
    array('Symfony/Component/Yaml', 'http://github.com/symfony/Yaml.git', 'v2.0.7'),
);
$deps23x = array(
    array('doctrine-orm', 'git://github.com/doctrine/doctrine2.git', '1b2b831feb'),
    array('doctrine-dbal', 'git://github.com/doctrine/dbal.git', 'd9c3509e8d'),
    array('doctrine-common', 'git://github.com/doctrine/common.git', 'd62352cc72'),
    array('doctrine-mongodb', 'git://github.com/doctrine/mongodb.git', 'd7fdcff25b'),
    array('doctrine-mongodb-odm', 'git://github.com/doctrine/mongodb-odm.git', 'fcff6211db'),

    array('Symfony/Component/ClassLoader', 'git://github.com/symfony/ClassLoader.git', 'v2.0.12'),
    array('Symfony/Component/Console', 'git://github.com/symfony/Console.git', 'v2.0.12'),
    array('Symfony/Component/Finder', 'git://github.com/symfony/Finder.git', 'v2.0.12'),
    array('Symfony/Component/Yaml', 'git://github.com/symfony/Yaml.git', 'v2.0.12'),
);

foreach ($deps23x as $dep) {
    list($name, $url, $rev) = $dep;

    echo "> Installing/Updating $name\n";

    $installDir = VENDOR_PATH.'/'.$name;
    if (!is_dir($installDir)) {
        system(sprintf('git clone %s %s', $url, $installDir));
    }

    system(sprintf('cd %s && git fetch origin && git reset --hard %s', $installDir, $rev));
}
