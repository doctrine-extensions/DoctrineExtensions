<?php

namespace Gedmo\Tree\Traits\Repository\ORM;

use Doctrine\ORM\QueryBuilder;
use Gedmo\Exception\InvalidArgumentException;
use Doctrine\ORM\Query;
use Gedmo\Tree\Entity\MappedSuperclass\AbstractClosure;
use Gedmo\Tree\Entity\Repository\ClosureTreeRepository;
use Gedmo\Tree\Strategy;
use Gedmo\Tool\Wrapper\EntityWrapper;

/**
 * The ClosureTreeRepository has some useful functions
 * to interact with Closure tree. Repository uses
 * the strategy used by listener
 *
 * @author Gustavo Adrian <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
trait ClosureTreeRepositoryTrait
{
    use TreeRepositoryTrait;

    /**
     * {@inheritDoc}
     */
    public function getRootNodesQueryBuilder($sortByField = null, $direction = 'asc')
    {
        $meta = $this->getClassMetadata();
        $config = $this->listener->getConfiguration($this->getEntityManager(), $meta->name);

        $qb = $this->getQueryBuilder();
        $qb->select('node')
            ->from($config['useObjectClass'], 'node')
            ->where('node.'.$config['parent']." IS NULL");

        if ($sortByField) {
            $qb->orderBy('node.'.$sortByField, strtolower($direction) === 'asc' ? 'asc' : 'desc');
        }

        return $qb;
    }

    /**
     * {@inheritDoc}
     */
    public function getRootNodesQuery($sortByField = null, $direction = 'asc')
    {
        return $this->getRootNodesQueryBuilder($sortByField, $direction)->getQuery();
    }

    /**
     * {@inheritDoc}
     */
    public function getRootNodes($sortByField = null, $direction = 'asc')
    {
        return $this->getRootNodesQuery($sortByField, $direction)->getResult();
    }

    /**
     * Get the Tree path query by given $node
     *
     * @param object $node
     *
     * @throws InvalidArgumentException - if input is not valid
     *
     * @return Query
     */
    public function getPathQuery($node)
    {
        $meta = $this->getClassMetadata();
        $em = $this->getEntityManager();

        if (!$node instanceof $meta->name) {
            throw new InvalidArgumentException("Node is not related to this repository");
        }

        if (!$em->getUnitOfWork()->isInIdentityMap($node)) {
            throw new InvalidArgumentException("Node is not managed by UnitOfWork");
        }

        $config = $this->listener->getConfiguration($em, $meta->name);
        $closureMeta = $em->getClassMetadata($config['closure']);

        $dql = "SELECT c, node FROM {$closureMeta->name} c";
        $dql .= " INNER JOIN c.ancestor node";
        $dql .= " WHERE c.descendant = :node";
        $dql .= " ORDER BY c.depth DESC";
        $q = $em->createQuery($dql);
        $q->setParameters(compact('node'));

        return $q;
    }

    /**
     * Get the Tree path of Nodes by given $node
     *
     * @param object $node
     *
     * @return array - list of Nodes in path
     */
    public function getPath($node)
    {
        return array_map(function (AbstractClosure $closure) {
            return $closure->getAncestor();
        }, $this->getPathQuery($node)->getResult());
    }

    /**
     * Get list of nodes related to a given $node
     * @param string  $way         - search direction: "down" (for children) or "up" (for ancestors)
     * @param object  $node        - if null, all tree nodes will be taken
     * @param boolean $direct      - true to take only direct children or parents
     * @param string  $sortByField - field name to sort by
     * @param string  $direction   - sort direction : "ASC" or "DESC"
     * @param bool    $includeNode - Include the root node in results?
     *
     * @return array - list of given $node parents, null on failure
     */
    public function closureLocateQueryBuilder($way = 'down', $node = null, $direct = false, $sortByField = null, $direction = 'ASC', $includeNode = false)
    {
        switch($way) {
            case 'down':
                $first = 'ancestor';
                $second = 'descendant';
                break;
            case 'up':
                $first = 'descendant';
                $second = 'ancestor';
                break;
            default:
                throw new InvalidArgumentException("Direction must be 'up' or 'down' but '$way' found");
        }

        $meta = $this->getClassMetadata();
        $config = $this->listener->getConfiguration($this->getEntityManager(), $meta->name);
        $qb = $this->getQueryBuilder();

        if ($node !== null) {
            if ($node instanceof $meta->name) {
                if (!$this->getEntityManager()->getUnitOfWork()->isInIdentityMap($node)) {
                    throw new InvalidArgumentException('Node is not managed by UnitOfWork');
                }
                $where = "c.$first = :node AND ";
                $qb->select('c, node')
                    ->from($config['closure'], 'c')
                    ->innerJoin("c.$second", 'node');
                if ($direct) {
                    $where .= 'c.depth = 1';
                } else {
                    $where .= "c.$second <> :node";
                }
                $qb->where($where);
                if ($includeNode) {
                    $qb->orWhere("c.$first = :node AND c.$second = :node");
                }
            } else {
                throw new \InvalidArgumentException("Node is not related to this repository");
            }
        } else {
            $qb->select('node')
                ->from($config['useObjectClass'], 'node');
            if ($direct) {
                $qb->where('node.'.$config['parent'].' IS NULL');
            }
        }

        if ($sortByField) {
            if ($meta->hasField($sortByField) && in_array(strtolower($direction), array('asc', 'desc'))) {
                $qb->orderBy('node.'.$sortByField, $direction);
            } else {
                throw new InvalidArgumentException("Invalid sort options specified: field - {$sortByField}, direction - {$direction}");
            }
        }

        if ($node) {
            $qb->setParameter('node', $node);
        }

        return $qb;
    }

    /**
     * @see getChildrenQueryBuilder
     */
    public function childrenQueryBuilder($node = null, $direct = false, $sortByField = null, $direction = 'ASC', $includeNode = false)
    {
        return $this->closureLocateQueryBuilder('down', $node, $direct, $sortByField, $direction, $includeNode);
    }

    /**
     * @see getChildrenQuery
     */
    public function childrenQuery($node = null, $direct = false, $sortByField = null, $direction = 'ASC', $includeNode = false)
    {
        return $this->childrenQueryBuilder($node, $direct, $sortByField, $direction, $includeNode)->getQuery();
    }

    /**
     * @see getChildren
     */
    public function children($node = null, $direct = false, $sortByField = null, $direction = 'ASC', $includeNode = false)
    {
        $result = $this->childrenQuery($node, $direct, $sortByField, $direction, $includeNode)->getResult();

        if ($node) {
            $result = array_map(function (AbstractClosure $closure) {
                return $closure->getDescendant();
            }, $result);
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getChildrenQueryBuilder($node = null, $direct = false, $sortByField = null, $direction = 'ASC', $includeNode = false)
    {
        return $this->childrenQueryBuilder($node, $direct, $sortByField, $direction, $includeNode);
    }

    /**
     * {@inheritDoc}
     */
    public function getChildrenQuery($node = null, $direct = false, $sortByField = null, $direction = 'ASC', $includeNode = false)
    {
        return $this->childrenQuery($node, $direct, $sortByField, $direction, $includeNode);
    }

    /**
     * {@inheritDoc}
     */
    public function getChildren($node = null, $direct = false, $sortByField = null, $direction = 'ASC', $includeNode = false)
    {
        return $this->children($node, $direct, $sortByField, $direction, $includeNode);
    }

    /**
     * @see getAncestorsQueryBuilder
     */
    public function ancestorsQueryBuilder($node = null, $direct = false, $sortByField = null, $direction = 'ASC', $includeNode = false)
    {
        return $this->closureLocateQueryBuilder('up', $node, $direct, $sortByField, $direction, $includeNode);
    }

    /**
     * @see getAncestorsQuery
     */
    public function ancestorsQuery($node = null, $direct = false, $sortByField = null, $direction = 'ASC', $includeNode = false)
    {
        return $this->ancestorsQueryBuilder($node, $direct, $sortByField, $direction, $includeNode)->getQuery();
    }

    /**
     * @see getAncestors
     */
    public function ancestors($node = null, $direct = false, $sortByField = null, $direction = 'ASC', $includeNode = false)
    {
        $result = $this->ancestorsQuery($node, $direct, $sortByField, $direction, $includeNode)->getResult();

        if ($node) {
            $result = array_map(function (AbstractClosure $closure) {
                return $closure->getAncestor();
            }, $result);
        }

        return $result;
    }

    /**
     * Get the list of ancestors that lead to the given $node. This returns a QueryBuilder object
     *
     * @param object  $node        - if null, all tree nodes will be taken
     * @param boolean $direct      - true to take only direct children
     * @param string  $sortByField - field name to sort by
     * @param string  $direction   - sort direction : "ASC" or "DESC"
     * @param bool    $includeNode - Include the root node in results?
     *
     * @return QueryBuilder - QueryBuilder object
     */
    public function getAncestorsQueryBuilder($node = null, $direct = false, $sortByField = null, $direction = 'ASC', $includeNode = false)
    {
        return $this->ancestorsQueryBuilder($node, $direct, $sortByField, $direction, $includeNode);
    }

    /**
     * Get the list of ancestors that lead to the given $node. This returns a Query object
     *
     * @param object  $node        - if null, all tree nodes will be taken
     * @param boolean $direct      - true to take only direct children
     * @param string  $sortByField - field name to sort by
     * @param string  $direction   - sort direction : "ASC" or "DESC"
     * @param bool    $includeNode - Include the root node in results?
     *
     * @return Query - Query object
     */
    public function getAncestorsQuery($node = null, $direct = false, $sortByField = null, $direction = 'ASC', $includeNode = false)
    {
        return $this->ancestorsQuery($node, $direct, $sortByField, $direction, $includeNode);
    }

    /**
     * Get the list of ancestors that lead to the given $node
     *
     * @param object  $node        - if null, all tree nodes will be taken
     * @param boolean $direct      - true to take only direct children
     * @param string  $sortByField - field name to sort by
     * @param string  $direction   - sort direction : "ASC" or "DESC"
     * @param bool    $includeNode - Include the root node in results?
     *
     * @return array - list of given $node parents, null on failure
     */
    public function getAncestors($node = null, $direct = false, $sortByField = null, $direction = 'ASC', $includeNode = false)
    {
        return $this->ancestors($node, $direct, $sortByField, $direction, $includeNode);
    }

    /**
     * @see childrenCount
     */
    public function ancestorsCount($node = null, $direct = false)
    {
        $meta = $this->getClassMetadata();

        if (is_object($node)) {
            if (!($node instanceof $meta->name)) {
                throw new InvalidArgumentException("Node is not related to this repository");
            }
            $wrapped = new EntityWrapper($node, $this->getEntityManager());
            if (!$wrapped->hasValidIdentifier()) {
                throw new InvalidArgumentException("Node is not managed by UnitOfWork");
            }
        }

        $qb = $this->getAncestorsQueryBuilder($node, $direct);
        // We need to remove the ORDER BY DQL part since some vendors could throw an error
        // in count queries
        $dqlParts = $qb->getDQLParts();
        // We need to check first if there's an ORDER BY DQL part, because resetDQLPart doesn't
        // check if its internal array has an "orderby" index
        if (isset($dqlParts['orderBy'])) {
            $qb->resetDQLPart('orderBy');
        }

        $aliases = $qb->getRootAliases();
        $alias = $aliases[0];
        $qb->select('COUNT('.$alias.')');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Removes given $node from the tree and reparents its descendants
     *
     * @todo may be improved, to issue single query on reparenting
     *
     * @param object $node
     *
     * @throws \Gedmo\Exception\InvalidArgumentException
     * @throws \Gedmo\Exception\RuntimeException         - if something fails in transaction
     */
    public function removeFromTree($node)
    {
        $meta = $this->getClassMetadata();
        $em = $this->getEntityManager();

        if (!$node instanceof $meta->name) {
            throw new InvalidArgumentException("Node is not related to this repository");
        }

        $wrapped = new EntityWrapper($node, $em);
        if (!$wrapped->hasValidIdentifier()) {
            throw new InvalidArgumentException("Node is not managed by UnitOfWork");
        }

        $config = $this->listener->getConfiguration($em, $meta->name);
        $pk = $meta->getSingleIdentifierFieldName();
        $nodeId = $wrapped->getIdentifier();
        $parent = $wrapped->getPropertyValue($config['parent']);

        $dql = "SELECT node FROM {$config['useObjectClass']} node";
        $dql .= " WHERE node.{$config['parent']} = :node";
        $q = $em->createQuery($dql);
        $q->setParameters(compact('node'));
        $nodesToReparent = $q->getResult();

        // process updates in transaction
        $em->getConnection()->beginTransaction();
        try {
            foreach ($nodesToReparent as $nodeToReparent) {
                $id = $meta->getReflectionProperty($pk)->getValue($nodeToReparent);
                $meta->getReflectionProperty($config['parent'])->setValue($nodeToReparent, $parent);

                $dql = "UPDATE {$config['useObjectClass']} node";
                $dql .= " SET node.{$config['parent']} = :parent";
                $dql .= " WHERE node.{$pk} = :id";
                $q = $em->createQuery($dql);
                $q->setParameters(compact('parent', 'id'));
                $q->getSingleScalarResult();

                $this->listener
                    ->getStrategy($em, $meta->name)
                    ->updateNode($em, $nodeToReparent, $node);
                $oid = spl_object_hash($nodeToReparent);
                $em->getUnitOfWork()->setOriginalEntityProperty($oid, $config['parent'], $parent);
            }

            $dql = "DELETE {$config['useObjectClass']} node";
            $dql .= " WHERE node.{$pk} = :nodeId";
            $q = $em->createQuery($dql);
            $q->setParameters(compact('nodeId'));
            $q->getSingleScalarResult();
            $em->getConnection()->commit();
        } catch (\Exception $e) {
            $em->close();
            $em->getConnection()->rollback();

            throw new \Gedmo\Exception\RuntimeException('Transaction failed: '.$e->getMessage(), null, $e);
        }

        // remove from identity map
        $em->getUnitOfWork()->removeFromIdentityMap($node);
        $node = null;
    }
    /**
     * Process nodes and produce an array with the
     * structure of the tree
     *
     * @param array - Array of nodes
     *
     * @return array - Array with tree structure
     */
    public function buildTreeArray(array $nodes)
    {
        $meta = $this->getClassMetadata();
        $config = $this->listener->getConfiguration($this->getEntityManager(), $meta->name);
        $nestedTree = array();
        $idField = $meta->getSingleIdentifierFieldName();
        $hasLevelProp = !empty($config['level']);
        $levelProp = $hasLevelProp ? $config['level'] : ClosureTreeRepository::SUBQUERY_LEVEL;
        $childrenIndex = $this->repoUtils->getChildrenIndex();

        if (count($nodes) > 0) {
            $firstLevel = $hasLevelProp ? $nodes[0][0]['descendant'][$levelProp] : $nodes[0][$levelProp];
            $l = 1;     // 1 is only an initial value. We could have a tree which has a root node with any level (subtrees)
            $refs = array();
            foreach ($nodes as $n) {
                $node = $n[0]['descendant'];
                $node[$childrenIndex] = array();
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

    /**
     * {@inheritdoc}
     */
    public function getNodesHierarchy($node = null, $direct = false, array $options = array(), $includeNode = false)
    {
        return $this->getNodesHierarchyQuery($node, $direct, $options, $includeNode)->getArrayResult();
    }

    /**
     * {@inheritdoc}
     */
    public function getNodesHierarchyQuery($node = null, $direct = false, array $options = array(), $includeNode = false)
    {
        return $this->getNodesHierarchyQueryBuilder($node, $direct, $options, $includeNode)->getQuery();
    }

    /**
     * {@inheritdoc}
     */
    public function getNodesHierarchyQueryBuilder($node = null, $direct = false, array $options = array(), $includeNode = false)
    {
        $meta = $this->getClassMetadata();
        $config = $this->listener->getConfiguration($this->getEntityManager(), $meta->name);
        $idField = $meta->getSingleIdentifierFieldName();
        $subQuery = '';
        $hasLevelProp = isset($config['level']) && $config['level'];

        if (!$hasLevelProp) {
            $subQuery = ', (SELECT MAX(c2.depth) + 1 FROM '.$config['closure'];
            $subQuery .= ' c2 WHERE c2.descendant = c.descendant GROUP BY c2.descendant) AS '.ClosureTreeRepository::SUBQUERY_LEVEL;
        }

        $q = $this->getEntityManager()->createQueryBuilder()
            ->select('c, node, p.'.$idField.' AS parent_id'.$subQuery)
            ->from($config['closure'], 'c')
            ->innerJoin('c.descendant', 'node')
            ->leftJoin('node.parent', 'p')
            ->addOrderBy(($hasLevelProp ? 'node.'.$config['level'] : ClosureTreeRepository::SUBQUERY_LEVEL), 'asc');

        if ($node !== null) {
            $q->where('c.ancestor = :node');
            $q->setParameters(compact('node'));
        } else {
            $q->groupBy('c.descendant');
        }

        if (!$includeNode) {
            $q->andWhere('c.ancestor != c.descendant');
        }

        $defaultOptions = array();
        $options = array_merge($defaultOptions, $options);
        if (isset($options['childSort']) && is_array($options['childSort']) &&
            isset($options['childSort']['field']) && isset($options['childSort']['dir'])) {
            $q->addOrderBy(
                'node.'.$options['childSort']['field'],
                strtolower($options['childSort']['dir']) == 'asc' ? 'asc' : 'desc'
            );
        }

        return $q;
    }

    /**
     * {@inheritdoc}
     */
    protected function validate()
    {
        return $this->listener->getStrategy($this->getEntityManager(), $this->getClassMetadata()->name)->getName() === Strategy::CLOSURE;
    }
}
