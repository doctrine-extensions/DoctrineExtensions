<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tree\Strategy\ORM;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\Deprecations\Deprecation;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\AssociationMapping;
use Doctrine\ORM\Mapping\ClassMetadata as ORMClassMetadata;
use Doctrine\ORM\Mapping\PropertyAccessors\PropertyAccessorFactory;
use Doctrine\ORM\Mapping\ToOneOwningSideMapping;
use Doctrine\ORM\Query;
use Doctrine\Persistence\Mapping\AbstractClassMetadataFactory;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Gedmo\Exception\RuntimeException;
use Gedmo\Exception\UnexpectedValueException;
use Gedmo\Mapping\Event\AdapterInterface;
use Gedmo\Tool\Wrapper\AbstractWrapper;
use Gedmo\Tree\Node;
use Gedmo\Tree\Strategy;
use Gedmo\Tree\TreeListener;
use Psr\Cache\CacheItemPoolInterface;

/**
 * This strategy makes tree act like
 * a closure table.
 *
 * @author Gustavo Adrian <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @final since gedmo/doctrine-extensions 3.11
 */
class Closure implements Strategy
{
    /**
     * TreeListener
     *
     * @var TreeListener
     */
    protected $listener;

    /**
     * List of pending Nodes, which needs to
     * be post processed because of having a parent Node
     * which requires some additional calculations
     *
     * @var array<int, array<int, object|Node>>
     */
    private array $pendingChildNodeInserts = [];

    /**
     * List of nodes which has their parents updated, but using
     * new nodes. They have to wait until their parents are inserted
     * on DB to make the update
     *
     * @var array<int, array<string, mixed>>
     *
     * @phpstan-var array<int, array{node: object|Node, oldParent: mixed}>
     */
    private array $pendingNodeUpdates = [];

    /**
     * List of pending Nodes, which needs their "level"
     * field value set
     *
     * @var array<int|string, object|Node>
     *
     * @phpstan-var array<array-key, object|Node>
     */
    private array $pendingNodesLevelProcess = [];

    public function __construct(TreeListener $listener)
    {
        $this->listener = $listener;
    }

    public function getName()
    {
        return Strategy::CLOSURE;
    }

    /**
     * @param EntityManagerInterface   $em
     * @param ORMClassMetadata<object> $meta
     */
    public function processMetadataLoad($em, $meta)
    {
        // TODO: Remove the body of this method in the next major version.
        $config = $this->listener->getConfiguration($em, $meta->getName());
        $closureMetadata = $em->getClassMetadata($config['closure']);

        $cmf = $em->getMetadataFactory();

        $hasTheUserExplicitlyDefinedMapping = true;

        if (!$closureMetadata->hasAssociation('ancestor')) {
            Deprecation::trigger(
                'gedmo/doctrine-extensions',
                'https://github.com/doctrine-extensions/DoctrineExtensions/pull/2390',
                'Not adding mapping explicitly to "ancestor" property in "%s" is deprecated and will not work in'
                .' version 4.0. You MUST explicitly set the mapping as in our docs: https://github.com/doctrine-extensions/DoctrineExtensions/blob/main/doc/tree.md#closure-table',
                $closureMetadata->getName()
            );

            $hasTheUserExplicitlyDefinedMapping = false;

            // create ancestor mapping
            $ancestorMapping = [
                'fieldName' => 'ancestor',
                'id' => false,
                'joinColumns' => [
                    [
                        'name' => 'ancestor',
                        'referencedColumnName' => 'id',
                        'unique' => false,
                        'nullable' => false,
                        'onDelete' => 'CASCADE',
                        'onUpdate' => null,
                        'columnDefinition' => null,
                    ],
                ],
                'inversedBy' => null,
                'targetEntity' => $meta->getName(),
                'cascade' => null,
                'fetch' => ORMClassMetadata::FETCH_LAZY,
            ];
            $closureMetadata->mapManyToOne($ancestorMapping);

            if (property_exists($closureMetadata, 'propertyAccessors')) {
                // ORM 3.4+
                /** @phpstan-ignore-next-line class.NotFound Class introduced in ORM 3.4 */
                $closureMetadata->propertyAccessors['ancestor'] = PropertyAccessorFactory::createPropertyAccessor(
                    $closureMetadata->getName(),
                    'ancestor'
                );
            } else {
                // ORM 3.3-
                $closureMetadata->reflFields['ancestor'] = $cmf
                    ->getReflectionService()
                    ->getAccessibleProperty($closureMetadata->getName(), 'ancestor')
                ;
            }
        }

        if (!$closureMetadata->hasAssociation('descendant')) {
            Deprecation::trigger(
                'gedmo/doctrine-extensions',
                'https://github.com/doctrine-extensions/DoctrineExtensions/pull/2390',
                'Not adding mapping explicitly to "descendant" property in "%s" is deprecated and will not work in'
                .' version 4.0. You MUST explicitly set the mapping as in our docs: https://github.com/doctrine-extensions/DoctrineExtensions/blob/main/doc/tree.md#closure-table',
                $closureMetadata->getName()
            );

            $hasTheUserExplicitlyDefinedMapping = false;

            // create descendant mapping
            $descendantMapping = [
                'fieldName' => 'descendant',
                'id' => false,
                'joinColumns' => [
                    [
                        'name' => 'descendant',
                        'referencedColumnName' => 'id',
                        'unique' => false,
                        'nullable' => false,
                        'onDelete' => 'CASCADE',
                        'onUpdate' => null,
                        'columnDefinition' => null,
                    ],
                ],
                'inversedBy' => null,
                'targetEntity' => $meta->getName(),
                'cascade' => null,
                'fetch' => ORMClassMetadata::FETCH_LAZY,
            ];
            $closureMetadata->mapManyToOne($descendantMapping);

            if (property_exists($closureMetadata, 'propertyAccessors')) {
                // ORM 3.4+
                /** @phpstan-ignore-next-line class.NotFound Class introduced in ORM 3.4 */
                $closureMetadata->propertyAccessors['descendant'] = PropertyAccessorFactory::createPropertyAccessor(
                    $closureMetadata->getName(),
                    'descendant'
                );
            } else {
                // ORM 3.3-
                $closureMetadata->reflFields['descendant'] = $cmf
                    ->getReflectionService()
                    ->getAccessibleProperty($closureMetadata->getName(), 'descendant')
                ;
            }
        }

        if (!$this->hasClosureTableUniqueConstraint($closureMetadata)) {
            Deprecation::trigger(
                'gedmo/doctrine-extensions',
                'https://github.com/doctrine-extensions/DoctrineExtensions/pull/2390',
                'Not adding a unique constraint explicitly to "%s" is deprecated and will not be automatically'
                .' added in version 4.0. You SHOULD explicitly add the unique constraint as in our docs: https://github.com/doctrine-extensions/DoctrineExtensions/blob/main/doc/tree.md#closure-table',
                $closureMetadata->getName()
            );

            $hasTheUserExplicitlyDefinedMapping = false;

            // create unique index on ancestor and descendant
            $indexName = substr(strtoupper('IDX_'.md5($closureMetadata->getName())), 0, 20);

            $ancestorAssociationMapping = $em->getClassMetadata($config['closure'])->getAssociationMapping('ancestor');
            $descendantAssociationMapping = $em->getClassMetadata($config['closure'])->getAssociationMapping('descendant');

            $closureMetadata->table['uniqueConstraints'][$indexName] = [
                'columns' => [
                    $this->getJoinColumnFieldName(is_array($ancestorAssociationMapping) ? $ancestorAssociationMapping : clone $ancestorAssociationMapping),
                    $this->getJoinColumnFieldName(is_array($descendantAssociationMapping) ? $descendantAssociationMapping : clone $descendantAssociationMapping),
                ],
            ];
        }

        if (!$this->hasClosureTableDepthIndex($closureMetadata)) {
            Deprecation::trigger(
                'gedmo/doctrine-extensions',
                'https://github.com/doctrine-extensions/DoctrineExtensions/pull/2390',
                'Not adding an index with "depth" column explicitly to "%s" is deprecated and will not be automatically'
                .' added in version 4.0. You SHOULD explicitly add the index as in our docs: https://github.com/doctrine-extensions/DoctrineExtensions/blob/main/doc/tree.md#closure-table',
                $closureMetadata->getName()
            );

            $hasTheUserExplicitlyDefinedMapping = false;

            // this one may not be very useful
            $indexName = substr(strtoupper('IDX_'.md5($meta->getName().'depth')), 0, 20);
            $closureMetadata->table['indexes'][$indexName] = [
                'columns' => ['depth'],
            ];
        }

        if (!$hasTheUserExplicitlyDefinedMapping) {
            $metadataFactory = $em->getMetadataFactory();
            $getCache = \Closure::bind(static fn (AbstractClassMetadataFactory $metadataFactory): ?CacheItemPoolInterface => $metadataFactory->getCache(), null, \get_class($metadataFactory));

            $metadataCache = $getCache($metadataFactory);

            if (null !== $metadataCache) {
                // @see https://github.com/doctrine/persistence/pull/144
                // @see \Doctrine\Persistence\Mapping\AbstractClassMetadataFactory::getCacheKey()
                $cacheKey = str_replace('\\', '__', $closureMetadata->getName()).'__CLASSMETADATA__';

                $item = $metadataCache->getItem($cacheKey);

                $metadataCache->save($item->set($closureMetadata));
            }
        }
    }

    public function onFlushEnd($em, AdapterInterface $ea)
    {
    }

    public function processPrePersist($em, $node)
    {
        $this->pendingChildNodeInserts[spl_object_id($em)][spl_object_id($node)] = $node;
    }

    public function processPreUpdate($em, $node)
    {
    }

    public function processPreRemove($em, $node)
    {
    }

    public function processScheduledInsertion($em, $node, AdapterInterface $ea)
    {
    }

    public function processScheduledDelete($em, $entity)
    {
    }

    public function processPostUpdate($em, $entity, AdapterInterface $ea)
    {
        \assert($em instanceof EntityManagerInterface);
        $meta = $em->getClassMetadata(get_class($entity));
        $config = $this->listener->getConfiguration($em, $meta->getName());

        // Process TreeLevel field value
        if (!empty($config)) {
            $this->setLevelFieldOnPendingNodes($em);
        }
    }

    public function processPostRemove($em, $entity, AdapterInterface $ea)
    {
    }

    /**
     * @param EntityManagerInterface $em
     */
    public function processPostPersist($em, $entity, AdapterInterface $ea)
    {
        $uow = $em->getUnitOfWork();
        $emHash = spl_object_id($em);

        while ($node = array_shift($this->pendingChildNodeInserts[$emHash])) {
            $meta = $em->getClassMetadata(get_class($node));
            $config = $this->listener->getConfiguration($em, $meta->getName());

            $identifier = $meta->getSingleIdentifierFieldName();
            $nodeId = $meta->getReflectionProperty($identifier)->getValue($node);
            $parent = $meta->getReflectionProperty($config['parent'])->getValue($node);

            $closureClass = $config['closure'];
            $closureMeta = $em->getClassMetadata($closureClass);
            $closureTable = $closureMeta->getTableName();

            $ancestorAssociationMapping = $em->getClassMetadata($config['closure'])->getAssociationMapping('ancestor');
            $descendantAssociationMapping = $em->getClassMetadata($config['closure'])->getAssociationMapping('descendant');

            $ancestorColumnName = $this->getJoinColumnFieldName(is_array($ancestorAssociationMapping) ? $ancestorAssociationMapping : clone $ancestorAssociationMapping);
            $descendantColumnName = $this->getJoinColumnFieldName(is_array($descendantAssociationMapping) ? $descendantAssociationMapping : clone $descendantAssociationMapping);
            $depthColumnName = $em->getClassMetadata($config['closure'])->getColumnName('depth');

            $entries = [
                [
                    $ancestorColumnName => $nodeId,
                    $descendantColumnName => $nodeId,
                    $depthColumnName => 0,
                ],
            ];

            if ($parent) {
                $dql = "SELECT c, a FROM {$closureMeta->getName()} c";
                $dql .= ' JOIN c.ancestor a';
                $dql .= ' WHERE c.descendant = :parent';
                $q = $em->createQuery($dql);
                $q->setParameter('parent', $parent);

                $mustPostpone = true;

                foreach ($q->toIterable([], Query::HYDRATE_ARRAY) as $ancestor) {
                    $mustPostpone = false;

                    $entries[] = [
                        $ancestorColumnName => $ancestor['ancestor'][$identifier],
                        $descendantColumnName => $nodeId,
                        $depthColumnName => $ancestor['depth'] + 1,
                    ];
                }

                if ($mustPostpone) {
                    // The parent has been persisted after the child, postpone the evaluation
                    $this->pendingChildNodeInserts[$emHash][] = $node;

                    continue;
                }

                if (isset($config['level'])) {
                    $this->pendingNodesLevelProcess[$nodeId] = $node;
                }
            } elseif (isset($config['level'])) {
                $uow->scheduleExtraUpdate($node, [$config['level'] => [null, 1]]);
                $ea->setOriginalObjectProperty($uow, $node, $config['level'], 1);
                $levelProp = $meta->getReflectionProperty($config['level']);
                $levelProp->setValue($node, 1);
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

            $this->pendingNodeUpdates = [];
        }

        // Process TreeLevel field value
        $this->setLevelFieldOnPendingNodes($em);
    }

    /**
     * @param EntityManagerInterface $em
     */
    public function processScheduledUpdate($em, $node, AdapterInterface $ea)
    {
        $meta = $em->getClassMetadata(get_class($node));
        $config = $this->listener->getConfiguration($em, $meta->getName());
        $uow = $em->getUnitOfWork();
        $changeSet = $uow->getEntityChangeSet($node);

        if (array_key_exists($config['parent'], $changeSet)) {
            // If new parent is new, we need to delay the update of the node
            // until it is inserted on DB
            $parent = $changeSet[$config['parent']][1] ? AbstractWrapper::wrap($changeSet[$config['parent']][1], $em) : null;

            if ($parent && !$parent->getIdentifier()) {
                $this->pendingNodeUpdates[spl_object_id($node)] = [
                    'node' => $node,
                    'oldParent' => $changeSet[$config['parent']][0],
                ];
            } else {
                $this->updateNode($em, $node, $changeSet[$config['parent']][0]);
            }
        }
    }

    /**
     * Update node and closures
     *
     * @param object $node
     * @param object $oldParent
     *
     * @return void
     */
    public function updateNode(EntityManagerInterface $em, $node, $oldParent)
    {
        $wrapped = AbstractWrapper::wrap($node, $em);
        $meta = $wrapped->getMetadata();
        $config = $this->listener->getConfiguration($em, $meta->getName());
        $closureMeta = $em->getClassMetadata($config['closure']);

        $nodeId = $wrapped->getIdentifier();
        $parent = $wrapped->getPropertyValue($config['parent']);
        $table = $closureMeta->getTableName();
        $conn = $em->getConnection();
        // ensure integrity
        if ($parent) {
            $dql = "SELECT COUNT(c) FROM {$closureMeta->getName()} c";
            $dql .= ' WHERE c.ancestor = :node';
            $dql .= ' AND c.descendant = :parent';
            $q = $em->createQuery($dql);
            $q->setParameters([
                'node' => $node,
                'parent' => $parent,
            ]);
            if ($q->getSingleScalarResult()) {
                throw new UnexpectedValueException("Cannot set child as parent to node: {$nodeId}");
            }
        }

        if ($oldParent) {
            $subQuery = "SELECT c2.id FROM {$table} c1";
            $subQuery .= " JOIN {$table} c2 ON c1.descendant = c2.descendant";
            $subQuery .= ' WHERE c1.ancestor = :nodeId AND c2.depth > c1.depth';

            $ids = $conn->executeQuery($subQuery, ['nodeId' => $nodeId])->fetchFirstColumn();
            if ([] !== $ids) {
                // using subquery directly, sqlite acts unfriendly
                $query = "DELETE FROM {$table} WHERE id IN (".implode(', ', $ids).')';
                if (0 === $conn->executeStatement($query)) {
                    throw new RuntimeException('Failed to remove old closures');
                }
            }
        }

        if ($parent) {
            $wrappedParent = AbstractWrapper::wrap($parent, $em);
            $parentId = $wrappedParent->getIdentifier();
            $query = 'SELECT c1.ancestor, c2.descendant, (c1.depth + c2.depth + 1) AS depth';
            $query .= " FROM {$table} c1, {$table} c2";
            $query .= ' WHERE c1.descendant = :parentId';
            $query .= ' AND c2.ancestor = :nodeId';

            $closures = $conn->executeQuery($query, ['nodeId' => $nodeId, 'parentId' => $parentId])->fetchAllAssociative();

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

    /**
     * @param array<string, mixed>|AssociationMapping $association
     *
     * @return string|null
     */
    protected function getJoinColumnFieldName($association)
    {
        if (is_array($association)) {
            if (count($association['joinColumnFieldNames']) > 1) {
                throw new RuntimeException('More association on field '.$association['fieldName']);
            }

            return array_shift($association['joinColumnFieldNames']);
        }

        if ($association instanceof ToOneOwningSideMapping) {
            if (count($association->joinColumnFieldNames) > 1) {
                throw new RuntimeException('More association on field '.$association->fieldName);
            }

            return array_shift($association->joinColumnFieldNames);
        }

        throw new RuntimeException('Unsupported mapping type '.gettype($association));
    }

    /**
     * Process pending entities to set their "level" value
     *
     * @param EntityManagerInterface $em
     *
     * @return void
     */
    protected function setLevelFieldOnPendingNodes(ObjectManager $em)
    {
        if (!empty($this->pendingNodesLevelProcess)) {
            $first = array_slice($this->pendingNodesLevelProcess, 0, 1);
            $first = array_shift($first);

            assert(null !== $first);

            $meta = $em->getClassMetadata(get_class($first));
            unset($first);
            $identifier = $meta->getIdentifier();
            $mapping = $meta->getFieldMapping($identifier[0]);
            $config = $this->listener->getConfiguration($em, $meta->getName());
            $closureClass = $config['closure'];
            $closureMeta = $em->getClassMetadata($closureClass);
            $uow = $em->getUnitOfWork();

            foreach ($this->pendingNodesLevelProcess as $node) {
                $children = $em->getRepository($meta->getName())->children($node);

                foreach ($children as $child) {
                    $this->pendingNodesLevelProcess[AbstractWrapper::wrap($child, $em)->getIdentifier()] = $child;
                }
            }

            // Avoid type conversion performance penalty
            $type = 'integer' === ($mapping->type ?? $mapping['type'])
                ? ArrayParameterType::INTEGER
                : ArrayParameterType::STRING;

            // We calculate levels for all nodes
            $sql = 'SELECT c.descendant, MAX(c.depth) + 1 AS levelNum ';
            $sql .= 'FROM '.$closureMeta->getTableName().' c ';
            $sql .= 'WHERE c.descendant IN (?) ';
            $sql .= 'GROUP BY c.descendant';

            $levelsAssoc = $em->getConnection()->executeQuery($sql, [array_keys($this->pendingNodesLevelProcess)], [$type])->fetchAllNumeric();

            // create key pair array with resultset
            $levels = [];
            foreach ($levelsAssoc as $level) {
                $levels[$level[0]] = $level[1];
            }
            $levelsAssoc = null;

            // Now we update levels
            foreach ($this->pendingNodesLevelProcess as $nodeId => $node) {
                // Update new level
                $level = $levels[$nodeId];
                $levelProp = $meta->getReflectionProperty($config['level']);
                $uow->scheduleExtraUpdate(
                    $node,
                    [$config['level'] => [
                        $levelProp->getValue($node), $level,
                    ]]
                );
                $levelProp->setValue($node, $level);
                $uow->setOriginalEntityProperty(spl_object_id($node), $config['level'], $level);
            }

            $this->pendingNodesLevelProcess = [];
        }
    }

    /**
     * @param ORMClassMetadata<object> $closureMetadata
     */
    private function hasClosureTableUniqueConstraint(ClassMetadata $closureMetadata): bool
    {
        if (!isset($closureMetadata->table['uniqueConstraints'])) {
            return false;
        }

        foreach ($closureMetadata->table['uniqueConstraints'] as $uniqueConstraint) {
            if ([] === array_diff(['ancestor', 'descendant'], $uniqueConstraint['columns'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param ORMClassMetadata<object> $closureMetadata
     */
    private function hasClosureTableDepthIndex(ClassMetadata $closureMetadata): bool
    {
        if (!isset($closureMetadata->table['indexes'])) {
            return false;
        }

        foreach ($closureMetadata->table['indexes'] as $uniqueConstraint) {
            if ([] === array_diff(['depth'], $uniqueConstraint['columns'])) {
                return true;
            }
        }

        return false;
    }
}
