<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// Contains all annotations for extensions
// NOTE: should be included with require_once
foreach (glob(__DIR__.'/*.php') as $filename) {
    if ('All' === basename($filename, '.php')) {
        continue;
    }
    include_once $filename;
}
