#!/usr/bin/env php
<?php

// dependent libraries for test environment

define('VENDOR_PATH', __DIR__ . '/../vendor');

if (!is_dir(VENDOR_PATH)) {
    mkdir(VENDOR_PATH, 0775, true);
}

$deps = array(
    array('doctrine-orm', 'http://github.com/doctrine/doctrine2.git', 'a4cbb23fc8612587d1886e4c3e7d62d72457a297'),
    array('doctrine-dbal', 'http://github.com/doctrine/dbal.git', 'eb80a3797e80fbaa024bb0a1ef01c3d81bb68a76'),
    array('doctrine-common', 'http://github.com/doctrine/common.git', 'aa00010faa764c49d9bdee5d35fa90aea5c682ee'),
    array('doctrine-mongodb', 'http://github.com/doctrine/mongodb.git', '4109734e249a951f270c531999871bfe9eeed843'),
    array('doctrine-mongodb-odm', 'http://github.com/doctrine/mongodb-odm.git', '6b91d944e68bbf94702a38351f03c74b7d6a057a'),

    array('Symfony/Component/ClassLoader', 'http://github.com/symfony/ClassLoader.git', '86fed40f30a64d0726ed19060d4b872f2feaaf7d'),
    array('Symfony/Component/Console', 'http://github.com/symfony/Console.git', 'd8ccb833b19ca7965fd320737a4dec5f152bb7ef'),
    array('Symfony/Component/Finder', 'http://github.com/symfony/Finder.git', '42709a7857fd46fd67fdb452302f1b0bdcd4eccb'),
    array('Symfony/Component/Yaml', 'http://github.com/symfony/Yaml.git', '9a5dc42f6611d6c103e6c0dc1c4688994fd68a89'),
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
