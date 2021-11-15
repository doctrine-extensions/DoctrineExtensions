<?php

namespace Gedmo\SoftDeleteable\Query\TreeWalker\Exec;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Exec\MultiTableDeleteExecutor as BaseMultiTableDeleteExecutor;

/**
 * This class is used when a DELETE DQL query is called for entities
 * that are part of an inheritance tree
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class MultiTableDeleteExecutor extends BaseMultiTableDeleteExecutor
{
    /**
     * {@inheritdoc}
     */
    public function __construct(Node $AST, $sqlWalker, ClassMetadataInfo $meta, AbstractPlatform $platform, array $config)
    {
        parent::__construct($AST, $sqlWalker);

        $sqlStatements = $this->_sqlStatements;

        foreach ($sqlStatements as $index => $stmt) {
            $matches = [];
            preg_match('/DELETE FROM (\w+) .+/', $stmt, $matches);

            if (isset($matches[1]) && $meta->getQuotedTableName($platform) === $matches[1]) {
                $sqlStatements[$index] = str_replace('DELETE FROM', 'UPDATE', $stmt);
                $sqlStatements[$index] = str_replace(
                    'WHERE',
                    'SET '.$config['fieldName'].' = '.$platform->getCurrentTimestampSQL().' WHERE',
                    $sqlStatements[$index]
                );
            } else {
                // We have to avoid the removal of registers of child entities of a SoftDeleteable entity
                unset($sqlStatements[$index]);
            }
        }

        $this->_sqlStatements = $sqlStatements;
    }
}
