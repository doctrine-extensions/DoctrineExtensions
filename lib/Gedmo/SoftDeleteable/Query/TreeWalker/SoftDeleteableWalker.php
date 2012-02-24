<?php

namespace Gedmo\SoftDeleteable\Query\TreeWalker;

use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\AST\DeleteStatement;
use Doctrine\ORM\Query\AST\DeleteClause;
use Doctrine\ORM\Query\AST\UpdateClause;
use Doctrine\ORM\Query\AST\UpdateItem;
use Doctrine\ORM\Query\Exec\SingleTableDeleteUpdateExecutor;
use Doctrine\ORM\Query\AST\PathExpression;
use Gedmo\SoftDeleteable\SoftDeleteableListener;

/**
 * Created by Gustavo Falco <comfortablynumb84@gmail.com>
 */

class SoftDeleteableWalker extends SqlWalker
{
    protected $conn;
    protected $platform;
    protected $listener;
    protected $alias;
    protected $deletedAtField;
    protected $meta;
    
    /**
     * {@inheritDoc}
     */
    public function __construct($query, $parserResult, array $queryComponents)
    {
        parent::__construct($query, $parserResult, $queryComponents);
        
        $this->conn = $this->getConnection();
        $this->platform = $this->conn->getDatabasePlatform();
        $this->listener = $this->getSoftDeleteableListener();
        $this->extractComponents($queryComponents);
    }

    /**
     * {@inheritDoc}
     */
    public function getExecutor($AST)
    {
        if (!$AST instanceof DeleteStatement) {
            throw new \Gedmo\Exception\UnexpectedValueException('SoftDeleteable walker should be used only on delete statement');
        }
        
        return parent::getExecutor($AST);
    }

    /**
     * Change a DELETE clause for an UPDATE clause
     *
     * @param DeleteClause
     * @return string The SQL.
     */
    public function walkDeleteClause(DeleteClause $deleteClause)
    {
        $em = $this->getEntityManager();
        $class = $em->getClassMetadata($deleteClause->abstractSchemaName);
        $tableName = $class->getTableName();
        $this->setSQLTableAlias($tableName, $tableName, $deleteClause->aliasIdentificationVariable);
        $quotedTableName = $class->getQuotedTableName($this->platform);
        $quotedColumnName = $class->getQuotedColumnName($this->deletedAtField, $this->platform);
        
        $sql = 'UPDATE '.$quotedTableName.' SET '.$quotedColumnName.' = "'.date('Y-m-d H:i:s').'"';

        return $sql;
    }

    /**
     * Get the currently used SoftDeleteableListener
     *
     * @throws \Gedmo\Exception\RuntimeException - if listener is not found
     * @return SoftDeleteableListener
     */
    private function getSoftDeleteableListener()
    {
        if (is_null($this->listener)) {
            $em = $this->getEntityManager();

            foreach ($em->getEventManager()->getListeners() as $event => $listeners) {
                foreach ($listeners as $hash => $listener) {
                    if ($listener instanceof SoftDeleteableListener) {
                        $this->listener = $listener;
                        break;
                    }
                }
                if ($this->listener) {
                    break;
                }
            }

            if (is_null($this->listener)) {
                throw new \Gedmo\Exception\RuntimeException('The SoftDeleteable listener could not be found.');
            }
        }

        return $this->listener;
    }

    /**
     * Search for components in the delete clause
     *
     * @param array $queryComponents
     * @return void
     */
    private function extractComponents(array $queryComponents)
    {
        $em = $this->getEntityManager();
        
        foreach ($queryComponents as $alias => $comp) {
            if (!isset($comp['metadata'])) {
                continue;
            }
            $meta = $comp['metadata'];
            $config = $this->listener->getConfiguration($em, $meta->name);
            if ($config && isset($config['softDeleteable']) && $config['softDeleteable']) {
                $this->deletedAtField = $config['fieldName'];
                $this->meta = $meta;
            }
        }
    }
}
