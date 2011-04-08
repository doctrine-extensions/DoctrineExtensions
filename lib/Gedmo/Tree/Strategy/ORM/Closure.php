<?php

namespace Gedmo\Tree\Strategy\ORM;

use Gedmo\Tree\Strategy,
    Doctrine\ORM\EntityManager,
    Doctrine\ORM\Proxy\Proxy,
    Gedmo\Tree\TreeListener;

/**
 * This strategy makes tree act like
 * a closure table.
 *
 * @author Gustavo Adrian <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Tree.Strategy.ORM
 * @subpackage Closure
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class Closure implements Strategy
{
    /**
     * TreeListener
     *
     * @var AbstractTreeListener
     */
    protected $listener = null;

    /**
     * List of pending Nodes, which needs to
     * be post processed because of having a parent Node
     * which requires some additional calculations
     *
     * @var array
     */
    protected $pendingChildNodeInserts = array();

    /**
     * List of pending Nodes to remove
     *
     * @var array
     */
    protected $pendingNodesForRemove = array();

    /**
     * {@inheritdoc}
     */
    public function __construct(TreeListener $listener)
    {
        $this->listener = $listener;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return Strategy::CLOSURE;
    }

    /**
     * {@inheritdoc}
     */
    public function processScheduledInsert($em, $entity)
    {
        $this->pendingChildNodeInserts[] = $entity;

        $meta = $em->getClassMetadata(get_class($entity));
        $config = $this->listener->getConfiguration($em, $meta->name);

        if (isset( $config['childCount'])) {
            // We set by default 0 on insertions for childCount field
            $meta->getReflectionProperty($config['childCount'])->setValue($entity, 0);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function processPostPersist($em, $entity)
    {
        if (count($this->pendingChildNodeInserts)) {
            while ($e = array_shift($this->pendingChildNodeInserts)) {
                $this->insertNode($em, $e);
            }

            // If "childCount" property is in the schema, we recalculate child count of all entities
            $meta = $em->getClassMetadata(get_class($entity));
            $config = $this->listener->getConfiguration($em, $meta->name);
            if (isset($config['childCount'])) {
                $this->recalculateChildCountForEntities($em, get_class($entity));
            }
        }
    }

    /**
     * Insert node and closures
     *
     * @param EntityManager $em
     * @param object $entity
     * @param bool $addNodeChildrenToAncestors
     * @throws \Gedmo\Exception\RuntimeException - if closure insert fails
     */
    public function insertNode(EntityManager $em, $entity, $addNodeChildrenToAncestors = false)
    {
        $meta = $em->getClassMetadata(get_class($entity));
        $config = $this->listener->getConfiguration($em, $meta->name);
        $identifier = $meta->getSingleIdentifierFieldName();
        $id = $this->extractIdentifier($em, $entity);
        $closureMeta = $em->getClassMetadata($config['closure']);
        $entityTable = $meta->getTableName();
        $closureTable = $closureMeta->getTableName();
        $entries = array();
        $childrenIDs = array();
        $ancestorsIDs = array();

        // If node has children it means it already has a self referencing row, so we skip its insertion
        if ($addNodeChildrenToAncestors === false) {
            $entries[] = array(
                'ancestor' => $id,
                'descendant' => $id,
                'depth' => 0
            );
        }

        $parent = $meta->getReflectionProperty($config['parent'])->getValue($entity);

        if ($parent) {
            $parentId = $meta->getReflectionProperty($identifier)->getValue($parent);
            $dql = "SELECT c.ancestor, c.depth FROM {$closureMeta->name} c";
            $dql .= " WHERE c.descendant = {$parentId}";
            $ancestors = $em->createQuery($dql)->getArrayResult();

            foreach ($ancestors as $ancestor) {
                $entries[] = array(
                    'ancestor' => $ancestor['ancestor'],
                    'descendant' => $id,
                    'depth' => $ancestor['depth'] + 1
                );
                $ancestorsIDs[] = $ancestor['ancestor'];

                if ($addNodeChildrenToAncestors === true) {
                    $dql = "SELECT c.descendant, c.depth FROM {$closureMeta->name} c";
                    $dql .= " WHERE c.ancestor = {$id} AND c.ancestor != c.descendant";
                    $children = $em->createQuery($dql)
                        ->getArrayResult();

                    foreach ($children as $child) {
                        $entries[] = array(
                            'ancestor' => $ancestor['ancestor'],
                            'descendant' => $child['descendant'],
                            'depth' => $child['depth'] + 1
                        );
                        $childrenIDs[] = $child['descendant'];
                    }
                }
            }
        }

        foreach ($entries as $closure) {
            if (!$em->getConnection()->insert($closureTable, $closure)) {
                throw new \Gedmo\Exception\RuntimeException('Failed to insert new Closure record');
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function processScheduledUpdate($em, $entity)
    {
        $entityClass = get_class($entity);
        $config = $this->listener->getConfiguration($em, $entityClass);
        $meta = $em->getClassMetadata($entityClass);
        $uow = $em->getUnitOfWork();
        $changeSet = $uow->getEntityChangeSet($entity);
        if (array_key_exists($config['parent'], $changeSet)) {
            $this->updateNode($em, $entity, $changeSet[$config['parent']]);
        }

        // If "childCount" property is in the schema, we recalculate child count of all entities
        if (isset($config['childCount'])) {
            $this->recalculateChildCountForEntities($em, get_class($entity));
        }
    }

    /**
     * Update node and closures
     *
     * @param EntityManager $em
     * @param object $entity
     * @param array $change - changeset of parent
     */
    public function updateNode(EntityManager $em, $entity, array $change)
    {
        $meta = $em->getClassMetadata(get_class($entity));
        $config = $this->listener->getConfiguration($em, $meta->name);
        $closureMeta = $em->getClassMetadata($config['closure']);
        $oldParent = $change[0];
        $nodeId = $this->extractIdentifier($em, $entity);
        $table = $closureMeta->getTableName();

        if ($oldParent) {
            $this->removeClosurePathsOfNodeID($em, $table, $nodeId);
            $this->insertNode($em, $entity, true);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function processScheduledDelete($em, $entity)
    {
        $this->removeNode($em, $entity);

        // If "childCount" property is in the schema, we recalculate child count of all entities
        $meta = $em->getClassMetadata(get_class($entity));
        $config = $this->listener->getConfiguration($em, $meta->name);

        if (isset($config['childCount'])) {
            $this->recalculateChildCountForEntities($em, get_class( $entity ));
        }
    }

    /**
     * Remove node and associated closures
     *
     * @param EntityManager $em
     * @param object $entity
     * @param bool $maintainSelfReferencingRow
     * @param bool $maintainSelfReferencingRowOfChildren
     */
    public function removeNode(EntityManager $em, $entity, $maintainSelfReferencingRow = false, $maintainSelfReferencingRowOfChildren = false)
    {
        $meta = $em->getClassMetadata(get_class($entity));
        $config = $this->listener->getConfiguration($em, $meta->name);
        $closureMeta = $em->getClassMetadata($config['closure']);
        $id = $this->extractIdentifier($em, $entity);

        $this->removeClosurePathsOfNodeID($em, $closureMeta->getTableName(), $id, $maintainSelfReferencingRow, $maintainSelfReferencingRowOfChildren);
    }

    /**
     * Remove closures for node $nodeId
     *
     * @param EntityManager $em
     * @param string $table
     * @param integer $nodeId
     * @param bool $maintainSelfReferencingRow
     * @param bool $maintainSelfReferencingRowOfChildren
     * @throws \Gedmo\Exception\RuntimeException - if deletion of closures fails
     */
    public function removeClosurePathsOfNodeID(EntityManager $em, $table, $nodeId, $maintainSelfReferencingRow = true, $maintainSelfReferencingRowOfChildren = true)
    {
        $subquery = "SELECT c1.id FROM {$table} c1 ";
        $subquery .= "WHERE c1.descendant IN (SELECT c2.descendant FROM {$table} c2 WHERE c2.ancestor = :id) ";
        $subquery .= "AND (c1.ancestor IN (SELECT c3.ancestor FROM {$table} c3 WHERE c3.descendant = :id ";

        if ($maintainSelfReferencingRow === true) {
            $subquery .= "AND c3.descendant != c3.ancestor ";
        }

        if ( $maintainSelfReferencingRowOfChildren === false) {
            $subquery .= " OR c1.descendant = c1.ancestor ";
        }

        $subquery .= " )) ";
        $subquery = "DELETE FROM {$table} WHERE {$table}.id IN (SELECT temp_table.id FROM ({$subquery}) temp_table)";

        if (!$em->getConnection()->executeQuery($subquery, array('id' => $nodeId))) {
            throw new \Gedmo\Exception\RuntimeException('Failed to delete old Closure records');
        }
    }

    /**
     * Childcount recalculation
     *
     * @param EntityManager $em
     * @param string $entityClass
     * @throws \Gedmo\Exception\RuntimeException - if update fails
     */
    public function recalculateChildCountForEntities(EntityManager $em, $entityClass)
    {
        $meta = $em->getClassMetadata($entityClass);
        $config = $this->listener->getConfiguration($em, $meta->name);
        $entityIdentifierField = $meta->getIdentifierColumnNames();
        $entityIdentifierField = $entityIdentifierField[0];
        $childCountField = $config['childCount'];
        $closureMeta = $em->getClassMetadata($config['closure']);
        $entityTable = $meta->getTableName();
        $closureTable = $closureMeta->getTableName();

        $subquery = "(SELECT COUNT( c2.descendant ) FROM {$closureTable} c2 WHERE c2.ancestor = c1.{$entityIdentifierField} AND c2.ancestor != c2.descendant)";
        $sql = "UPDATE {$entityTable} c1 SET c1.{$childCountField} = {$subquery}";

        if (!$em->getConnection()->executeQuery($sql)) {
            throw new \Gedmo\Exception\RuntimeException('Failed to update child count field of entities');
        }
    }

    /**
     * Extracts identifiers from object or proxy
     *
     * @param EntityManager $em
     * @param object $entity
     * @param bool $single
     * @return mixed - array or single identifier
     */
    private function extractIdentifier(EntityManager $em, $entity, $single = true)
    {
        if ($entity instanceof Proxy) {
            $id = $em->getUnitOfWork()->getEntityIdentifier($entity);
        } else {
            $meta = $em->getClassMetadata(get_class($entity));
            $id = array();
            foreach ($meta->identifier as $name) {
                $id[$name] = $meta->getReflectionProperty($name)->getValue($entity);
            }
        }
        if ($single) {
            $id = current($id);
        }
        return $id;
    }

    /**
     * {@inheritdoc}
     */
    public function onFlushEnd($em)
    {
    }
}
