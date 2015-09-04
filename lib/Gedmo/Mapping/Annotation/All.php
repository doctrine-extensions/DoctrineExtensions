<?php

/**
* Contains all annotations for extensions
* NOTE: should be included with require_once
*
* @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
* @license MIT License (http://www.opensource.org/licenses/mit-license.php)
*/
$files = glob(__DIR__ . "/*.php");
asort($files);
foreach ($files as $filename) {
    if (basename($filename, '.php') === 'All') {
        continue;
    }
    include $filename;
}
