<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tool\ORM\Walker;

use Doctrine\ORM\Query\SqlOutputWalker;
use Doctrine\ORM\Query\SqlWalker;

if (class_exists(SqlOutputWalker::class)) {
    if ((new \ReflectionClass(SqlWalker::class))->getMethod('getExecutor')->hasReturnType()) {
        /**
         * Helper trait to address compatibility issues between ORM 2.x and 3.x.
         *
         * @internal
         */
        abstract class CompatSqlOutputWalker extends SqlOutputWalker
        {
            use CompatSqlOutputWalkerForOrm3;
        }
    } else {
        /**
         * Helper trait to address compatibility issues between ORM 2.x and 3.x.
         *
         * @internal
         */
        abstract class CompatSqlOutputWalker extends SqlOutputWalker
        {
            use CompatSqlOutputWalkerForOrm2;
        }
    }
} else {
    if ((new \ReflectionClass(SqlWalker::class))->getMethod('getExecutor')->hasReturnType()) {
        /**
         * Helper trait to address compatibility issues between ORM 2.x and 3.x.
         *
         * @internal
         */
        abstract class CompatSqlOutputWalker extends SqlWalker
        {
            use CompatSqlOutputWalkerForOrm3;
        }
    } else {
        /**
         * Helper trait to address compatibility issues between ORM 2.x and 3.x.
         *
         * @internal
         */
        abstract class CompatSqlOutputWalker extends SqlWalker
        {
            use CompatSqlOutputWalkerForOrm2;
        }
    }
}
