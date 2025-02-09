<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tool\ORM\Walker;

use Doctrine\ORM\Query\AST\DeleteClause;
use Doctrine\ORM\Query\AST\DeleteStatement;
use Doctrine\ORM\Query\AST\FromClause;
use Doctrine\ORM\Query\AST\GroupByClause;
use Doctrine\ORM\Query\AST\HavingClause;
use Doctrine\ORM\Query\AST\OrderByClause;
use Doctrine\ORM\Query\AST\SelectClause;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\AST\SimpleSelectClause;
use Doctrine\ORM\Query\AST\SubselectFromClause;
use Doctrine\ORM\Query\AST\UpdateStatement;
use Doctrine\ORM\Query\AST\WhereClause;
use Doctrine\ORM\Query\Exec\AbstractSqlExecutor;
use Doctrine\ORM\Query\Exec\SqlFinalizer;
use Doctrine\ORM\Query\SqlWalker;

/**
 * Helper trait to address compatibility issues between ORM 2.x and 3.x.
 *
 * @mixin SqlWalker
 *
 * @internal
 */
trait SqlWalkerCompatForOrm2
{
    /**
     * Gets an executor that can be used to execute the result of this walker.
     *
     * @param SelectStatement|UpdateStatement|DeleteStatement $statement
     *
     * @return AbstractSqlExecutor
     */
    public function getExecutor($statement)
    {
        return $this->doGetExecutorWithCompat($statement);
    }

    /**
     * @param DeleteStatement|UpdateStatement|SelectStatement $AST
     */
    public function getFinalizer($AST): SqlFinalizer
    {
        return $this->doGetFinalizerWithCompat($AST);
    }

    /**
     * Walks down a SelectClause AST node, thereby generating the appropriate SQL.
     *
     * @param SelectClause $selectClause
     *
     * @return string
     */
    public function walkSelectClause($selectClause)
    {
        return $this->doWalkSelectClauseWithCompat($selectClause);
    }

    /**
     * Walks down a FromClause AST node, thereby generating the appropriate SQL.
     *
     * @param FromClause $fromClause
     *
     * @return string
     */
    public function walkFromClause($fromClause)
    {
        return $this->doWalkFromClauseWithCompat($fromClause);
    }

    /**
     * Walks down a OrderByClause AST node, thereby generating the appropriate SQL.
     *
     * @param OrderByClause $orderByClause
     *
     * @return string
     */
    public function walkOrderByClause($orderByClause)
    {
        return $this->doWalkOrderByClauseWithCompat($orderByClause);
    }

    /**
     * Walks down a HavingClause AST node, thereby generating the appropriate SQL.
     *
     * @param HavingClause $havingClause
     *
     * @return string
     */
    public function walkHavingClause($havingClause)
    {
        return $this->doWalkHavingClauseWithCompat($havingClause);
    }

    /**
     * Walks down a SubselectFromClause AST node, thereby generating the appropriate SQL.
     *
     * @param SubselectFromClause $subselectFromClause
     *
     * @return string
     */
    public function walkSubselectFromClause($subselectFromClause)
    {
        return $this->doWalkSubselectFromClauseWithCompat($subselectFromClause);
    }

    /**
     * Walks down a SimpleSelectClause AST node, thereby generating the appropriate SQL.
     *
     * @param SimpleSelectClause $simpleSelectClause
     *
     * @return string
     */
    public function walkSimpleSelectClause($simpleSelectClause)
    {
        return $this->doWalkSimpleSelectClauseWithCompat($simpleSelectClause);
    }

    /**
     * Walks down a GroupByClause AST node, thereby generating the appropriate SQL.
     *
     * @param GroupByClause $groupByClause
     *
     * @return string
     */
    public function walkGroupByClause($groupByClause)
    {
        return $this->doWalkGroupByClauseWithCompat($groupByClause);
    }

    /**
     * Walks down a DeleteClause AST node, thereby generating the appropriate SQL.
     *
     * @param DeleteClause $deleteClause
     *
     * @return string
     */
    public function walkDeleteClause($deleteClause)
    {
        return $this->doWalkDeleteClauseWithCompat($deleteClause);
    }

    /**
     * Walks down a WhereClause AST node, thereby generating the appropriate SQL.
     *
     * WhereClause or not, the appropriate discriminator sql is added.
     *
     * @param WhereClause|null $whereClause
     *
     * @return string
     */
    public function walkWhereClause($whereClause)
    {
        return $this->doWalkWhereClauseWithCompat($whereClause);
    }

    /**
     * Gets an executor that can be used to execute the result of this walker.
     *
     * @param SelectStatement|UpdateStatement|DeleteStatement $statement
     */
    protected function doGetExecutorWithCompat($statement): AbstractSqlExecutor
    {
        return parent::getExecutor($statement);
    }

    /**
     * @param DeleteStatement|UpdateStatement|SelectStatement $AST
     */
    protected function doGetFinalizerWithCompat($AST): SqlFinalizer
    {
        return parent::getFinalizer($AST);
    }

    protected function doWalkSelectClauseWithCompat(SelectClause $selectClause): string
    {
        return parent::walkSelectClause($selectClause);
    }

    protected function doWalkFromClauseWithCompat(FromClause $fromClause): string
    {
        return parent::walkFromClause($fromClause);
    }

    protected function doWalkOrderByClauseWithCompat(OrderByClause $orderByClause): string
    {
        return parent::walkOrderByClause($orderByClause);
    }

    protected function doWalkHavingClauseWithCompat(HavingClause $havingClause): string
    {
        return parent::walkHavingClause($havingClause);
    }

    protected function doWalkSubselectFromClauseWithCompat(SubselectFromClause $subselectFromClause): string
    {
        return parent::walkSubselectFromClause($subselectFromClause);
    }

    protected function doWalkSimpleSelectClauseWithCompat(SimpleSelectClause $simpleSelectClause): string
    {
        return parent::walkSimpleSelectClause($simpleSelectClause);
    }

    protected function doWalkGroupByClauseWithCompat(GroupByClause $groupByClause): string
    {
        return parent::walkGroupByClause($groupByClause);
    }

    protected function doWalkDeleteClauseWithCompat(DeleteClause $deleteClause): string
    {
        return parent::walkDeleteClause($deleteClause);
    }

    protected function doWalkWhereClauseWithCompat(?WhereClause $whereClause): string
    {
        return parent::walkWhereClause($whereClause);
    }
}
