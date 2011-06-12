#!/usr/bin/env php
<?php

// dependent libraries for test environment

define('VENDOR_PATH', __DIR__ . '/../vendor');

if (!is_dir(VENDOR_PATH)) {
    mkdir(VENDOR_PATH, 0775, true);
}

$deps = array(
    array('doctrine-orm', 'http://github.com/doctrine/doctrine2.git', '35a318148cd891347f489e64140b724beb267849'),
    array('doctrine-dbal', 'http://github.com/doctrine/dbal.git', 'b21728a1322d1d45c2fc2f1fb3942f9dd7c56199'),
    array('doctrine-common', 'http://github.com/doctrine/common.git', 'ffd9dc7460bb90ebfe1f98388e5eceb03a934e9a'),
    array('doctrine-mongodb', 'http://github.com/doctrine/mongodb.git', '2e45a311c88a0498f3585b321913621cc3771f12'),
    array('doctrine-mongodb-odm', 'http://github.com/doctrine/mongodb-odm.git', 'ee0dd4811b2295d95291451f1b77e82509055568'),

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
