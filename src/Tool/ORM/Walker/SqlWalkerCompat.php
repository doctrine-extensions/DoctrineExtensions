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
    /**
     * Helper trait to address compatibility issues between ORM 2.x and 3.x.
     *
     * @mixin SqlWalker
     *
     * @internal
     */
    trait SqlWalkerCompat
    {
        use SqlWalkerCompatForOrm3;
    }
} else {
    /**
     * Helper trait to address compatibility issues between ORM 2.x and 3.x.
     *
     * @mixin SqlWalker
     *
     * @internal
     */
    trait SqlWalkerCompat
    {
        use SqlWalkerCompatForOrm2;
    }
}
