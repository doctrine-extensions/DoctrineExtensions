<?php

/**
* Contains all annotations for extensions
*
* @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
* @license MIT License (http://www.opensource.org/licenses/mit-license.php)
*/
foreach (glob(__DIR__ . "/*.php") as $filename) {
    if (basename($filename, '.php') === 'All') {
        continue;
    }
    include_once $filename;
}
