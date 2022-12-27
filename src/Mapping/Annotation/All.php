<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

@trigger_error(sprintf(
    'Requiring the file at "%s" is deprecated since gedmo/doctrine-extensions 3.11, this file will be removed in version 4.0.',
    __FILE__
), E_USER_DEPRECATED);

// Contains all annotations for extensions
// NOTE: should be included with require_once
foreach (glob(__DIR__.'/*.php') as $filename) {
    if ('All' === basename($filename, '.php')) {
        continue;
    }
    include_once $filename;
}
