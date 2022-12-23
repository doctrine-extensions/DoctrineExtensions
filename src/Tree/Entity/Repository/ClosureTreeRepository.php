<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tree\Entity\Repository;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Gedmo\Exception\InvalidArgumentException;
use Gedmo\Tool\Wrapper\EntityWrapper;
use Gedmo\Tree\Entity\MappedSuperclass\AbstractClosure;
use Gedmo\Tree\Strategy;

/**
 * The ClosureTreeRepository has some useful functions
 * to interact with Closure tree. Repository uses
 * the strategy used by listener
 *
 * @author Gustavo Adrian <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @template T of object
 *
 * @template-extends AbstractTreeRepository<T>
 */
class ClosureTreeRepository extends AbstractTreeRepository
{
    /** Alias for the level value used in the subquery of the getNodesHierarchy method */
    public const SUBQUERY_LEVEL = 'level';

    public function getRootNodesQueryBuilder($sortByField = null, $direction = 'asc')
    {
        $meta = $this->getClassMetadata();
        $config = $this->listener->getConfiguration($this->getEntityManager(), $meta->getName());
        $qb = $this->getQueryBuilder();
        $qb->select('node')
            ->from($config['useObjectClass'], 'node')
            ->where('node.'.$config['parent'].' IS NULL');

        if (null !== $sortByField) {
            $sortByField = (array) $sortByField;
            $direction = (array) $direction;
            foreach ($sortByField as $key => $field) {
                $fieldDirection = $direction[$key] ?? 'asc';
                if ($meta->hasField($field) || $meta->isSingleValuedAssociation($field)) {
                    $qb->addOrderBy('node.'.$field, 'asc' === strtolower($fieldDirection) ? 'asc' : 'desc');
                }
            }
        }

        return $qb;
    }

    public function getRootNodesQuery($sortByField = null, $direction = 'asc')
    {
        return $this->getRootNodesQueryBuilder($sortByField, $direction)->getQuery();
    }

    public function getRootNodes($sortByField = null, $direction = 'asc')
    {
        return $this->getRootNodesQuery($sortByField, $direction)->getResult();
    }

    /**
     * Get the Tree path query by given $node
     *
     * @param object $node
     *
     * @throws InvalidArgumentException if input is not valid
     *
     * @return Query
     */
    public function getPathQuery($node)
    {
        $meta = $this->getClassMetadata();
        if (!is_a($node, $meta->getName())) {
            throw new InvalidArgumentException('Node is not related to this repository');
        }
        if (!$this->getEntityManager()->getUnitOfWork()->isInIdentityMap($node)) {
            throw new InvalidArgumentException('Node is not managed by UnitOfWork');
        }
        $config = $this->listener->getConfiguration($this->getEntityManager(), $meta->getName());
        $closureMeta = $this->getEntityManager()->getClassMetadata($config['closure']);

        $dql = "SELECT c, node FROM {$closureMeta->getName()} c";
        $dql .= ' INNER JOIN c.ancestor node';
        $dql .= ' WHERE c.descendant = :node';
        $dql .= ' ORDER BY c.depth DESC';
        $q = $this->getEntityManager()->createQuery($dql);
        $q->setParameter('node', $node);

        return $q;
    }

    /**
     * Get the Tree path of Nodes by given $node
     *
     * @param object $node
     *
     * @return array<int, object|null> list of Nodes in path
     */
    public function getPath($node)
    {
        return array_map(static fn (AbstractClosure $closure) => $closure->getAncestor(), $this->getPathQuery($node)->getResult());
    }

    /**
     * @param object|null          $node        If null, all tree nodes will be taken
     * @param bool                 $direct      True to take only direct children
     * @param string|string[]|null $sortByField Field name or array of fields names to sort by
     * @param string|string[]      $direction   Sort order ('asc'|'desc'|'ASC'|'DESC'). If $sortByField is an array, this may also be an array with matching number of elements
     * @param bool                 $includeNode Include the root node in results?
     *
     * @return QueryBuilder QueryBuilder object
     *
     * @phpstan-param 'asc'|'desc'|'ASC'|'DESC'|array<int, 'asc'|'desc'|'ASC'|'DESC'> $direction
     */
    public function childrenQueryBuilder($node = null, $direct = false, $sortByField = null, $direction = 'ASC', $includeNode = false)
    {
        $meta = $this->getClassMetadata();
        $config = $this->listener->getConfiguration($this->getEntityManager(), $meta->getName());

        $qb = $this->getQueryBuilder();
        if (null !== $node) {
            if (is_a($node, $meta->getName())) {
                if (!$this->getEntityManager()->getUnitOfWork()->isInIdentityMap($node)) {
                    throw new InvalidArgumentException('Node is not managed by UnitOfWork');
                }

                $where = 'c.ancestor = :node AND ';

                $qb->select('c, node')
                    ->from($config['closure'], 'c')
                    ->innerJoin('c.descendant', 'node');

                if ($direct) {
                    $where .= 'c.depth = 1';
                } else {
                    $where .= 'c.descendant <> :node';
                }

                $qb->where($where);

                if ($includeNode) {
                    $qb->orWhere('c.ancestor = :node AND c.descendant = :node');
                }
            } else {
                throw new \InvalidArgumentException('Node is not related to this repository');
            }
        } else {
            $qb->select('node')
                ->from($config['useObjectClass'], 'node');
            if ($direct) {
                $qb->where('node.'.$config['parent'].' IS NULL');
            }
        }

        if ($sortByField) {
            if (is_array($sortByField)) {
                foreach ($sortByField as $key => $field) {
                    $fieldDirection = is_array($direction) ? ($direction[$key] ?? 'asc') : $direction;
                    if (($meta->hasField($field) || $meta->isSingleValuedAssociation($field)) && in_array(strtolower($fieldDirection), ['asc', 'desc'], true)) {
                        $qb->addOrderBy('node.'.$field, $fieldDirection);
                    } else {
                        throw new InvalidArgumentException(sprintf('Invalid sort options specified: field - %s, direction - %s', $field, $fieldDirection));
                    }
                }
            } else {
                if (($meta->hasField($sortByField) || $meta->isSingleValuedAssociation($sortByField)) && in_array(strtolower($direction), ['asc', 'desc'], true)) {
                    $qb->orderBy('node.'.$sortByField, $direction);
                } else {
                    throw new InvalidArgumentException(sprintf('Invalid sort options specified: field - %s, direction - %s', $sortByField, $direction));
                }
            }
        }

        if ($node) {
            $qb->setParameter('node', $node);
        }

        return $qb;
    }

    /**
     * @param object|null          $node        If null, all tree nodes will be taken
     * @param bool                 $direct      True to take only direct children
     * @param string|string[]|null $sortByField Field name or array of fields names to sort by
     * @param string|string[]      $direction   Sort order ('asc'|'desc'|'ASC'|'DESC'). If $sortByField is an array, this may also be an array with matching number of elements
     * @param bool                 $includeNode Include the root node in results?
     *
     * @return Query Query object
     *
     * @phpstan-param 'asc'|'desc'|'ASC'|'DESC'|array<int, 'asc'|'desc'|'ASC'|'DESC'> $direction
     */
    public function childrenQuery($node = null, $direct = false, $sortByField = null, $direction = 'ASC', $includeNode = false)
    {
        return $this->childrenQueryBuilder($node, $direct, $sortByField, $direction, $includeNode)->getQuery();
    }

    /**
     * @param object|null          $node        If null, all tree nodes will be taken
     * @param bool                 $direct      True to take only direct children
     * @param string|string[]|null $sortByField Field name or array of fields names to sort by
     * @param string|string[]      $direction   Sort order ('asc'|'desc'|'ASC'|'DESC'). If $sortByField is an array, this may also be an array with matching number of elements
     * @param bool                 $includeNode Include the root node in results?
     *
     * @return array<int, object|null> List of children or null on failure
     *
     * @phpstan-param 'asc'|'desc'|'ASC'|'DESC'|array<int, 'asc'|'desc'|'ASC'|'DESC'> $direction
     */
    public function children($node = null, $direct = false, $sortByField = null, $direction = 'ASC', $includeNode = false)
    {
        $result = $this->childrenQuery($node, $direct, $sortByField, $direction, $includeNode)->getResult();
        if ($node) {
            $result = array_map(static fn (AbstractClosure $closure) => $closure->getDescendant(), $result);
        }

        return $result;
    }

    public function getChildrenQueryBuilder($node = null, $direct = false, $sortByField = null, $direction = 'ASC', $includeNode = false)
    {
        return $this->childrenQueryBuilder($node, $direct, $sortByField, $direction, $includeNode);
    }

    public function getChildrenQuery($node = null, $direct = false, $sortByField = null, $direction = 'ASC', $includeNode = false)
    {
        return $this->childrenQuery($node, $direct, $sortByField, $direction, $includeNode);
    }

    /**
     * @return array<int, object|null>
     */
    public function getChildren($node = null, $direct = false, $sortByField = null, $direction = 'ASC', $includeNode = false)
    {
        return $this->children($node, $direct, $sortByField, $direction, $includeNode);
    }

    /**
     * Removes given $node from the tree and reparents its descendants
     *
     * @todo may be improved, to issue single query on reparenting
     *
     * @param object $node
     *
     * @throws InvalidArgumentException
     * @throws \Gedmo\Exception\RuntimeException if something fails in transaction
     *
     * @return void
     */
    public function removeFromTree($node)
    {
        $meta = $this->getClassMetadata();
        if (!is_a($node, $meta->getName())) {
            throw new InvalidArgumentException('Node is not related to this repository');
        }
        $wrapped = new EntityWrapper($node, $this->getEntityManager());
        if (!$wrapped->hasValidIdentifier()) {
            throw new InvalidArgumentException('Node is not managed by UnitOfWork');
        }
        $config = $this->listener->getConfiguration($this->getEntityManager(), $meta->getName());
        $pk = $meta->getSingleIdentifierFieldName();
        $nodeId = $wrapped->getIdentifier();
        $parent = $wrapped->getPropertyValue($config['parent']);

        $dql = "SELECT node FROM {$config['useObjectClass']} node";
        $dql .= " WHERE node.{$config['parent']} = :node";
        $q = $this->getEntityManager()->createQuery($dql);
        $q->setParameter('node', $node);
        $nodesToReparent = $q->toIterable();
        // process updates in transaction
        $this->getEntityManager()->getConnection()->beginTransaction();

        try {
            foreach ($nodesToReparent as $nodeToReparent) {
                $id = $meta->getReflectionProperty($pk)->getValue($nodeToReparent);
                $meta->getReflectionProperty($config['parent'])->setValue($nodeToReparent, $parent);

                $dql = "UPDATE {$config['useObjectClass']} node";
                $dql .= " SET node.{$config['parent']} = :parent";
                $dql .= " WHERE node.{$pk} = :id";

                $q = $this->getEntityManager()->createQuery($dql);
                $q->setParameters([
                    'parent' => $parent,
                    'id' => $id,
                ]);
                $q->getSingleScalarResult();

                $this->listener
                    ->getStrategy($this->getEntityManager(), $meta->getName())
                    ->updateNode($this->getEntityManager(), $nodeToReparent, $node);

                $oid = spl_object_id($nodeToReparent);
                $this->getEntityManager()->getUnitOfWork()->setOriginalEntityProperty($oid, $config['parent'], $parent);
            }

            $dql = "DELETE {$config['useObjectClass']} node";
            $dql .= " WHERE node.{$pk} = :nodeId";

            $q = $this->getEntityManager()->createQuery($dql);
            $q->setParameter('nodeId', $nodeId);
            $q->getSingleScalarResult();
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Exception $e) {
            $this->getEntityManager()->close();
            $this->getEntityManager()->getConnection()->rollback();

            throw new \Gedmo\Exception\RuntimeException('Transaction failed: '.$e->getMessage(), $e->getCode(), $e);
        }
        // remove from identity map
        $this->getEntityManager()->getUnitOfWork()->removeFromIdentityMap($node);
        $node = null;
    }

    public function buildTreeArray(array $nodes)
    {
        $meta = $this->getClassMetadata();
        $config = $this->listener->getConfiguration($this->getEntityManager(), $meta->getName());
        $nestedTree = [];
        $idField = $meta->getSingleIdentifierFieldName();
        $hasLevelProp = !empty($config['level']);
        $levelProp = $hasLevelProp ? $config['level'] : self::SUBQUERY_LEVEL;
        $childrenIndex = $this->repoUtils->getChildrenIndex();

        if ([] !== $nodes) {
            $firstLevel = $hasLevelProp ? $nodes[0][0]['descendant'][$levelProp] : $nodes[0][$levelProp];
            $l = 1;     // 1 is only an initial value. We could have a tree which has a root node with any level (subtrees)
            $refs = [];

            foreach ($nodes as $n) {
                $node = $n[0]['descendant'];
                $node[$childrenIndex] = [];
                $level = $hasLevelProp ? $node[$levelProp] : $n[$levelProp];

                if ($l < $level) {
                    $l = $level;
                }

                if ($l == $firstLevel) {
                    $tmp = &$nestedTree;
                } else {
                    $tmp = &$refs[$n['parent_id']][$childrenIndex];
                }

                $key = count($tmp);
                $tmp[$key] = $node;
                $refs[$node[$idField]] = &$tmp[$key];
            }

            unset($refs);
        }

        return $nestedTree;
    }

    public function getNodesHierarchy($node = null, $direct = false, array $options = [], $includeNode = false)
    {
        return $this->getNodesHierarchyQuery($node, $direct, $options, $includeNode)->getArrayResult();
    }

    public function getNodesHierarchyQuery($node = null, $direct = false, array $options = [], $includeNode = false)
    {
        return $this->getNodesHierarchyQueryBuilder($node, $direct, $options, $includeNode)->getQuery();
    }

    public function getNodesHierarchyQueryBuilder($node = null, $direct = false, array $options = [], $includeNode = false)
    {
        $meta = $this->getClassMetadata();
        $config = $this->listener->getConfiguration($this->getEntityManager(), $meta->getName());
        $idField = $meta->getSingleIdentifierFieldName();
        $subQuery = '';
        $hasLevelProp = isset($config['level']) && $config['level'];

        if (!$hasLevelProp) {
            $subQuery = ', (SELECT MAX(c2.depth) + 1 FROM '.$config['closure'];
            $subQuery .= ' c2 WHERE c2.descendant = c.descendant GROUP BY c2.descendant) AS '.self::SUBQUERY_LEVEL;
        }

        $q = $this->getEntityManager()->createQueryBuilder()
            ->select('c, node, p.'.$idField.' AS parent_id'.$subQuery)
            ->from($config['closure'], 'c')
            ->innerJoin('c.descendant', 'node')
            ->leftJoin('node.parent', 'p')
            ->addOrderBy($hasLevelProp ? 'node.'.$config['level'] : self::SUBQUERY_LEVEL, 'asc');

        if (null !== $node) {
            $q->where('c.ancestor = :node');
            $q->setParameter('node', $node);
        } else {
            $q->groupBy('c.descendant');
        }

        if (!$includeNode) {
            $q->andWhere('c.ancestor != c.descendant');
        }

        $defaultOptions = [];
        $options = array_merge($defaultOptions, $options);

        if (isset($options['childSort']) && is_array($options['childSort'])
            && isset($options['childSort']['field'], $options['childSort']['dir'])) {
            $q->addOrderBy(
                'node.'.$options['childSort']['field'],
                'asc' === strtolower($options['childSort']['dir']) ? 'asc' : 'desc'
            );
        }

        return $q;
    }

    /**
     * @return array<int, string>|bool
     */
    public function verify()
    {
        $nodeMeta = $this->getClassMetadata();
        $nodeIdField = $nodeMeta->getSingleIdentifierFieldName();
        $config = $this->listener->getConfiguration($this->getEntityManager(), $nodeMeta->getName());
        $closureMeta = $this->getEntityManager()->getClassMetadata($config['closure']);
        $errors = [];

        $q = $this->getEntityManager()->createQuery("
          SELECT COUNT(node)
          FROM {$nodeMeta->getName()} AS node
          LEFT JOIN {$closureMeta->getName()} AS c WITH c.ancestor = node AND c.depth = 0
          WHERE c.id IS NULL
        ");

        if ($missingSelfRefsCount = (int) $q->getSingleScalarResult()) {
            $errors[] = "Missing $missingSelfRefsCount self referencing closures";
        }

        $q = $this->getEntityManager()->createQuery("
          SELECT COUNT(node)
          FROM {$nodeMeta->getName()} AS node
          INNER JOIN {$closureMeta->getName()} AS c1 WITH c1.descendant = node.{$config['parent']}
          LEFT  JOIN {$closureMeta->getName()} AS c2 WITH c2.descendant = node.$nodeIdField AND c2.ancestor = c1.ancestor
          WHERE c2.id IS NULL AND node.$nodeIdField <> c1.ancestor
        ");

        if ($missingClosuresCount = (int) $q->getSingleScalarResult()) {
            $errors[] = "Missing $missingClosuresCount closures";
        }

        $q = $this->getEntityManager()->createQuery("
            SELECT COUNT(c1.id)
            FROM {$closureMeta->getName()} AS c1
            LEFT JOIN {$nodeMeta->getName()} AS node WITH c1.descendant = node.$nodeIdField
            LEFT JOIN {$closureMeta->getName()} AS c2 WITH c2.descendant = node.{$config['parent']} AND c2.ancestor = c1.ancestor
            WHERE c2.id IS NULL AND c1.descendant <> c1.ancestor
        ");

        if ($invalidClosuresCount = (int) $q->getSingleScalarResult()) {
            $errors[] = "Found $invalidClosuresCount invalid closures";
        }

        if (!empty($config['level'])) {
            $levelField = $config['level'];
            $maxResults = 1000;
            $q = $this->getEntityManager()->createQuery("
                SELECT node.$nodeIdField AS id, node.$levelField AS node_level, MAX(c.depth) AS closure_level
                FROM {$nodeMeta->getName()} AS node
                INNER JOIN {$closureMeta->getName()} AS c WITH c.descendant = node.$nodeIdField
                GROUP BY node.$nodeIdField, node.$levelField
                HAVING node.$levelField IS NULL OR node.$levelField <> MAX(c.depth) + 1
            ")->setMaxResults($maxResults);

            if ($invalidLevelsCount = count($q->getScalarResult())) {
                $errors[] = "Found $invalidLevelsCount invalid level values";
            }
        }

        return [] !== $errors ? $errors : true;
    }

    /**
     * @return void
     */
    public function recover()
    {
        if (true === $this->verify()) {
            return;
        }

        $this->cleanUpClosure();
        $this->rebuildClosure();
    }

    /**
     * @return int
     */
    public function rebuildClosure()
    {
        $nodeMeta = $this->getClassMetadata();
        $config = $this->listener->getConfiguration($this->getEntityManager(), $nodeMeta->getName());
        $closureMeta = $this->getEntityManager()->getClassMetadata($config['closure']);

        $insertClosures = function ($entries) use ($closureMeta) {
            $closureTable = $closureMeta->getTableName();
            $ancestorColumnName = $this->getJoinColumnFieldName($closureMeta->getAssociationMapping('ancestor'));
            $descendantColumnName = $this->getJoinColumnFieldName($closureMeta->getAssociationMapping('descendant'));
            $depthColumnName = $closureMeta->getColumnName('depth');

            $conn = $this->getEntityManager()->getConnection();
            $conn->beginTransaction();
            foreach ($entries as $entry) {
                $conn->insert($closureTable, array_combine(
                    [$ancestorColumnName, $descendantColumnName, $depthColumnName],
                    $entry
                ));
            }
            $conn->commit();
        };

        $buildClosures = function ($dql) use ($insertClosures) {
            $newClosuresCount = 0;
            $batchSize = 1000;
            $q = $this->getEntityManager()->createQuery($dql)->setMaxResults($batchSize)->setCacheable(false);
            do {
                $entries = $q->getScalarResult();
                $insertClosures($entries);
                $newClosuresCount += count($entries);
            } while ([] !== $entries);

            return $newClosuresCount;
        };

        $nodeIdField = $nodeMeta->getSingleIdentifierFieldName();
        $newClosuresCount = $buildClosures("
          SELECT node.$nodeIdField AS ancestor, node.$nodeIdField AS descendant, 0 AS depth
          FROM {$nodeMeta->getName()} AS node
          LEFT JOIN {$closureMeta->getName()} AS c WITH c.ancestor = node AND c.depth = 0
          WHERE c.id IS NULL
        ");
        $newClosuresCount += $buildClosures("
          SELECT IDENTITY(c1.ancestor) AS ancestor, node.$nodeIdField AS descendant, c1.depth + 1 AS depth
          FROM {$nodeMeta->getName()} AS node
          INNER JOIN {$closureMeta->getName()} AS c1 WITH c1.descendant = node.{$config['parent']}
          LEFT  JOIN {$closureMeta->getName()} AS c2 WITH c2.descendant = node.$nodeIdField AND c2.ancestor = c1.ancestor
          WHERE c2.id IS NULL AND node.$nodeIdField <> c1.ancestor
        ");

        return $newClosuresCount;
    }

    /**
     * @return int
     */
    public function cleanUpClosure()
    {
        $conn = $this->getEntityManager()->getConnection();
        $nodeMeta = $this->getClassMetadata();
        $nodeIdField = $nodeMeta->getSingleIdentifierFieldName();
        $config = $this->listener->getConfiguration($this->getEntityManager(), $nodeMeta->getName());
        $closureMeta = $this->getEntityManager()->getClassMetadata($config['closure']);
        $closureTableName = $closureMeta->getTableName();

        $dql = "
            SELECT c1.id AS id
            FROM {$closureMeta->getName()} AS c1
            LEFT JOIN {$nodeMeta->getName()} AS node WITH c1.descendant = node.$nodeIdField
            LEFT JOIN {$closureMeta->getName()} AS c2 WITH c2.descendant = node.{$config['parent']} AND c2.ancestor = c1.ancestor
            WHERE c2.id IS NULL AND c1.descendant <> c1.ancestor
        ";

        $deletedClosuresCount = 0;
        $batchSize = 1000;
        $q = $this->getEntityManager()->createQuery($dql)->setMaxResults($batchSize)->setCacheable(false);

        while (($ids = $q->getScalarResult()) && [] !== $ids) {
            $ids = array_map(static fn (array $el) => $el['id'], $ids);
            $query = "DELETE FROM {$closureTableName} WHERE id IN (".implode(', ', $ids).')';
            if (0 === $conn->executeStatement($query)) {
                throw new \RuntimeException('Failed to remove incorrect closures');
            }
            $deletedClosuresCount += count($ids);
        }

        return $deletedClosuresCount;
    }

    /**
     * @return int
     */
    public function updateLevelValues()
    {
        $nodeMeta = $this->getClassMetadata();
        $config = $this->listener->getConfiguration($this->getEntityManager(), $nodeMeta->getName());
        $levelUpdatesCount = 0;

        if (!empty($config['level'])) {
            $levelField = $config['level'];
            $nodeIdField = $nodeMeta->getSingleIdentifierFieldName();
            $closureMeta = $this->getEntityManager()->getClassMetadata($config['closure']);

            $batchSize = 1000;
            $q = $this->getEntityManager()->createQuery("
                SELECT node.$nodeIdField AS id, node.$levelField AS node_level, MAX(c.depth) AS closure_level
                FROM {$nodeMeta->getName()} AS node
                INNER JOIN {$closureMeta->getName()} AS c WITH c.descendant = node.$nodeIdField
                GROUP BY node.$nodeIdField, node.$levelField
                HAVING node.$levelField IS NULL OR node.$levelField <> MAX(c.depth) + 1
            ")->setMaxResults($batchSize)->setCacheable(false);
            do {
                $entries = $q->getScalarResult();
                $this->getEntityManager()->getConnection()->beginTransaction();
                foreach ($entries as $entry) {
                    unset($entry['node_level']);
                    $this->getEntityManager()->createQuery("
                      UPDATE {$nodeMeta->getName()} AS node SET node.$levelField = (:closure_level + 1) WHERE node.$nodeIdField = :id
                    ")->execute($entry);
                }
                $this->getEntityManager()->getConnection()->commit();
                $levelUpdatesCount += count($entries);
            } while ([] !== $entries);
        }

        return $levelUpdatesCount;
    }

    protected function validate()
    {
        return Strategy::CLOSURE === $this->listener->getStrategy($this->getEntityManager(), $this->getClassMetadata()->name)->getName();
    }

    /**
     * @param array<string, mixed> $association
     *
     * @return string|null
     */
    protected function getJoinColumnFieldName($association)
    {
        if (count($association['joinColumnFieldNames']) > 1) {
            throw new \RuntimeException('More association on field '.$association['fieldName']);
        }

        return array_shift($association['joinColumnFieldNames']);
    }
}
