<?php

namespace Gedmo\Tree\Strategy\ORM;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\Version;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Gedmo\Tree\Strategy;
use Gedmo\Tree\TreeListener;
use Gedmo\Exception\RuntimeException;
use Gedmo\Exception\UnexpectedValueException;

/**
 * This strategy makes tree act like
 * a closure table.
 *
 * @author Gustavo Adrian <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
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
     * List of nodes which has their parents updated, but using
     * new nodes. They have to wait until their parents are inserted
     * on DB to make the update
     *
     * @var array
     */
    private $pendingNodeUpdates = array();

    /**
     * List of pending Nodes, which needs their "level"
     * field value set
     *
     * @var array
     */
    private $pendingNodesLevelProcess = array();

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
    public function processMetadataLoad(ObjectManager $em, ClassMetadata $meta)
    {
        $config = $this->listener->getConfiguration($em, $meta->name);
        $closureMetadata = $em->getClassMetadata($config['closure']);
        $cmf = $em->getMetadataFactory();

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
            if (Version::compare('2.3.0-dev') <= 0) {
                $closureMetadata->reflFields['ancestor'] = $cmf
                    ->getReflectionService()
                    ->getAccessibleProperty($closureMetadata->name, 'ancestor')
                ;
            }
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
            if (Version::compare('2.3.0-dev') <= 0) {
                $closureMetadata->reflFields['descendant'] = $cmf
                    ->getReflectionService()
                    ->getAccessibleProperty($closureMetadata->name, 'descendant')
                ;
            }
        }
        // create unique index on ancestor and descendant
        $indexName = substr(strtoupper("IDX_" . md5($closureMetadata->name)), 0, 20);
        $closureMetadata->table['uniqueConstraints'][$indexName] = array(
            'columns' => array(
                $this->getJoinColumnFieldName($em->getClassMetadata($config['closure'])->getAssociationMapping('ancestor')),
                $this->getJoinColumnFieldName($em->getClassMetadata($config['closure'])->getAssociationMapping('descendant'))
            )
        );
        // this one may not be very usefull
        $indexName = substr(strtoupper("IDX_" . md5($meta->name . 'depth')), 0, 20);
        $closureMetadata->table['indexes'][$indexName] = array(
            'columns' => array('depth')
        );
        if ($cacheDriver = $cmf->getCacheDriver()) {
            $cacheDriver->save($closureMetadata->name."\$CLASSMETADATA", $closureMetadata, null);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onFlushEnd(ObjectManager $em)
    {}

    /**
     * {@inheritdoc}
     */
    public function processPrePersist(ObjectManager $em, $node)
    {
        $this->pendingChildNodeInserts[spl_object_hash($node)] = $node;
    }

    /**
     * {@inheritdoc}
     */
    public function processPreUpdate(ObjectManager $em, $node)
    {}

    /**
     * {@inheritdoc}
     */
    public function processPreRemove(ObjectManager $em, $node)
    {}

     /**
     * {@inheritdoc}
     */
    public function processScheduledInsertion(ObjectManager $em, $node)
    {}

    /**
     * {@inheritdoc}
     */
    public function processScheduledDelete(ObjectManager $em, $entity)
    {}

    protected function getJoinColumnFieldName($association)
    {
        if (count($association['joinColumnFieldNames']) > 1) {
            throw new RuntimeException('More association on field '.$association['fieldName']);
        }

        return array_shift($association['joinColumnFieldNames']);
    }

    /**
     * {@inheritdoc}
     */
    public function processPostUpdate(ObjectManager $em, $entity)
    {
        $meta = $em->getClassMetadata(get_class($entity));
        $config = $this->listener->getConfiguration($em, $meta->name);

        // Process TreeLevel field value
        if (!empty($config)) {
            $this->setLevelFieldOnPendingNodes($em);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function processPostRemove(ObjectManager $em, $entity)
    {}

    /**
     * {@inheritdoc}
     */
    public function processPostPersist(ObjectManager $em, $entity)
    {
        $uow = $em->getUnitOfWork();

        while ($node = array_shift($this->pendingChildNodeInserts)) {
            $meta = $em->getClassMetadata(get_class($node));
            $config = $this->listener->getConfiguration($em, $meta->name);

            $identifier = $meta->getSingleIdentifierFieldName();
            $nodeId = $meta->getReflectionProperty($identifier)->getValue($node);
            $parent = $meta->getReflectionProperty($config['parent'])->getValue($node);

            $closureClass = $config['closure'];
            $closureMeta = $em->getClassMetadata($closureClass);
            $closureTable = $closureMeta->getTableName();

            $ancestorColumnName = $this->getJoinColumnFieldName($em->getClassMetadata($config['closure'])->getAssociationMapping('ancestor'));
            $descendantColumnName = $this->getJoinColumnFieldName($em->getClassMetadata($config['closure'])->getAssociationMapping('descendant'));
            $depthColumnName = $em->getClassMetadata($config['closure'])->getColumnName('depth');

            $entries = array(
                array(
                    $ancestorColumnName => $nodeId,
                    $descendantColumnName => $nodeId,
                    $depthColumnName => 0
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
                        $ancestorColumnName => $ancestor['ancestor']['id'],
                        $descendantColumnName => $nodeId,
                        $depthColumnName => $ancestor['depth'] + 1
                    );
                }

                if (isset($config['level'])) {
                    $this->pendingNodesLevelProcess[$nodeId] = $node;
                }
            } else if (isset($config['level'])) {
                $uow->scheduleExtraUpdate($node, array($config['level'] => array(null, 1)));
                $uow->setOriginalEntityProperty(spl_object_hash($node), $config['level'], 1);
            }

            foreach ($entries as $closure) {
                if (!$em->getConnection()->insert($closureTable, $closure)) {
                    throw new RuntimeException('Failed to insert new Closure record');
                }
            }
        }

        // Process pending node updates
        if (!empty($this->pendingNodeUpdates)) {
            foreach ($this->pendingNodeUpdates as $info) {
                $this->updateNode($em, $info['node'], $info['oldParent']);
            }

            $this->pendingNodeUpdates = array();
        }

        // Process TreeLevel field value
        $this->setLevelFieldOnPendingNodes($em);
    }

    /**
     * Process pending entities to set their "level" value
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $em
     */
    protected function setLevelFieldOnPendingNodes(ObjectManager $em)
    {
        if (!empty($this->pendingNodesLevelProcess)) {
            $first = array_slice($this->pendingNodesLevelProcess, 0, 1);
            $first = array_shift($first);
            $meta = $em->getClassMetadata(get_class($first));
            unset($first);
            $identifier = $meta->getIdentifier();
            $mapping = $meta->getFieldMapping($identifier[0]);
            $config = $this->listener->getConfiguration($em, $meta->name);
            $closureClass = $config['closure'];
            $closureMeta = $em->getClassMetadata($closureClass);
            $uow = $em->getUnitOfWork();

            foreach ($this->pendingNodesLevelProcess as $node) {
                $children = $em->getRepository($meta->name)->children($node);

                foreach ($children as $child) {
                    $em->initializeObject($child);
                    $id = $meta->getReflectionProperty($identifier[0])->getValue($child);
                    $this->pendingNodesLevelProcess[$id] = $child;
                }
            }

            // Avoid type conversion performance penalty
            $type = 'integer' === $mapping['type'] ? Connection::PARAM_INT_ARRAY : Connection::PARAM_STR_ARRAY;

            // We calculate levels for all nodes
            $sql = 'SELECT c.descendant, MAX(c.depth) + 1 AS level ';
            $sql .= 'FROM '.$closureMeta->getTableName().' c ';
            $sql .= 'WHERE c.descendant IN (?) ';
            $sql .= 'GROUP BY c.descendant';

            $levels = $em->getConnection()->executeQuery($sql, array(array_keys($this->pendingNodesLevelProcess)), array($type))->fetchAll(\PDO::FETCH_KEY_PAIR);

            // Now we update levels
            foreach ($this->pendingNodesLevelProcess as $nodeId => $node) {
                // Update new level
                $level = $levels[$nodeId];
                $uow->scheduleExtraUpdate(
                    $node,
                    array($config['level'] => array(
                        $meta->getReflectionProperty($config['level'])->getValue($node), $level
                    ))
                );
                $uow->setOriginalEntityProperty(spl_object_hash($node), $config['level'], $level);
            }

            $this->pendingNodesLevelProcess = array();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function processScheduledUpdate(ObjectManager $em, $node)
    {
        $meta = $em->getClassMetadata(get_class($node));
        $config = $this->listener->getConfiguration($em, $meta->name);
        $uow = $em->getUnitOfWork();
        $changeSet = $uow->getEntityChangeSet($node);

        if (array_key_exists($config['parent'], $changeSet)) {
            // If new parent is new, we need to delay the update of the node
            // until it is inserted on DB
            $parent = $changeSet[$config['parent']][1] ?: null;
            if ($parent && !$uow->isInIdentityMap($parent)) {
                $this->pendingNodeUpdates[spl_object_hash($node)] = array(
                    'node'      => $node,
                    'oldParent' => $changeSet[$config['parent']][0]
                );
            } else {
                $this->updateNode($em, $node, $changeSet[$config['parent']][0]);
            }
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

        $nodeId = $meta->getReflectionProperty($meta->getSingleIdentifierFieldName())->getValue($node);
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
                throw new UnexpectedValueException("Cannot set child as parent to node: {$nodeId}");
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
            $em->initializeObject($parent);
            $parentId = $meta->getReflectionProperty($meta->getSingleIdentifierFieldName())->getValue($parent);
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

        if (isset($config['level'])) {
            $this->pendingNodesLevelProcess[$nodeId] = $node;
        }
    }
}
