<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Translatable\Query\TreeWalker;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\AST\DeleteStatement;
use Doctrine\ORM\Query\AST\FromClause;
use Doctrine\ORM\Query\AST\GroupByClause;
use Doctrine\ORM\Query\AST\HavingClause;
use Doctrine\ORM\Query\AST\Join;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\AST\OrderByClause;
use Doctrine\ORM\Query\AST\RangeVariableDeclaration;
use Doctrine\ORM\Query\AST\SelectClause;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\AST\SimpleSelectClause;
use Doctrine\ORM\Query\AST\SubselectFromClause;
use Doctrine\ORM\Query\AST\UpdateStatement;
use Doctrine\ORM\Query\AST\WhereClause;
use Doctrine\ORM\Query\Exec\AbstractSqlExecutor;
use Doctrine\ORM\Query\Exec\SingleSelectExecutor;
use Doctrine\ORM\Query\Exec\SingleSelectSqlFinalizer;
use Doctrine\ORM\Query\Exec\SqlFinalizer;
use Doctrine\ORM\Query\SqlOutputWalker;
use Gedmo\Exception\RuntimeException;
use Gedmo\Tool\ORM\Walker\SqlWalkerCompat;
use Gedmo\Translatable\Hydrator\ORM\ObjectHydrator;
use Gedmo\Translatable\Hydrator\ORM\SimpleObjectHydrator;
use Gedmo\Translatable\Mapping\Event\Adapter\ORM as TranslatableEventAdapter;
use Gedmo\Translatable\TranslatableListener;

/**
 * The translation sql output walker makes it possible
 * to translate all query components during single query.
 * It works with any select query, any hydration method.
 *
 * Behind the scenes, during the object hydration it forces
 * custom hydrator in order to interact with TranslatableListener
 * and skip postLoad event which would cause automatic retranslation
 * of the fields.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @final since gedmo/doctrine-extensions 3.11
 */
class TranslationWalker extends SqlOutputWalker
{
    use SqlWalkerCompat;

    /**
     * Name for translation fallback hint
     *
     * @internal
     */
    public const HINT_TRANSLATION_FALLBACKS = '__gedmo.translatable.stored.fallbacks';

    /**
     * Customized object hydrator name
     *
     * @internal
     */
    public const HYDRATE_OBJECT_TRANSLATION = '__gedmo.translatable.object.hydrator';

    /**
     * Customized object hydrator name
     *
     * @internal
     */
    public const HYDRATE_SIMPLE_OBJECT_TRANSLATION = '__gedmo.translatable.simple_object.hydrator';

    /**
     * Stores all component references from select clause
     *
     * @var array<string, array<string, mixed>>
     *
     * @phpstan-var array<string, array{metadata: ClassMetadata<object>}>
     */
    private array $translatedComponents = [];

    /**
     * DBAL database platform
     */
    private AbstractPlatform $platform;

    /**
     * DBAL database connection
     */
    private Connection $conn;

    /**
     * List of aliases to replace with translation
     * content reference
     *
     * @var array<string, string>
     */
    private array $replacements = [];

    /**
     * List of joins for translated components in query
     *
     * @var array<string, string>
     */
    private array $components = [];

    private TranslatableListener $listener;

    public function __construct($query, $parserResult, array $queryComponents)
    {
        parent::__construct($query, $parserResult, $queryComponents);
        $this->conn = $this->getConnection();
        $this->platform = $this->getConnection()->getDatabasePlatform();
        $this->listener = $this->getTranslatableListener();
        $this->extractTranslatedComponents($queryComponents);
    }

    /**
     * Gets an executor that can be used to execute the result of this walker.
     *
     * @param SelectStatement|UpdateStatement|DeleteStatement $statement
     */
    protected function doGetExecutorWithCompat($statement): AbstractSqlExecutor
    {
        // If it's not a Select, the TreeWalker ought to skip it, and just return the parent.
        // @see https://github.com/doctrine-extensions/DoctrineExtensions/issues/2013
        if (!$statement instanceof SelectStatement) {
            return parent::getExecutor($statement);
        }
        $this->prepareTranslatedComponents();

        return new SingleSelectExecutor($statement, $this);
    }

    /**
     * @param DeleteStatement|UpdateStatement|SelectStatement $AST
     */
    protected function doGetFinalizerWithCompat($AST): SqlFinalizer
    {
        // If it's not a Select, the TreeWalker ought to skip it, and just return the parent.
        // @see https://github.com/doctrine-extensions/DoctrineExtensions/issues/2013
        if (!$AST instanceof SelectStatement) {
            return parent::getFinalizer($AST);
        }
        $this->prepareTranslatedComponents();

        return new SingleSelectSqlFinalizer($this->createSqlForFinalizer($AST));
    }

    protected function createSqlForFinalizer(SelectStatement $selectStatement): string
    {
        $result = parent::createSqlForFinalizer($selectStatement);
        if ([] === $this->translatedComponents) {
            return $result;
        }

        $hydrationMode = $this->getQuery()->getHydrationMode();
        if (Query::HYDRATE_OBJECT === $hydrationMode) {
            $this->getQuery()->setHydrationMode(self::HYDRATE_OBJECT_TRANSLATION);
            $this->getEntityManager()->getConfiguration()->addCustomHydrationMode(
                self::HYDRATE_OBJECT_TRANSLATION,
                ObjectHydrator::class
            );
            $this->getQuery()->setHint(Query::HINT_REFRESH, true);
        } elseif (Query::HYDRATE_SIMPLEOBJECT === $hydrationMode) {
            $this->getQuery()->setHydrationMode(self::HYDRATE_SIMPLE_OBJECT_TRANSLATION);
            $this->getEntityManager()->getConfiguration()->addCustomHydrationMode(
                self::HYDRATE_SIMPLE_OBJECT_TRANSLATION,
                SimpleObjectHydrator::class
            );
            $this->getQuery()->setHint(Query::HINT_REFRESH, true);
        }

        return $result;
    }

    protected function doWalkSelectClauseWithCompat(SelectClause $selectClause): string
    {
        return $this->replace($this->replacements, parent::walkSelectClause($selectClause));
    }

    protected function doWalkFromClauseWithCompat(FromClause $fromClause): string
    {
        return parent::walkFromClause($fromClause).$this->joinTranslations($fromClause);
    }

    protected function doWalkWhereClauseWithCompat(?WhereClause $whereClause): string
    {
        return $this->replace($this->replacements, parent::walkWhereClause($whereClause));
    }

    protected function doWalkHavingClauseWithCompat(HavingClause $havingClause): string
    {
        return $this->replace($this->replacements, parent::walkHavingClause($havingClause));
    }

    protected function doWalkOrderByClauseWithCompat(OrderByClause $orderByClause): string
    {
        return $this->replace($this->replacements, parent::walkOrderByClause($orderByClause));
    }

    protected function doWalkSubselectFromClauseWithCompat(SubselectFromClause $subselectFromClause): string
    {
        return parent::walkSubselectFromClause($subselectFromClause).$this->joinTranslations($subselectFromClause);
    }

    protected function doWalkSimpleSelectClauseWithCompat(SimpleSelectClause $simpleSelectClause): string
    {
        return $this->replace($this->replacements, parent::walkSimpleSelectClause($simpleSelectClause));
    }

    protected function doWalkGroupByClauseWithCompat(GroupByClause $groupByClause): string
    {
        return $this->replace($this->replacements, parent::walkGroupByClause($groupByClause));
    }

    /**
     * Walks from clause, and creates translation joins
     * for the translated components
     *
     * @param FromClause|SubselectFromClause $from
     */
    private function joinTranslations(Node $from): string
    {
        $result = '';
        foreach ($from->identificationVariableDeclarations as $decl) {
            if ($decl->rangeVariableDeclaration instanceof RangeVariableDeclaration) {
                if (isset($this->components[$decl->rangeVariableDeclaration->aliasIdentificationVariable])) {
                    $result .= $this->components[$decl->rangeVariableDeclaration->aliasIdentificationVariable];
                }
            }
            if (isset($decl->joinVariableDeclarations)) {
                foreach ($decl->joinVariableDeclarations as $joinDecl) {
                    if ($joinDecl->join instanceof Join) {
                        if (isset($this->components[$joinDecl->join->aliasIdentificationVariable])) {
                            $result .= $this->components[$joinDecl->join->aliasIdentificationVariable];
                        }
                    }
                }
            } else {
                // based on new changes
                foreach ($decl->joins as $join) {
                    if ($join instanceof Join) {
                        if (isset($this->components[$join->joinAssociationDeclaration->aliasIdentificationVariable])) {
                            $result .= $this->components[$join->joinAssociationDeclaration->aliasIdentificationVariable];
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Creates a left join list for translations
     * on used query components
     *
     * @todo: make it cleaner
     */
    private function prepareTranslatedComponents(): void
    {
        $q = $this->getQuery();
        $locale = $q->getHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE);
        if (!$locale) {
            // use from listener
            $locale = $this->listener->getListenerLocale();
        }
        $defaultLocale = $this->listener->getDefaultLocale();
        if ($locale === $defaultLocale && !$this->listener->getPersistDefaultLocaleTranslation()) {
            // Skip preparation as there's no need to translate anything
            return;
        }
        $em = $this->getEntityManager();
        $ea = new TranslatableEventAdapter();
        $ea->setEntityManager($em);
        $quoteStrategy = $em->getConfiguration()->getQuoteStrategy();
        $joinStrategy = $q->getHint(TranslatableListener::HINT_INNER_JOIN) ? 'INNER' : 'LEFT';

        foreach ($this->translatedComponents as $dqlAlias => $comp) {
            /** @var ClassMetadata<object> $meta */
            $meta = $comp['metadata'];
            $config = $this->listener->getConfiguration($em, $meta->getName());
            $transClass = $this->listener->getTranslationClass($ea, $meta->getName());
            $transMeta = $em->getClassMetadata($transClass);
            $transTable = $quoteStrategy->getTableName($transMeta, $this->platform);
            foreach ($config['fields'] as $field) {
                $compTblAlias = $this->walkIdentificationVariable($dqlAlias, $field);
                $tblAlias = $this->getSQLTableAlias('trans'.$compTblAlias.$field);
                $sql = " {$joinStrategy} JOIN ".$transTable.' '.$tblAlias;
                $sql .= ' ON '.$tblAlias.'.'.$quoteStrategy->getColumnName('locale', $transMeta, $this->platform)
                    .' = '.$this->conn->quote($locale);
                $sql .= ' AND '.$tblAlias.'.'.$quoteStrategy->getColumnName('field', $transMeta, $this->platform)
                    .' = '.$this->conn->quote($field);
                $identifier = $meta->getSingleIdentifierFieldName();
                $idColName = $quoteStrategy->getColumnName($identifier, $meta, $this->platform);
                if ($ea->usesPersonalTranslation($transClass)) {
                    $sql .= ' AND '.$tblAlias.'.'.$transMeta->getSingleAssociationJoinColumnName('object')
                        .' = '.$compTblAlias.'.'.$idColName;
                } else {
                    $sql .= ' AND '.$tblAlias.'.'.$quoteStrategy->getColumnName('objectClass', $transMeta, $this->platform)
                        .' = '.$this->conn->quote($config['useObjectClass']);

                    $mappingFK = $transMeta->getFieldMapping('foreignKey');
                    $mappingPK = $meta->getFieldMapping($identifier);
                    $fkColName = $this->getCastedForeignKey($compTblAlias.'.'.$idColName, $mappingFK->type ?? $mappingFK['type'], $mappingPK->type ?? $mappingPK['type']);
                    $sql .= ' AND '.$tblAlias.'.'.$quoteStrategy->getColumnName('foreignKey', $transMeta, $this->platform)
                        .' = '.$fkColName;
                }
                isset($this->components[$dqlAlias]) ? $this->components[$dqlAlias] .= $sql : $this->components[$dqlAlias] = $sql;

                $originalField = $compTblAlias.'.'.$quoteStrategy->getColumnName($field, $meta, $this->platform);
                $substituteField = $tblAlias.'.'.$quoteStrategy->getColumnName('content', $transMeta, $this->platform);

                // Treat translation as original field type
                $fieldMapping = $meta->getFieldMapping($field);
                if ((($this->platform instanceof AbstractMySQLPlatform)
                    && in_array($fieldMapping->type ?? $fieldMapping['type'], ['decimal'], true))
                    || (!($this->platform instanceof AbstractMySQLPlatform)
                    && !in_array($fieldMapping->type ?? $fieldMapping['type'], ['datetime', 'datetimetz', 'date', 'time'], true))) {
                    $type = Type::getType($fieldMapping->type ?? $fieldMapping['type']);

                    // In ORM 2.x, $fieldMapping is an array. In ORM 3.x, it's a data object. Always cast to an array for compatibility across versions.
                    $substituteField = 'CAST('.$substituteField.' AS '.$type->getSQLDeclaration((array) $fieldMapping, $this->platform).')';
                }

                // Fallback to original if was asked for
                if (($this->needsFallback() && (!isset($config['fallback'][$field]) || $config['fallback'][$field]))
                    || (!$this->needsFallback() && isset($config['fallback'][$field]) && $config['fallback'][$field])
                ) {
                    $substituteField = 'COALESCE('.$substituteField.', '.$originalField.')';
                }

                $this->replacements[$originalField] = $substituteField;
            }
        }
    }

    /**
     * Checks if translation fallbacks are needed
     */
    private function needsFallback(): bool
    {
        $q = $this->getQuery();
        $fallback = $q->getHint(TranslatableListener::HINT_FALLBACK);
        if (false === $fallback) {
            // non overrided
            $fallback = $this->listener->getTranslationFallback();
        }

        // applies fallbacks to scalar hydration as well
        return (bool) $fallback;
    }

    /**
     * Search for translated components in the select clause
     *
     * @param array<string, array<string, ClassMetadata<object>>> $queryComponents
     *
     * @phpstan-param array<string, array{metadata: ClassMetadata<object>}> $queryComponents
     */
    private function extractTranslatedComponents(array $queryComponents): void
    {
        $em = $this->getEntityManager();
        foreach ($queryComponents as $alias => $comp) {
            if (!isset($comp['metadata'])) {
                continue;
            }
            $meta = $comp['metadata'];
            $config = $this->listener->getConfiguration($em, $meta->getName());
            if ($config && isset($config['fields'])) {
                $this->translatedComponents[$alias] = $comp;
            }
        }
    }

    /**
     * Get the currently used TranslatableListener
     *
     * @throws RuntimeException if listener is not found
     */
    private function getTranslatableListener(): TranslatableListener
    {
        $em = $this->getEntityManager();
        foreach ($em->getEventManager()->getAllListeners() as $listeners) {
            foreach ($listeners as $listener) {
                if ($listener instanceof TranslatableListener) {
                    return $listener;
                }
            }
        }

        throw new RuntimeException('The translation listener could not be found');
    }

    /**
     * Replaces given sql $str with required
     * results
     *
     * @param array<string, string> $repl
     */
    private function replace(array $repl, string $str): string
    {
        foreach ($repl as $target => $result) {
            $str = preg_replace_callback('/(\s|\()('.$target.')(,?)(\s|\)|$)/smi', static fn (array $m): string => $m[1].$result.$m[3].$m[4], $str);
        }

        return $str;
    }

    /**
     * Casts a foreign key if needed
     *
     * @NOTE: personal translations manages that for themselves.
     *
     * @param string $component a column with an alias to cast
     * @param string $typeFK    translation table foreign key type
     * @param string $typePK    primary key type which references translation table
     *
     * @return string modified $component if needed
     */
    private function getCastedForeignKey(string $component, string $typeFK, string $typePK): string
    {
        // the keys are of same type
        if ($typeFK === $typePK) {
            return $component;
        }

        // try to look at postgres casting
        if ($this->platform instanceof PostgreSQLPlatform) {
            switch ($typeFK) {
                case 'string':
                case 'guid':
                    // need to cast to VARCHAR
                    $component .= '::VARCHAR';

                    break;
            }
        }

        // @TODO may add the same thing for MySQL for performance to match index

        return $component;
    }
}
