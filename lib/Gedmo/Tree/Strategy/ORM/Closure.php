<?php

namespace Gedmo\Tree\Strategy\ORM;

use Gedmo\Exception\RuntimeException;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Gedmo\Tree\Strategy;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Proxy\Proxy;
use Gedmo\Tree\TreeListener;

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
    private $pendingChildNodeInserts = array();

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
    public function processMetadataLoad($em, $meta)
    {
        $config = $this->listener->getConfiguration($em, $meta->name);
        $closureMetadata = $em->getClassMetadata($config['closure']);

        if (!$closureMetadata->hasAssociation('ancestor')) {
            // create ancestor mapping
            $ancestorMapping = array(
                'fieldName' => 'ancestor',
                'id' => false,
                'joinColumns' => array(
                    array(
                        'name' => 'ancestor',
                        'referencedColumnName' => 'id',
                        'unique' => false,
                        'nullable' => false,
                        'onDelete' => 'CASCADE',
                        'onUpdate' => null,
                        'columnDefinition' => null,
                    )
                ),
                'inversedBy' => null,
                'targetEntity' => $meta->name,
                'cascade' => null,
                'fetch' => ClassMetadataInfo::FETCH_LAZY
            );
            $closureMetadata->mapManyToOne($ancestorMapping);
        }

        if (!$closureMetadata->hasAssociation('descendant')) {
            // create descendant mapping
            $descendantMapping = array(
                'fieldName' => 'descendant',
                'id' => false,
                'joinColumns' => array(
                    array(
                        'name' => 'descendant',
                        'referencedColumnName' => 'id',
                        'unique' => false,
                        'nullable' => false,
                        'onDelete' => 'CASCADE',
                        'onUpdate' => null,
                        'columnDefinition' => null,
                    )
                ),
                'inversedBy' => null,
                'targetEntity' => $meta->name,
                'cascade' => null,
                'fetch' => ClassMetadataInfo::FETCH_LAZY
            );
            $closureMetadata->mapManyToOne($descendantMapping);
        }
        // create unique index on ancestor and descendant
        $indexName = substr(strtoupper("IDX_" . md5($closureMetadata->name)), 0, 20);
        $closureMetadata->table['uniqueConstraints'][$indexName] = array(
            'columns' => array('ancestor', 'descendant')
        );
        // this one may not be very usefull
        $indexName = substr(strtoupper("IDX_" . md5($meta->name . 'depth')), 0, 20);
        $closureMetadata->table['indexes'][$indexName] = array(
            'columns' => array('depth')
        );
        if ($cacheDriver = $em->getMetadataFactory()->getCacheDriver()) {
            $cacheDriver->save($closureMetadata->name."\$CLASSMETADATA", $closureMetadata, null);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onFlushEnd($em)
    {}

    /**
     * {@inheritdoc}
     */
    public function processPrePersist($em, $node)
    {
        $this->pendingChildNodeInserts[spl_object_hash($node)] = $node;
    }

    /**
     * {@inheritdoc}
     */
    public function processPreRemove($em, $node)
    {}

     /**
     * {@inheritdoc}
     */
    public function processScheduledInsertion($em, $node)
    {}

    /**
     * {@inheritdoc}
     */
    public function processScheduledDelete($em, $entity)
    {}

    /**
     * {@inheritdoc}
     */
    public function processPostPersist($em, $entity)
    {
        $uow = $em->getUnitOfWork();
        if ($uow->hasPendingInsertions()) {
            return;
        }

        while ($node = array_shift($this->pendingChildNodeInserts)) {
            $meta = $em->getClassMetadata(get_class($node));
            $config = $this->listener->getConfiguration($em, $meta->name);

            $identifier = $meta->getSingleIdentifierFieldName();
            $nodeId = $meta->getReflectionProperty($identifier)->getValue($node);
            $parent = $meta->getReflectionProperty($config['parent'])->getValue($node);

            $closureClass = $config['closure'];
            $closureMeta = $em->getClassMetadata($closureClass);
            $closureTable = $closureMeta->getTableName();
            $entries = array(
                array(
                    'ancestor' => $nodeId,
                    'descendant' => $nodeId,
                    'depth' => 0
                )
            );

            if ($parent) {
                $dql = "SELECT c, a FROM {$closureMeta->name} c";
                $dql .= " JOIN c.ancestor a";
                $dql .= " WHERE c.descendant = :parent";
                $q = $em->createQuery($dql);
                $q->setParameters(compact('parent'));
                $ancestors = $q->getArrayResult();

                foreach ($ancestors as $ancestor) {
                    $entries[] = array(
                        'ancestor' => $ancestor['ancestor']['id'],
                        'descendant' => $nodeId,
                        'depth' => $ancestor['depth'] + 1
                    );
                }
            }
            foreach ($entries as $closure) {
                if (!$em->getConnection()->insert($closureTable, $closure)) {
                    throw new RuntimeException('Failed to insert new Closure record');
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function processScheduledUpdate($em, $node)
    {
        $meta = $em->getClassMetadata(get_class($node));
        $config = $this->listener->getConfiguration($em, $meta->name);
        $uow = $em->getUnitOfWork();
        $changeSet = $uow->getEntityChangeSet($node);
        if (array_key_exists($config['parent'], $changeSet)) {
            $this->updateNode($em, $node, $changeSet[$config['parent']][0]);
        }
    }

    /**
     * Update node and closures
     *
     * @param EntityManager $em
     * @param object $node
     * @param object $oldParent
     */
    public function updateNode(EntityManager $em, $node, $oldParent)
    {
        $meta = $em->getClassMetadata(get_class($node));
        $config = $this->listener->getConfiguration($em, $meta->name);
        $closureMeta = $em->getClassMetadata($config['closure']);

        $nodeId = $this->extractIdentifier($em, $node);
        $parent = $meta->getReflectionProperty($config['parent'])->getValue($node);
        $table = $closureMeta->getTableName();
        $conn = $em->getConnection();
        // ensure integrity
        if ($parent) {
            $dql = "SELECT COUNT(c) FROM {$closureMeta->name} c";
            $dql .= " WHERE c.ancestor = :node";
            $dql .= " AND c.descendant = :parent";
            $q = $em->createQuery($dql);
            $q->setParameters(compact('node', 'parent'));
            if ($q->getSingleScalarResult()) {
                throw new \Gedmo\Exception\UnexpectedValueException("Cannot set child as parent to node: {$nodeId}");
            }
        }

        if ($oldParent) {
            $subQuery = "SELECT c2.id FROM {$table} c1";
            $subQuery .= " JOIN {$table} c2 ON c1.descendant = c2.descendant";
            $subQuery .= " WHERE c1.ancestor = :nodeId AND c2.depth > c1.depth";

            $ids = $conn->fetchAll($subQuery, compact('nodeId'));
            if ($ids) {
                $ids = array_map(function($el) {
                    return $el['id'];
                }, $ids);
            }
            // using subquery directly, sqlite acts unfriendly
            $query = "DELETE FROM {$table} WHERE id IN (".implode(', ', $ids).")";
            if (!$conn->executeQuery($query)) {
                throw new RuntimeException('Failed to remove old closures');
            }
        }
        if ($parent) {
            $parentId = $this->extractIdentifier($em, $parent);
            $query = "SELECT c1.ancestor, c2.descendant, (c1.depth + c2.depth + 1) AS depth";
            $query .= " FROM {$table} c1, {$table} c2";
            $query .= " WHERE c1.descendant = :parentId";
            $query .= " AND c2.ancestor = :nodeId";

            $closures = $conn->fetchAll($query, compact('nodeId', 'parentId'));
            foreach ($closures as $closure) {
                if (!$conn->insert($table, $closure)) {
                    throw new RuntimeException('Failed to insert new Closure record');
                }
            }
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
}
