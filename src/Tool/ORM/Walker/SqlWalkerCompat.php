<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tool\ORM\Walker;

use Doctrine\ORM\Query\SqlWalker;

if ((new \ReflectionClass(SqlWalker::class))->getMethod('getExecutor')->hasReturnType()) {
    // ORM 3.x
    require_once __DIR__.'/orm-3.php';
} else {
    // ORM 2.x
    require_once __DIR__.'/orm-2.php';
}
