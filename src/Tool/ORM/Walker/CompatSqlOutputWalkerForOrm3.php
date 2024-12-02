<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tool\ORM\Walker;

use Doctrine\ORM\Query\AST\DeleteStatement;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\AST\UpdateStatement;
use Doctrine\ORM\Query\Exec\SqlFinalizer;
use Doctrine\ORM\Query\SqlOutputWalker;

/**
 * Helper trait to address compatibility issues between ORM 2.x and 3.x.
 *
 * @mixin SqlOutputWalker
 *
 * @internal
 */
trait CompatSqlOutputWalkerForOrm3
{
    public function getFinalizer(DeleteStatement|UpdateStatement|SelectStatement $AST): SqlFinalizer
    {
        return $this->doGetFinalizerWithCompat($AST);
    }

    /**
     * @param DeleteStatement|UpdateStatement|SelectStatement $AST
     */
    protected function doGetFinalizerWithCompat($AST): SqlFinalizer
    {
        return parent::getFinalizer($AST);
    }
}
