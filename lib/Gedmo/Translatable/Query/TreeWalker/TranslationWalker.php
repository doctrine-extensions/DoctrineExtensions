<?php

namespace Gedmo\Translatable\Query\TreeWalker;

use Gedmo\Translatable\Mapping\Event\Adapter\ORM as TranslatableEventAdapter;
use Gedmo\Translatable\TranslationListener;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TreeWalkerAdapter;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\Exec\SingleSelectExecutor;
use Doctrine\ORM\Query\AST\RangeVariableDeclaration;
use Doctrine\ORM\Query\AST\Join;

/**
 * The translation sql output walker makes it possible
 * to translate all query components during single query.
 * It works with any select query, any hydration method.
 *
 * Behind the scenes, during the object hydration it forces
 * custom hydrator in order to interact with TranslationListener
 * and skip postLoad event which would couse automatic retranslation
 * of the fields.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Translatable.Query.TreeWalker
 * @subpackage TranslationWalker
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TranslationWalker extends SqlWalker
{
    /**
     * Name for translation fallback hint
     */
    const HINT_TRANSLATION_FALLBACKS = 'translation_fallbacks';

    /**
     * Name for translation listener hint
     */
    const HINT_TRANSLATION_LISTENER = 'translation_listener';

    /**
     * Customized object hydrator name
     */
    const HYDRATE_OBJECT_TRANSLATION = 'object_translation_hydrator';

    /**
     * Customized object hydrator name
     */
    const HYDRATE_ARRAY_TRANSLATION = 'array_translation_hydrator';

    /**
     * Customized object hydrator name
     */
    const HYDRATE_SIMPLE_OBJECT_TRANSLATION = 'simple_object_translation_hydrator';

    /**
     * Stores all component references from select clause
     *
     * @var array
     */
    private $translatedComponents = array();

    /**
     * Current TranslationListener instance used
     * in EntityManager
     *
     * @var TranslationListener
     */
    private $listener;

    /**
     * DBAL database platform
     *
     * @var Doctrine\DBAL\Platforms\AbstractPlatform
     */
    private $platform;

    /**
     * DBAL database connection
     *
     * @var Doctrine\DBAL\Connection
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
        parent::__construct($query, $parserResult, $queryComponents);
        $this->conn = $this->getConnection();
        $this->platform = $this->getConnection()->getDatabasePlatform();
        $this->listener = $this->getTranslationListener();
        $this->extractTranslatedComponents($queryComponents);
    }

    /**
     * {@inheritDoc}
     */
    public function getExecutor($AST)
    {
        if (!$AST instanceof SelectStatement) {
            throw new \Gedmo\Exception\UnexpectedValueException('Translation walker should be used only on select statement');
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

        $this->getQuery()->setHint(self::HINT_TRANSLATION_LISTENER, $this->listener);
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
     * Walks from clause, and creates translation joins
     * for the translated components
     *
     * @param Doctrine\ORM\Query\AST\FromClause $from
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
            foreach ($decl->joinVariableDeclarations as $joinDecl) {
                if ($joinDecl->join instanceof Join) {
                    if (isset($this->components[$joinDecl->join->aliasIdentificationVariable])) {
                        $result .= $this->components[$joinDecl->join->aliasIdentificationVariable];
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
     * @return string
     */
    private function prepareTranslatedComponents()
    {
        $em = $this->getEntityManager();
        $ea = new TranslatableEventAdapter;
        $locale = $this->listener->getListenerLocale();
        $defaultLocale = $this->listener->getDefaultLocale();

        foreach ($this->translatedComponents as $dqlAlias => $comp) {
            $meta = $comp['metadata'];
            $config = $this->listener->getConfiguration($em, $meta->name);
            $transClass = $this->listener->getTranslationClass($ea, $meta->name);
            $transMeta = $em->getClassMetadata($transClass);
            $transTable = $transMeta->getQuotedTableName($this->platform);
            if ($locale !== $defaultLocale) {
                foreach ($config['fields'] as $field) {
                    $compTableName = $meta->getQuotedTableName($this->platform);
                    $compTblAlias = $this->getSQLTableAlias($compTableName, $dqlAlias);
                    $tblAlias = $this->getSQLTableAlias('trans'.$compTblAlias.$field);
                    $sql = ' LEFT JOIN '.$transTable.' '.$tblAlias;
                    $sql .= ' ON '.$tblAlias.'.'.$transMeta->getQuotedColumnName('locale', $this->platform)
                        .' = '.$this->conn->quote($locale);
                    $sql .= ' AND '.$tblAlias.'.'.$transMeta->getQuotedColumnName('objectClass', $this->platform)
                        .' = '.$this->conn->quote($meta->name);
                    $sql .= ' AND '.$tblAlias.'.'.$transMeta->getQuotedColumnName('field', $this->platform)
                        .' = '.$this->conn->quote($field);
                    $identifier = $meta->getSingleIdentifierFieldName();
                    $colName = $meta->getQuotedColumnName($identifier, $this->platform);
                    $sql .= ' AND '.$tblAlias.'.'.$transMeta->getQuotedColumnName('foreignKey', $this->platform)
                        .' = '.$compTblAlias.'.'.$colName;
                    isset($this->components[$dqlAlias]) ? $this->components[$dqlAlias] .= $sql : $this->components[$dqlAlias] = $sql;
                    if ($this->needsFallback()) {
                        // COALESCE with the original record columns
                        $this->replacements[$compTblAlias.'.'.$meta->getQuotedColumnName($field, $this->platform)]
                            = 'COALESCE('.$tblAlias.'.'.$transMeta->getQuotedColumnName('content', $this->platform)
                            .', '.$compTblAlias.'.'.$meta->getQuotedColumnName($field, $this->platform).')'
                        ;
                    } else {
                        $this->replacements[$compTblAlias.'.'.$meta->getQuotedColumnName($field, $this->platform)]
                            = $tblAlias.'.'.$transMeta->getQuotedColumnName('content', $this->platform)
                        ;
                    }
                }
            }
        }
    }

    /**
     * Checks if translation fallbacks are needed
     *
     * @return boolean
     */
    private function needsFallback()
    {
        $q = $this->getQuery();
        return $this->listener->getTranslationFallback()
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
            if (!isset($comp['metadata'])) {
                continue;
            }
            $meta = $comp['metadata'];
            $config = $this->listener->getConfiguration($em, $meta->name);
            if ($config && isset($config['fields'])) {
                $this->translatedComponents[$alias] = $comp;
            }
        }
    }

    /**
     * Get the currently used TranslationListener
     *
     * @throws \Gedmo\Exception\RuntimeException - if listener is not found
     * @return TranslationListener
     */
    private function getTranslationListener()
    {
        $translationListener = null;
        $em = $this->getEntityManager();
        foreach ($em->getEventManager()->getListeners() as $event => $listeners) {
            foreach ($listeners as $hash => $listener) {
                if ($listener instanceof TranslationListener) {
                    $translationListener = $listener;
                    break;
                }
            }
            if ($translationListener) {
                break;
            }
        }

        if (is_null($translationListener)) {
            throw new \Gedmo\Exception\RuntimeException('The translation listener could not be found');
        }
        return $translationListener;
    }

    /**
     * Replaces given sql $str with required
     * results
     *
     * @param array $repl
     * @param string $str
     * @return string
     */
    private function replace(array $repl, $str)
    {
        foreach ($repl as $target => $result) {
            $str = preg_replace_callback('/(\s|\()('.$target.')(\s|\))/smi', function($m) use ($result) {
                return $m[1].$result.$m[3];
            }, $str);
        }
        return $str;
    }
}