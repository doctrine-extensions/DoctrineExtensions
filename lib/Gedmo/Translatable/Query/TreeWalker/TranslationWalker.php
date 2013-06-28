<?php

namespace Gedmo\Translatable\Query\TreeWalker;

use Doctrine\ORM\Query;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TreeWalkerAdapter;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\Exec\SingleSelectExecutor;
use Doctrine\ORM\Query\AST\RangeVariableDeclaration;
use Doctrine\ORM\Query\AST\Join;
use Gedmo\Exception\UnexpectedValueException;
use Gedmo\Exception\RuntimeException;
use Gedmo\Translatable\TranslatableListener;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * The translation sql output walker makes it possible
 * to translate all query components during single query.
 * It works with any select query, any hydration method.
 *
 * Behind the scenes, during the object hydration it forces
 * custom hydrator in order to interact with TranslatableListener
 * and skip postLoad event which would couse automatic retranslation
 * of the fields.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TranslationWalker extends SqlWalker
{
    /**
     * Customized object hydrator name
     *
     * @internal
     */
    const HYDRATE_OBJECT_TRANSLATION = '__gedmo.translatable.object.hydrator';

    /**
     * Customized object hydrator name
     *
     * @internal
     */
    const HYDRATE_SIMPLE_OBJECT_TRANSLATION = '__gedmo.translatable.simple_object.hydrator';

    /**
     * Stores all component references from select clause
     *
     * @var array
     */
    private $translatedComponents = array();

    /**
     * DBAL database platform
     *
     * @var \Doctrine\DBAL\Platforms\AbstractPlatform
     */
    private $platform;

    /**
     * DBAL database connection
     *
     * @var \Doctrine\DBAL\Connection
     */
    private $conn;

    /**
     * List of aliases to replace with translation
     * content reference
     *
     * @var array
     */
    private $replacements = array();

    /**
     * List of joins for translated components in query
     *
     * @var array
     */
    private $components = array();

    /**
     * {@inheritDoc}
     */
    public function __construct($query, $parserResult, array $queryComponents)
    {
        if (false === $query->getHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE)) {
            throw new RuntimeException("Translation walker requires TranslatableListener::HINT_TRANSLATABLE_LOCALE to be set"
                .", it cannot reuse listeners locale, because when it is cached, it will produce unexpected results");
        }
        parent::__construct($query, $parserResult, $queryComponents);
        $this->conn = $this->getConnection();
        $this->platform = $this->getConnection()->getDatabasePlatform();
        $this->listener = $this->getTranslatableListener();
        $this->extractTranslatedComponents($queryComponents);
    }

    /**
     * {@inheritDoc}
     */
    public function getExecutor($AST)
    {
        if (!$AST instanceof SelectStatement) {
            throw new UnexpectedValueException('Translation walker should be used only on select statement');
        }
        $this->prepareTranslatedComponents();
        return new SingleSelectExecutor($AST, $this);
    }

    /**
     * {@inheritDoc}
     */
    public function walkSelectStatement(SelectStatement $AST)
    {
        $result = parent::walkSelectStatement($AST);
        if (!count($this->translatedComponents)) {
            return $result;
        }

        $hydrationMode = $this->getQuery()->getHydrationMode();
        if ($hydrationMode === Query::HYDRATE_OBJECT) {
            $this->getQuery()->setHydrationMode(self::HYDRATE_OBJECT_TRANSLATION);
            $this->getEntityManager()->getConfiguration()->addCustomHydrationMode(
                self::HYDRATE_OBJECT_TRANSLATION,
                'Gedmo\\Translatable\\Hydrator\\ORM\\ObjectHydrator'
            );
            $this->getQuery()->setHint(Query::HINT_REFRESH, true);
        } elseif ($hydrationMode === Query::HYDRATE_SIMPLEOBJECT) {
            $this->getQuery()->setHydrationMode(self::HYDRATE_SIMPLE_OBJECT_TRANSLATION);
            $this->getEntityManager()->getConfiguration()->addCustomHydrationMode(
                self::HYDRATE_SIMPLE_OBJECT_TRANSLATION,
                'Gedmo\\Translatable\\Hydrator\\ORM\\SimpleObjectHydrator'
            );
            $this->getQuery()->setHint(Query::HINT_REFRESH, true);
        }
        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function walkSelectClause($selectClause)
    {
        $result = parent::walkSelectClause($selectClause);
        $result = $this->replace($this->replacements, $result);
        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function walkFromClause($fromClause)
    {
        $result = parent::walkFromClause($fromClause);
        $result .= $this->joinTranslations($fromClause);
        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function walkWhereClause($whereClause)
    {
        $result = parent::walkWhereClause($whereClause);
        return $this->replace($this->replacements, $result);
    }

    /**
     * {@inheritDoc}
     */
    public function walkHavingClause($havingClause)
    {
        $result = parent::walkHavingClause($havingClause);
        return $this->replace($this->replacements, $result);
    }

    /**
     * {@inheritDoc}
     */
    public function walkOrderByClause($orderByClause)
    {
        $result = parent::walkOrderByClause($orderByClause);
        return $this->replace($this->replacements, $result);
    }

    /**
     * {@inheritDoc}
     */
    public function walkSubselect($subselect)
    {
        $result = parent::walkSubselect($subselect);
        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function walkSubselectFromClause($subselectFromClause)
    {
        $result = parent::walkSubselectFromClause($subselectFromClause);
        $result .= $this->joinTranslations($subselectFromClause);
        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function walkSimpleSelectClause($simpleSelectClause)
    {
        $result = parent::walkSimpleSelectClause($simpleSelectClause);
        return $this->replace($this->replacements, $result);
    }

    /**
     * {@inheritDoc}
     */
    public function walkGroupByClause($groupByClause)
    {
        $result = parent::walkGroupByClause($groupByClause);
        return $this->replace($this->replacements, $result);
    }

    /**
     * Walks from clause, and creates translation joins
     * for the translated components
     *
     * @param \Doctrine\ORM\Query\AST\FromClause $from
     * @return string
     */
    private function joinTranslations($from)
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
     * @return string
     */
    private function prepareTranslatedComponents()
    {
        $q = $this->getQuery();
        $locale = $q->getHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE);
        $em = $this->getEntityManager();
        $joinStrategy = $q->getHint(TranslatableListener::HINT_INNER_JOIN) ? 'INNER' : 'LEFT';
        $fallbacks = $q->getHint(TranslatableListener::HINT_FALLBACK);
        $fallbacks = $fallbacks === false ? array() : $fallbacks; // switch keys with values

        foreach ($this->translatedComponents as $dqlAlias => $comp) {
            $meta = $comp['metadata'];
            $config = $this->listener->getConfiguration($em, $meta->name);
            $tmeta = $em->getClassMetadata($config['translationClass']);

            // join translation based on current locale
            list($compTblAlias, $tblAlias) = $this->joinTranslationSql($meta, $dqlAlias, $locale, $joinStrategy);
            // join all translation fallbacks
            $fallbackComponents = array();
            foreach ($fallbacks as $fallback) {
                list($unused, $tblAliasFallback) = $this->joinTranslationSql($meta, $dqlAlias, $fallback);
                $fallbackComponents[] = $tblAliasFallback;
            }
            // organize replacements for sql query
            foreach ($config['fields'] as $field => $options) {
                $originalField = $compTblAlias.'.'.$meta->getQuotedColumnName($field, $this->platform);
                $transFieldSql = $tblAlias.'.'.$tmeta->getQuotedColumnName($field, $this->platform);
                $fallbackSql = 'NULL';
                foreach (array_reverse($fallbackComponents) as $tblAliasFallback) {
                    $fallbackField = $tblAliasFallback.'.'.$tmeta->getQuotedColumnName($field, $this->platform);
                    $fallbackSql = "COALESCE({$fallbackField}, {$fallbackSql})";
                }
                $fieldSql = "COALESCE({$transFieldSql}, {$fallbackSql})"; // always fallback to NULL by default
                $this->replacements[$originalField] = $fieldSql;
            }
        }
    }

    private function joinTranslationSql(ClassMetadataInfo $cmeta, $dqlAlias, $locale, $joinStrategy = 'LEFT')
    {
        $em = $this->getEntityManager();
        $config = $this->listener->getConfiguration($em, $cmeta->name);
        $tmeta = $em->getClassMetadata($config['translationClass']);

        $transTable = $tmeta->getQuotedTableName($this->platform);
        $compTblAlias = $this->walkIdentificationVariable($dqlAlias);
        $tblAlias = $this->getSQLTableAlias('trans_'.$locale.'_'.$compTblAlias);

        // create a join for translation
        $sql = " {$joinStrategy} JOIN {$transTable} {$tblAlias}";
        $sql .= " ON {$tblAlias}.".$tmeta->getQuotedColumnName('locale', $this->platform).' = '.$this->conn->quote($locale);

        $identifier = $cmeta->getSingleIdentifierFieldName();
        $idColName = $cmeta->getQuotedColumnName($identifier, $this->platform);
        $sql .= ' AND '.$tblAlias.'.'.$tmeta->getSingleAssociationJoinColumnName('object').' = '.$compTblAlias.'.'.$idColName;
        isset($this->components[$dqlAlias]) ? $this->components[$dqlAlias] .= $sql : $this->components[$dqlAlias] = $sql;

        return array($compTblAlias, $tblAlias);
    }

    /**
     * Checks if translation fallbacks are needed
     *
     * @return boolean
     */
    private function needsFallback()
    {
        $q = $this->getQuery();
        $fallbacks = $q->getHint(TranslatableListener::HINT_FALLBACK);
        return is_array($fallbacks)
            && count($fallbacks)
            && $q->getHydrationMode() !== Query::HYDRATE_SCALAR
            && $q->getHydrationMode() !== Query::HYDRATE_SINGLE_SCALAR;
    }

    /**
     * Search for translated components in the select clause
     *
     * @param array $queryComponents
     * @return void
     */
    private function extractTranslatedComponents(array $queryComponents)
    {
        $em = $this->getEntityManager();
        foreach ($queryComponents as $alias => $comp) {
            if (isset($comp['metadata']) && ($meta = $comp['metadata'])) {
                if (($config = $this->listener->getConfiguration($em, $meta->name)) && isset($config['fields'])) {
                    $this->translatedComponents[$alias] = $comp;
                }
            }
        }
    }

    /**
     * Get the currently used TranslatableListener
     *
     * @throws \Gedmo\Exception\RuntimeException - if listener is not found
     * @return TranslatableListener
     */
    private function getTranslatableListener()
    {
        foreach ($this->getEntityManager()->getEventManager()->getListeners() as $listeners) {
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
     * @param array $repl
     * @param string $sql
     * @return string
     */
    private function replace(array $repl, $sql)
    {
        foreach ($repl as $target => $result) {
            $sql = preg_replace_callback('/(\s|\()('.$target.')(,?)(\s|\))/smi', function($m) use ($result) {
                return $m[1].$result.$m[3].$m[4];
            }, $sql);
        }
        return $sql;
    }
}
