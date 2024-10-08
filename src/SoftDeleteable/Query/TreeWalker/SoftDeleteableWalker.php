<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\SoftDeleteable\Query\TreeWalker;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\QuoteStrategy;
use Doctrine\ORM\Query\AST\DeleteClause;
use Doctrine\ORM\Query\AST\DeleteStatement;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\AST\UpdateStatement;
use Doctrine\ORM\Query\Exec\AbstractSqlExecutor;
use Doctrine\ORM\Query\Exec\SingleTableDeleteUpdateExecutor;
use Doctrine\ORM\Query\SqlWalker;
use Gedmo\Exception\RuntimeException;
use Gedmo\Exception\UnexpectedValueException;
use Gedmo\SoftDeleteable\Query\TreeWalker\Exec\MultiTableDeleteExecutor;
use Gedmo\SoftDeleteable\SoftDeleteableListener;
use Gedmo\Tool\ORM\Walker\SqlWalkerCompat;

/**
 * This SqlWalker is needed when you need to use a DELETE DQL query.
 * It will update the "deletedAt" field with the actual date, instead
 * of actually deleting it.
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @final since gedmo/doctrine-extensions 3.11
 */
class SoftDeleteableWalker extends SqlWalker
{
    use SqlWalkerCompat;

    /**
     * @var Connection
     *
     * @deprecated to be removed in 4.0, use the `getConnection()` method instead.
     */
    protected $conn;

    /**
     * @var AbstractPlatform
     *
     * @deprecated to be removed in 4.0, fetch the platform from the connection instead
     */
    protected $platform;

    /**
     * @var SoftDeleteableListener
     */
    protected $listener;

    /**
     * @var array<string, mixed>
     */
    protected $configuration;

    /**
     * @var string|null
     *
     * @deprecated to be removed in 4.0, unused
     */
    protected $alias;

    /**
     * @var string
     */
    protected $deletedAtField;

    /**
     * @var ClassMetadata
     */
    protected $meta;

    private QuoteStrategy $quoteStrategy;

    public function __construct($query, $parserResult, array $queryComponents)
    {
        parent::__construct($query, $parserResult, $queryComponents);

        $this->conn = $this->getConnection();
        $this->platform = $this->getConnection()->getDatabasePlatform();
        $this->listener = $this->getSoftDeleteableListener();
        $this->quoteStrategy = $this->getEntityManager()->getConfiguration()->getQuoteStrategy();

        $this->extractComponents($this->getQueryComponents());
    }

    /**
     * @param SelectStatement|UpdateStatement|DeleteStatement $statement
     *
     * @throws UnexpectedValueException when an unsupported AST statement is given
     */
    protected function doGetExecutorWithCompat($statement): AbstractSqlExecutor
    {
        switch (true) {
            case $statement instanceof DeleteStatement:
                assert(class_exists($statement->deleteClause->abstractSchemaName));

                $primaryClass = $this->getEntityManager()->getClassMetadata($statement->deleteClause->abstractSchemaName);

                return $primaryClass->isInheritanceTypeJoined()
                    ? new MultiTableDeleteExecutor($statement, $this, $this->meta, $this->getConnection()->getDatabasePlatform(), $this->configuration)
                    : new SingleTableDeleteUpdateExecutor($statement, $this);
            default:
                throw new UnexpectedValueException('SoftDeleteable walker should be used only on delete statement');
        }
    }

    /**
     * Changes a DELETE clause into an UPDATE clause for a soft-deleteable entity.
     */
    protected function doWalkDeleteClauseWithCompat(DeleteClause $deleteClause): string
    {
        $em = $this->getEntityManager();

        assert(class_exists($deleteClause->abstractSchemaName));

        $class = $em->getClassMetadata($deleteClause->abstractSchemaName);
        $tableName = $class->getTableName();
        $this->setSQLTableAlias($tableName, $tableName, $deleteClause->aliasIdentificationVariable);

        $platform = $this->getConnection()->getDatabasePlatform();

        $quotedTableName = $this->quoteStrategy->getTableName($class, $platform);
        $quotedColumnName = $this->quoteStrategy->getColumnName($this->deletedAtField, $class, $platform);

        return 'UPDATE '.$quotedTableName.' SET '.$quotedColumnName.' = '.$platform->getCurrentTimestampSQL();
    }

    /**
     * Get the currently used SoftDeleteableListener
     *
     * @throws RuntimeException if listener is not found
     */
    private function getSoftDeleteableListener(): SoftDeleteableListener
    {
        if (null === $this->listener) {
            $em = $this->getEntityManager();

            foreach ($em->getEventManager()->getAllListeners() as $listeners) {
                foreach ($listeners as $listener) {
                    if ($listener instanceof SoftDeleteableListener) {
                        $this->listener = $listener;

                        break 2;
                    }
                }
            }

            if (null === $this->listener) {
                throw new RuntimeException('The SoftDeleteable listener could not be found.');
            }
        }

        return $this->listener;
    }

    /**
     * Search for components in the delete clause
     *
     * @param array<string, array<string, mixed>> $queryComponents
     */
    private function extractComponents(array $queryComponents): void
    {
        $em = $this->getEntityManager();

        foreach ($queryComponents as $comp) {
            $meta = $comp['metadata'];
            $config = $this->listener->getConfiguration($em, $meta->getName());
            if ($config && isset($config['softDeleteable']) && $config['softDeleteable']) {
                $this->configuration = $config;
                $this->deletedAtField = $config['fieldName'];
                $this->meta = $meta;
            }
        }
    }
}
