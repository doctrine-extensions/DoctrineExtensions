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
use Doctrine\ORM\Query\Exec\AbstractSqlExecutor;
use Doctrine\ORM\Query\Exec\SingleTableDeleteUpdateExecutor;
use Doctrine\ORM\Query\SqlWalker;
use Gedmo\SoftDeleteable\Query\TreeWalker\Exec\MultiTableDeleteExecutor;
use Gedmo\SoftDeleteable\SoftDeleteableListener;

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
     * @var array
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

    /**
     * @var QuoteStrategy
     */
    private $quoteStrategy;

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
     * @return AbstractSqlExecutor
     */
    public function getExecutor($AST)
    {
        switch (true) {
            case $AST instanceof DeleteStatement:
                $primaryClass = $this->getEntityManager()->getClassMetadata($AST->deleteClause->abstractSchemaName);

                return $primaryClass->isInheritanceTypeJoined()
                    ? new MultiTableDeleteExecutor($AST, $this, $this->meta, $this->getConnection()->getDatabasePlatform(), $this->configuration)
                    : new SingleTableDeleteUpdateExecutor($AST, $this);
            default:
                throw new \Gedmo\Exception\UnexpectedValueException('SoftDeleteable walker should be used only on delete statement');
        }
    }

    /**
     * Change a DELETE clause for an UPDATE clause
     *
     * @return string the SQL
     */
    public function walkDeleteClause(DeleteClause $deleteClause)
    {
        $em = $this->getEntityManager();
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
     * @throws \Gedmo\Exception\RuntimeException if listener is not found
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
                throw new \Gedmo\Exception\RuntimeException('The SoftDeleteable listener could not be found.');
            }
        }

        return $this->listener;
    }

    /**
     * Search for components in the delete clause
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
