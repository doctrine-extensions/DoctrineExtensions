<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tree\Entity\Repository;

use Doctrine\Deprecations\Deprecation;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\Proxy;
use Gedmo\Exception\InvalidArgumentException;
use Gedmo\Exception\RuntimeException;
use Gedmo\Exception\UnexpectedValueException;
use Gedmo\Tool\ORM\Repository\EntityRepositoryCompat;
use Gedmo\Tool\Wrapper\EntityWrapper;
use Gedmo\Tree\Node;
use Gedmo\Tree\Strategy;
use Gedmo\Tree\Strategy\ORM\Nested;

/**
 * The NestedTreeRepository has some useful functions
 * to interact with NestedSet tree. Repository uses
 * the strategy used by listener
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @template T of object
 *
 * @template-extends AbstractTreeRepository<T>
 *
 * @method persistAsFirstChild($node)
 * @method persistAsFirstChildOf($node, $parent)
 * @method persistAsLastChild($node)
 * @method persistAsLastChildOf($node, $parent)
 * @method persistAsNextSibling($node)
 * @method persistAsNextSiblingOf($node, $sibling)
 * @method persistAsPrevSibling($node)
 * @method persistAsPrevSiblingOf($node, $sibling)
 */
class NestedTreeRepository extends AbstractTreeRepository
{
    use EntityRepositoryCompat;

    public function getRootNodesQueryBuilder($sortByField = null, $direction = 'asc')
    {
        $meta = $this->getClassMetadata();
        $config = $this->listener->getConfiguration($this->getEntityManager(), $meta->getName());
        $qb = $this->getQueryBuilder();
        $qb
            ->select('node')
            ->from($config['useObjectClass'], 'node')
            ->where($qb->expr()->isNull('node.'.$config['parent']))
        ;

        if (null !== $sortByField) {
            $sortByField = (array) $sortByField;
            $direction = (array) $direction;
            foreach ($sortByField as $key => $field) {
                $fieldDirection = $direction[$key] ?? 'asc';
                if ($meta->hasField($field) || $meta->isSingleValuedAssociation($field)) {
                    $qb->addOrderBy('node.'.$field, 'asc' === strtolower($fieldDirection) ? 'asc' : 'desc');
                }
            }
        } else {
            $qb->orderBy('node.'.$config['left'], 'ASC');
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
     * Get the Tree path query builder by given $node
     *
     * @param object $node
     *
     * @phpstan-param array{includeNode?: bool} $options
     *
     * options:
     * - includeNode: (bool) Whether to include the node itself. Defaults to true.
     *
     * @throws InvalidArgumentException if input is not valid
     *
     * @return QueryBuilder
     */
    public function getPathQueryBuilder($node/* , array $options = [] */) // @phpstan-ignore-line
    {
        $options = func_get_args()[1] ?? [];
        if (!\is_array($options)) {
            throw new \TypeError('Argument 2 MUST be an array.');
        }

        $defaultOptions = [
            'includeNode' => true,
        ];
        $options += $defaultOptions;

        $meta = $this->getClassMetadata();
        if (!is_a($node, $meta->getName())) {
            throw new InvalidArgumentException('Node is not related to this repository');
        }
        $config = $this->listener->getConfiguration($this->getEntityManager(), $meta->getName());
        $wrapped = new EntityWrapper($node, $this->getEntityManager());
        if (!$wrapped->hasValidIdentifier()) {
            throw new InvalidArgumentException('Node is not managed by UnitOfWork');
        }
        $left = $wrapped->getPropertyValue($config['left']);
        $right = $wrapped->getPropertyValue($config['right']);
        $qb = $this->getQueryBuilder();
        $qb->select('node')
            ->from($config['useObjectClass'], 'node')
            ->orderBy('node.'.$config['left'], 'ASC')
        ;
        if ($options['includeNode']) {
            $qb->where($qb->expr()->lte('node.'.$config['left'], $left))
               ->andWhere($qb->expr()->gte('node.'.$config['right'], $right));
        } else {
            $qb->where($qb->expr()->lt('node.'.$config['left'], $left))
               ->andWhere($qb->expr()->gt('node.'.$config['right'], $right));
        }
        if (isset($config['root'])) {
            $rootId = $wrapped->getPropertyValue($config['root']);
            $qb->andWhere($qb->expr()->eq('node.'.$config['root'], ':rid'));
            $qb->setParameter('rid', $rootId);
        }

        return $qb;
    }

    /**
     * Get the Tree path query by given $node
     *
     * @param object $node
     *
     * @phpstan-param array{includeNode?: bool} $options
     *
     * options:
     * - includeNode: (bool) Whether to include the node itself. Defaults to true.
     *
     * @return Query
     */
    public function getPathQuery($node/* , array $options = [] */) // @phpstan-ignore-line
    {
        $options = func_get_args()[1] ?? [];
        if (!\is_array($options)) {
            throw new \TypeError('Argument 2 MUST be an array.');
        }

        return $this->getPathQueryBuilder($node, $options)->getQuery();
    }

    /**
     * Get the Tree path of Nodes by given $node
     *
     * @param object $node
     *
     * @phpstan-param array{includeNode?: bool} $options
     *
     * options:
     * - includeNode: (bool) Whether to include the node itself. Defaults to true.
     *
     * @return array list of Nodes in path
     */
    public function getPath($node/* , array $options = [] */) // @phpstan-ignore-line
    {
        $options = func_get_args()[1] ?? [];
        if (!\is_array($options)) {
            throw new \TypeError('Argument 2 MUST be an array.');
        }

        return $this->getPathQuery($node, $options)->getResult();
    }

    /**
     * Get the Tree path of Nodes by given $node as a string
     *
     * @phpstan-param array{
     *     includeNode?: bool,
     *     separator?: string,
     *     stringMethod?: string
     * } $options
     *
     * options:
     * - includeNode:  (bool)   Whether to include the node itself. Defaults to true.
     * - separator:    (string) The string separating the nodes of the tree. Defaults to ' > '.
     * - stringMethod: (string) Entity method returning its displayable name. Defaults to '__toString'.
     *
     * @throws InvalidArgumentException
     */
    public function getPathAsString(object $node, array $options = []): string
    {
        $defaultOptions = [
            'includeNode' => true,
            'separator' => ' > ',
            'stringMethod' => '__toString',
        ];
        $options += $defaultOptions;

        if (!is_string($options['stringMethod'])) {
            throw new InvalidArgumentException(sprintf('"stringMethod" option passed in argument 2 to %s must be a valid string.', __METHOD__));
        }
        if (!method_exists($node, $options['stringMethod'])) {
            throw new InvalidArgumentException(sprintf('%s must implement method "%s".', get_class($node), $options['stringMethod']));
        }

        $path = [];
        foreach ($this->getPath($node, $options) as $pathNode) {
            $path[] = $pathNode->{$options['stringMethod']}();
        }

        return implode($options['separator'], $path);
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
        $qb->select('node')
            ->from($config['useObjectClass'], 'node')
        ;
        if (null !== $node) {
            if (is_a($node, $meta->getName())) {
                $wrapped = new EntityWrapper($node, $this->getEntityManager());
                if (!$wrapped->hasValidIdentifier()) {
                    throw new InvalidArgumentException('Node is not managed by UnitOfWork');
                }
                if ($direct) {
                    $qb->where($qb->expr()->eq('node.'.$config['parent'], ':pid'));
                    $qb->setParameter('pid', $wrapped->getIdentifier());
                } else {
                    $left = $wrapped->getPropertyValue($config['left']);
                    $right = $wrapped->getPropertyValue($config['right']);
                    if ($left && $right) {
                        $qb->where($qb->expr()->lt('node.'.$config['right'], $right));
                        $qb->andWhere($qb->expr()->gt('node.'.$config['left'], $left));
                    }
                }
                if (isset($config['root'])) {
                    $qb->andWhere($qb->expr()->eq('node.'.$config['root'], ':rid'));
                    $qb->setParameter('rid', $wrapped->getPropertyValue($config['root']));
                }
                if ($includeNode) {
                    $idField = $meta->getSingleIdentifierFieldName();
                    $qb->where('('.$qb->getDqlPart('where').') OR node.'.$idField.' = :rootNode');
                    $qb->setParameter('rootNode', $node);
                }
            } else {
                throw new \InvalidArgumentException('Node is not related to this repository');
            }
        } else {
            if ($direct) {
                $qb->where($qb->expr()->isNull('node.'.$config['parent']));
            }
        }
        if (!$sortByField) {
            $qb->orderBy('node.'.$config['left'], 'ASC');
        } elseif (is_array($sortByField)) {
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

        return $qb;
    }

    /**
     * @param object|null          $node        if null, all tree nodes will be taken
     * @param bool                 $direct      true to take only direct children
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
     * @param object|null          $node        The object to fetch children for; if null, all nodes will be retrieved
     * @param bool                 $direct      Flag indicating whether only direct children should be retrieved
     * @param string|string[]|null $sortByField Field name or array of fields names to sort by
     * @param string|string[]      $direction   Sort order ('asc'|'desc'|'ASC'|'DESC'). If $sortByField is an array, this may also be an array with matching number of elements
     * @param bool                 $includeNode Flag indicating whether the given node should be included in the results
     *
     * @return array<int, object> List of children
     *
     * @phpstan-param 'asc'|'desc'|'ASC'|'DESC'|array<int, 'asc'|'desc'|'ASC'|'DESC'> $direction
     */
    public function children($node = null, $direct = false, $sortByField = null, $direction = 'ASC', $includeNode = false)
    {
        return $this->childrenQuery($node, $direct, $sortByField, $direction, $includeNode)->getResult();
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
     * @return array<int, object>
     */
    public function getChildren($node = null, $direct = false, $sortByField = null, $direction = 'ASC', $includeNode = false)
    {
        return $this->children($node, $direct, $sortByField, $direction, $includeNode);
    }

    /**
     * Get tree leafs query builder
     *
     * @param object $root        root node in case of root tree is required
     * @param string $sortByField field name to sort by
     * @param string $direction   sort direction : "ASC" or "DESC"
     *
     * @throws InvalidArgumentException if input is not valid
     *
     * @return QueryBuilder
     *
     * @phpstan-param 'asc'|'desc'|'ASC'|'DESC' $direction
     */
    public function getLeafsQueryBuilder($root = null, $sortByField = null, $direction = 'ASC')
    {
        $meta = $this->getClassMetadata();
        $config = $this->listener->getConfiguration($this->getEntityManager(), $meta->getName());

        if (isset($config['root']) && null === $root) {
            throw new InvalidArgumentException('If tree has root, getLeafs method requires any node of this tree');
        }

        $qb = $this->getQueryBuilder();
        $qb->select('node')
            ->from($config['useObjectClass'], 'node')
            ->where($qb->expr()->eq('node.'.$config['right'], '1 + node.'.$config['left']))
        ;
        if (isset($config['root'])) {
            if (is_a($root, $meta->getName())) {
                $wrapped = new EntityWrapper($root, $this->getEntityManager());
                $rootId = $wrapped->getPropertyValue($config['root']);
                if (!$rootId) {
                    throw new InvalidArgumentException('Root node must be managed');
                }
                $qb->andWhere($qb->expr()->eq('node.'.$config['root'], ':rid'));
                $qb->setParameter('rid', $rootId);
            } else {
                throw new InvalidArgumentException('Node is not related to this repository');
            }
        }
        if (!$sortByField) {
            if (isset($config['root'])) {
                $qb->addOrderBy('node.'.$config['root'], 'ASC');
            }
            $qb->addOrderBy('node.'.$config['left'], 'ASC');
        } else {
            if ($meta->hasField($sortByField) && in_array(strtolower($direction), ['asc', 'desc'], true)) {
                $qb->orderBy('node.'.$sortByField, $direction);
            } else {
                throw new InvalidArgumentException("Invalid sort options specified: field - {$sortByField}, direction - {$direction}");
            }
        }

        return $qb;
    }

    /**
     * Get tree leafs query
     *
     * @param object $root        root node in case of root tree is required
     * @param string $sortByField field name to sort by
     * @param string $direction   sort direction : "ASC" or "DESC"
     *
     * @return Query
     *
     * @phpstan-param 'asc'|'desc'|'ASC'|'DESC' $direction
     */
    public function getLeafsQuery($root = null, $sortByField = null, $direction = 'ASC')
    {
        return $this->getLeafsQueryBuilder($root, $sortByField, $direction)->getQuery();
    }

    /**
     * Get list of leaf nodes of the tree
     *
     * @param object $root        root node in case of root tree is required
     * @param string $sortByField field name to sort by
     * @param string $direction   sort direction : "ASC" or "DESC"
     *
     * @return array<int, object>
     *
     * @phpstan-param 'asc'|'desc'|'ASC'|'DESC' $direction
     */
    public function getLeafs($root = null, $sortByField = null, $direction = 'ASC')
    {
        return $this->getLeafsQuery($root, $sortByField, $direction)->getResult();
    }

    /**
     * Get the query builder for next siblings of the given $node
     *
     * @param object $node
     * @param bool   $includeSelf include the node itself
     *
     * @throws InvalidArgumentException if input is invalid
     *
     * @return QueryBuilder
     */
    public function getNextSiblingsQueryBuilder($node, $includeSelf = false)
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
        $parent = $wrapped->getPropertyValue($config['parent']);

        $left = $wrapped->getPropertyValue($config['left']);

        $qb = $this->getQueryBuilder();
        $qb->select('node')
            ->from($config['useObjectClass'], 'node')
            ->where($includeSelf ?
                $qb->expr()->gte('node.'.$config['left'], $left) :
                $qb->expr()->gt('node.'.$config['left'], $left)
            )
            ->orderBy("node.{$config['left']}", 'ASC')
        ;
        if ($parent) {
            $wrappedParent = new EntityWrapper($parent, $this->getEntityManager());
            $qb->andWhere($qb->expr()->eq('node.'.$config['parent'], ':pid'));
            $qb->setParameter('pid', $wrappedParent->getIdentifier());
        } elseif (isset($config['root'])) {
            $qb->andWhere($qb->expr()->eq('node.'.$config['root'], ':root'));
            $qb->andWhere($qb->expr()->isNull('node.'.$config['parent']));
            $root = isset($config['rootIdentifierMethod']) ?
                $node->{$config['rootIdentifierMethod']}() :
                $wrapped->getPropertyValue($config['root'])
            ;
            $qb->setParameter('root', $root);
        } else {
            $qb->andWhere($qb->expr()->isNull('node.'.$config['parent']));
        }

        return $qb;
    }

    /**
     * Get the query for next siblings of the given $node
     *
     * @param object $node
     * @param bool   $includeSelf include the node itself
     *
     * @return Query
     */
    public function getNextSiblingsQuery($node, $includeSelf = false)
    {
        return $this->getNextSiblingsQueryBuilder($node, $includeSelf)->getQuery();
    }

    /**
     * Find the next siblings of the given $node
     *
     * @param object $node
     * @param bool   $includeSelf include the node itself
     *
     * @return array<int, object>
     */
    public function getNextSiblings($node, $includeSelf = false)
    {
        return $this->getNextSiblingsQuery($node, $includeSelf)->getResult();
    }

    /**
     * Get query builder for previous siblings of the given $node
     *
     * @param object $node
     * @param bool   $includeSelf include the node itself
     *
     * @throws InvalidArgumentException if input is invalid
     *
     * @return QueryBuilder
     */
    public function getPrevSiblingsQueryBuilder($node, $includeSelf = false)
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
        $parent = $wrapped->getPropertyValue($config['parent']);

        $left = $wrapped->getPropertyValue($config['left']);

        $qb = $this->getQueryBuilder();
        $qb->select('node')
            ->from($config['useObjectClass'], 'node')
            ->where($includeSelf ?
                $qb->expr()->lte('node.'.$config['left'], $left) :
                $qb->expr()->lt('node.'.$config['left'], $left)
            )
            ->orderBy("node.{$config['left']}", 'ASC')
        ;
        if ($parent) {
            $wrappedParent = new EntityWrapper($parent, $this->getEntityManager());
            $qb->andWhere($qb->expr()->eq('node.'.$config['parent'], ':pid'));
            $qb->setParameter('pid', $wrappedParent->getIdentifier());
        } elseif (isset($config['root'])) {
            $qb->andWhere($qb->expr()->eq('node.'.$config['root'], ':root'));
            $qb->andWhere($qb->expr()->isNull('node.'.$config['parent']));
            $method = $config['rootIdentifierMethod'];
            $qb->setParameter('root', $node->$method());
        } else {
            $qb->andWhere($qb->expr()->isNull('node.'.$config['parent']));
        }

        return $qb;
    }

    /**
     * Get query for previous siblings of the given $node
     *
     * @param object $node
     * @param bool   $includeSelf include the node itself
     *
     * @throws InvalidArgumentException if input is invalid
     *
     * @return Query
     */
    public function getPrevSiblingsQuery($node, $includeSelf = false)
    {
        return $this->getPrevSiblingsQueryBuilder($node, $includeSelf)->getQuery();
    }

    /**
     * Find the previous siblings of the given $node
     *
     * @param object $node
     * @param bool   $includeSelf include the node itself
     *
     * @return array<int, object>
     */
    public function getPrevSiblings($node, $includeSelf = false)
    {
        return $this->getPrevSiblingsQuery($node, $includeSelf)->getResult();
    }

    /**
     * Move the node down in the same level
     *
     * @param object   $node
     * @param int|bool $number integer - number of positions to shift
     *                         boolean - if "true" - shift till last position
     *
     * @throws \RuntimeException if something fails in transaction
     *
     * @return bool true if shifted
     */
    public function moveDown($node, $number = 1)
    {
        $result = false;
        $meta = $this->getClassMetadata();
        if (is_a($node, $meta->getName())) {
            $nextSiblings = $this->getNextSiblings($node);
            if ($numSiblings = count($nextSiblings)) {
                $result = true;
                if (true === $number) {
                    $number = $numSiblings;
                } elseif ($number > $numSiblings) {
                    $number = $numSiblings;
                }
                $this->listener
                    ->getStrategy($this->getEntityManager(), $meta->getName())
                    ->updateNode($this->getEntityManager(), $node, $nextSiblings[$number - 1], Nested::NEXT_SIBLING);
            }
        } else {
            throw new InvalidArgumentException('Node is not related to this repository');
        }

        return $result;
    }

    /**
     * Move the node up in the same level
     *
     * @param object   $node
     * @param int|bool $number integer - number of positions to shift
     *                         boolean - true shift till first position
     *
     * @throws \RuntimeException if something fails in transaction
     *
     * @return bool true if shifted
     */
    public function moveUp($node, $number = 1)
    {
        $result = false;
        $meta = $this->getClassMetadata();
        if (is_a($node, $meta->getName())) {
            $prevSiblings = array_reverse($this->getPrevSiblings($node));
            if ($numSiblings = count($prevSiblings)) {
                $result = true;
                if (true === $number) {
                    $number = $numSiblings;
                } elseif ($number > $numSiblings) {
                    $number = $numSiblings;
                }
                $this->listener
                    ->getStrategy($this->getEntityManager(), $meta->getName())
                    ->updateNode($this->getEntityManager(), $node, $prevSiblings[$number - 1], Nested::PREV_SIBLING);
            }
        } else {
            throw new InvalidArgumentException('Node is not related to this repository');
        }

        return $result;
    }

    /**
     * UNSAFE: be sure to backup before running this method when necessary
     *
     * Removes given $node from the tree and reparents its descendants
     *
     * @param object $node
     *
     * @throws \RuntimeException if something fails in transaction
     *
     * @return void
     */
    public function removeFromTree($node)
    {
        $meta = $this->getClassMetadata();
        if (is_a($node, $meta->getName())) {
            $wrapped = new EntityWrapper($node, $this->getEntityManager());
            $config = $this->listener->getConfiguration($this->getEntityManager(), $meta->getName());
            $right = $wrapped->getPropertyValue($config['right']);
            $left = $wrapped->getPropertyValue($config['left']);
            $rootId = isset($config['root']) ? $wrapped->getPropertyValue($config['root']) : null;

            // if node has no children
            if ($right == $left + 1) {
                $this->removeSingle($wrapped);
                $this->listener
                    ->getStrategy($this->getEntityManager(), $meta->getName())
                    ->shiftRL($this->getEntityManager(), $config['useObjectClass'], $right, -2, $rootId);

                return; // node was a leaf
            }
            // process updates in transaction
            $this->getEntityManager()->getConnection()->beginTransaction();

            try {
                $parent = $wrapped->getPropertyValue($config['parent']);
                $parentId = null;
                if ($parent) {
                    $wrappedParent = new EntityWrapper($parent, $this->getEntityManager());
                    $parentId = $wrappedParent->getIdentifier();
                }
                $pk = $meta->getSingleIdentifierFieldName();
                $nodeId = $wrapped->getIdentifier();
                $shift = -1;

                // in case if root node is removed, children become roots
                if (isset($config['root']) && !$parent) {
                    // get node's children
                    $qb = $this->getQueryBuilder();
                    $qb->select('node.'.$pk, 'node.'.$config['left'], 'node.'.$config['right'])
                        ->from($config['useObjectClass'], 'node');

                    $qb->andWhere($qb->expr()->eq('node.'.$config['parent'], ':pid'));
                    $qb->setParameter('pid', $nodeId);
                    $nodes = $qb->getQuery()->toIterable([], Query::HYDRATE_ARRAY);

                    // go through each of the node's children
                    foreach ($nodes as $newRoot) {
                        $left = $newRoot[$config['left']];
                        $right = $newRoot[$config['right']];
                        $rootId = $newRoot[$pk];
                        $shift = -($left - 1);

                        // set the root of this child node and its children to the newly formed tree
                        $qb = $this->getQueryBuilder();
                        $qb->update($config['useObjectClass'], 'node');
                        $qb->set('node.'.$config['root'], ':rid');
                        $qb->setParameter('rid', $rootId);
                        $qb->where($qb->expr()->eq('node.'.$config['root'], ':rpid'));
                        $qb->setParameter('rpid', $nodeId);
                        $qb->andWhere($qb->expr()->gte('node.'.$config['left'], $left));
                        $qb->andWhere($qb->expr()->lte('node.'.$config['right'], $right));
                        $qb->getQuery()->getSingleScalarResult();

                        // Set the parent to NULL for this child node, i.e. make it root
                        $qb = $this->getQueryBuilder();
                        $qb->update($config['useObjectClass'], 'node');
                        $qb->set('node.'.$config['parent'], ':pid');
                        $qb->setParameter('pid', $parentId);
                        $qb->where($qb->expr()->eq('node.'.$config['parent'], ':rpid'));
                        $qb->setParameter('rpid', $nodeId);
                        $qb->andWhere($qb->expr()->eq('node.'.$config['root'], ':rid'));
                        $qb->setParameter('rid', $rootId);
                        $qb->getQuery()->getSingleScalarResult();

                        // fix left, right and level values for the newly formed tree
                        $this->listener
                            ->getStrategy($this->getEntityManager(), $meta->getName())
                            ->shiftRangeRL($this->getEntityManager(), $config['useObjectClass'], $left, $right, $shift, $rootId, $rootId, -1);
                        $this->listener
                            ->getStrategy($this->getEntityManager(), $meta->getName())
                            ->shiftRL($this->getEntityManager(), $config['useObjectClass'], $right, -2, $rootId);
                    }
                } else {
                    // set parent of all direct children to be the parent of the node being deleted
                    $qb = $this->getQueryBuilder();
                    $qb->update($config['useObjectClass'], 'node');
                    $qb->set('node.'.$config['parent'], ':pid');
                    $qb->setParameter('pid', $parentId);
                    $qb->where($qb->expr()->eq('node.'.$config['parent'], ':rpid'));
                    $qb->setParameter('rpid', $nodeId);
                    if (isset($config['root'])) {
                        $qb->andWhere($qb->expr()->eq('node.'.$config['root'], ':rid'));
                        $qb->setParameter('rid', $rootId);
                    }
                    $qb->getQuery()->getSingleScalarResult();

                    // fix left, right and level values for the node's children
                    $this->listener
                        ->getStrategy($this->getEntityManager(), $meta->getName())
                        ->shiftRangeRL($this->getEntityManager(), $config['useObjectClass'], $left, $right, $shift, $rootId, $rootId, -1);

                    $this->listener
                        ->getStrategy($this->getEntityManager(), $meta->getName())
                        ->shiftRL($this->getEntityManager(), $config['useObjectClass'], $right, -2, $rootId);
                }
                $this->removeSingle($wrapped);
                $this->getEntityManager()->getConnection()->commit();
            } catch (\Exception $e) {
                $this->getEntityManager()->close();
                $this->getEntityManager()->getConnection()->rollback();

                throw new RuntimeException('Transaction failed', $e->getCode(), $e);
            }
        } else {
            throw new InvalidArgumentException('Node is not related to this repository');
        }
    }

    /**
     * Reorders $node's child nodes,
     * according to the $sortByField and $direction specified
     *
     * @param object|null $node        node from which to start reordering the tree; null will reorder everything
     * @param string      $sortByField field name to sort by
     * @param string      $direction   sort direction : "ASC" or "DESC"
     * @param bool        $verify      true to verify tree first
     * @param bool        $recursive   true to also reorder further descendants, not just the direct children
     *
     * @return void
     */
    public function reorder($node, $sortByField = null, $direction = 'ASC', $verify = true, $recursive = true)
    {
        $meta = $this->getClassMetadata();
        if (null === $node || is_a($node, $meta->getName())) {
            $config = $this->listener->getConfiguration($this->getEntityManager(), $meta->getName());
            if ($verify && is_array($this->verify())) {
                return;
            }

            $nodes = $this->children($node, true, $sortByField, $direction);
            foreach ($nodes as $node) {
                $wrapped = new EntityWrapper($node, $this->getEntityManager());
                $right = $wrapped->getPropertyValue($config['right']);
                $left = $wrapped->getPropertyValue($config['left']);
                $this->moveDown($node, true);
                if ($recursive && $left != ($right - 1)) {
                    $this->reorder($node, $sortByField, $direction, false);
                }
            }
        } else {
            throw new InvalidArgumentException('Node is not related to this repository');
        }
    }

    /**
     * Reorders all nodes in the tree according to the $sortByField and $direction specified.
     *
     * @param string $sortByField field name to sort by
     * @param string $direction   sort direction : "ASC" or "DESC"
     * @param bool   $verify      true to verify tree first
     *
     * @return void
     */
    public function reorderAll($sortByField = null, $direction = 'ASC', $verify = true)
    {
        $this->reorder(null, $sortByField, $direction, $verify);
    }

    /**
     * Verifies that current tree is valid.
     * If any error is detected it will return an array
     * with a list of errors found on tree
     *
     * @phpstan-param array{treeRootNode?: object} $options
     *
     * options:
     * - treeRootNode: (object) Optional tree root node to verify, if not the whole forest (only available for forests, not for single trees).
     *
     * @return array<int, string>|bool true on success, error list on failure
     */
    public function verify(/* array $options = [] */) // @phpstan-ignore-line
    {
        $options = func_get_args()[0] ?? [];
        if (!\is_array($options)) {
            throw new \TypeError('Argument 1 MUST be an array.');
        }

        $defaultOptions = [
            'treeRootNode' => null,
        ];
        $options += $defaultOptions;

        if (!$this->childCount()) {
            return true; // tree is empty
        }

        $errors = [];
        $meta = $this->getClassMetadata();
        $config = $this->listener->getConfiguration($this->getEntityManager(), $meta->getName());
        if (isset($config['root'])) {
            $trees = $this->getRootNodes();
            foreach ($trees as $tree) {
                // if a root node is specified, verify only it
                if (null !== $options['treeRootNode'] && $options['treeRootNode'] !== $tree) {
                    continue;
                }
                $this->verifyTree($errors, $tree);
            }
        } else {
            $this->verifyTree($errors);
        }

        return [] !== $errors ? $errors : true;
    }

    /**
     * Tries to recover the tree, avoiding entity object hydration and using DQL
     *
     * NOTE: DQL UPDATE statements are ported directly into a Database UPDATE statement and therefore bypass any locking
     * scheme, events and do not increment the version column. Entities that are already loaded into the persistence
     * context will NOT be synced with the updated database state.
     * It is recommended to call EntityManager#clear() and retrieve new instances of any affected entity.
     *
     * @phpstan-param array{sortByField?: string, sortDirection?: string} $options
     *
     * options:
     * - sortByField:   (string) Optionally sort siblings by specified field while recovering. Defaults to null.
     * - sortDirection: (string) The order to sort siblings in, when sortByField is specified ('ASC', 'DESC'). Defaults to 'ASC'.
     *
     * @throws ORMException
     */
    public function recoverFast(array $options = []): void
    {
        $defaultOptions = [
            'sortByField' => null,
            'sortDirection' => 'ASC',
        ];
        $options += $defaultOptions;

        $meta = $this->getClassMetadata();
        $config = $this->listener->getConfiguration($this->getEntityManager(), $meta->name);
        $em = $this->getEntityManager();

        $updateQb = $em->createQueryBuilder()
                       ->update($meta->getName(), 'node')
                       ->set('node.'.$config['left'], ':left')
                       ->set('node.'.$config['right'], ':right')
                       ->where('node.id = :id');
        if (isset($config['level'])) {
            $updateQb->set('node.'.$config['level'], ':level');
        }

        $doRecover = function (array $root, int &$count, int $level) use ($meta, $em, $options, $updateQb, &$doRecover): void {
            $rootEntity = $em->getReference($meta->getName(), $root['node_id']);
            $left = $count++;
            $childrenQuery = $this->getChildrenQuery($rootEntity, true, $options['sortByField'], $options['sortDirection']);
            foreach ($childrenQuery->getScalarResult() as $child) {
                $doRecover($child, $count, $level + 1);
            }
            $right = $count++;

            $updateQb
                ->setParameter('left', $left)
                ->setParameter('right', $right)
                ->setParameter('id', $root['node_id'])
                ->setParameter('level', $level)
                ->getQuery()->execute();
        };

        // if it's a forest
        if (isset($config['root'])) {
            $rootNodesQuery = $this->getRootNodesQuery($options['sortByField'], $options['sortDirection']);
            $roots = $rootNodesQuery->getScalarResult();
            foreach ($roots as $root) {
                // reset on every root node
                $count = 1;
                $level = $config['level_base'] ?? 0;
                $doRecover($root, $count, $level);
                $em->clear();
            }
        } else {
            $count = 1;
            $level = $config['level_base'] ?? 0;
            $childrenQuery = $this->getChildrenQuery(null, true, $options['sortByField'], $options['sortDirection']);
            foreach ($childrenQuery->getScalarResult() as $root) {
                $doRecover($root, $count, $level);
                $em->clear();
            }
        }
    }

    /**
     * NOTE: flush your entity manager after, unless the 'flush' option has been set to true
     *
     * Tries to recover the tree
     *
     * @phpstan-param array{
     *     flush?: bool,
     *     treeRootNode?: ?object,
     *     skipVerify?: bool,
     *     sortByField?: string,
     *     sortDirection?: string
     * } $options
     *
     * options:
     * - flush:         (bool)   Flush entity manager after each root node is recovered. Defaults to false.
     * - treeRootNode:  (object) Optional tree root node to recover, if not the whole forest (only available for forests, not for single trees). Defaults to null.
     * - skipVerify:    (bool)   Whether to skip verification and recover anyway. Defaults to false.
     * - sortByField:   (string) Optionally sort siblings by specified field while recovering. Defaults to null.
     * - sortDirection: (string) The order to sort siblings in, when sortByField is specified ('ASC', 'DESC'). Defaults to 'ASC'.
     *
     * @return void
     */
    public function recover(/* array $options = [] */) // @phpstan-ignore-line
    {
        $options = func_get_args()[0] ?? [];
        if (!\is_array($options)) {
            throw new \TypeError('Argument 1 MUST be an array.');
        }

        $defaultOptions = [
            'flush' => false,
            'treeRootNode' => null,
            'skipVerify' => false,
            'sortByField' => null,
            'sortDirection' => 'ASC',
        ];
        $options += $defaultOptions;

        if (!$options['skipVerify'] && (true === $this->verify())) {
            return;
        }

        $meta = $this->getClassMetadata();
        $config = $this->listener->getConfiguration($this->getEntityManager(), $meta->getName());
        $em = $this->getEntityManager();

        $doRecover = function ($root, &$count, &$lvl) use ($meta, $config, $em, $options, &$doRecover) {
            $left = $count++;
            foreach ($this->getChildren($root, true, $options['sortByField'], $options['sortDirection']) as $child) {
                $depth = ($lvl + 1);
                $doRecover($child, $count, $depth);
            }
            $right = $count++;
            $meta->getReflectionProperty($config['left'])->setValue($root, $left);
            $meta->getReflectionProperty($config['right'])->setValue($root, $right);
            if (isset($config['level'])) {
                $meta->getReflectionProperty($config['level'])->setValue($root, $lvl);
            }
            $em->persist($root);
        };

        // if it's a forest
        if (isset($config['root'])) {
            foreach ($this->getRootNodes($options['sortByField'], $options['sortDirection']) as $root) {
                // if a root node is specified, recover only it
                if (null !== $options['treeRootNode'] && $options['treeRootNode'] !== $root) {
                    continue;
                }

                $count = 1; // reset on every root node
                $lvl = $config['level_base'] ?? 0;
                $doRecover($root, $count, $lvl);

                if ($options['flush']) {
                    $em->flush();
                }
            }
        } else {
            $count = 1;
            $lvl = $config['level_base'] ?? 0;
            foreach ($this->getChildren(null, true, $options['sortByField'], $options['sortDirection']) as $root) {
                $doRecover($root, $count, $lvl);

                if ($options['flush']) {
                    $em->flush();
                }
            }
        }
    }

    public function getNodesHierarchyQueryBuilder($node = null, $direct = false, array $options = [], $includeNode = false)
    {
        $meta = $this->getClassMetadata();
        $config = $this->listener->getConfiguration($this->getEntityManager(), $meta->getName());

        return $this->childrenQueryBuilder(
            $node,
            $direct,
            isset($config['root']) ? [$config['root'], $config['left']] : $config['left'],
            'ASC',
            $includeNode
        );
    }

    public function getNodesHierarchyQuery($node = null, $direct = false, array $options = [], $includeNode = false)
    {
        return $this->getNodesHierarchyQueryBuilder($node, $direct, $options, $includeNode)->getQuery();
    }

    public function getNodesHierarchy($node = null, $direct = false, array $options = [], $includeNode = false)
    {
        return $this->getNodesHierarchyQuery($node, $direct, $options, $includeNode)->getArrayResult();
    }

    /**
     * Allows the following 'virtual' methods:
     * - persistAsFirstChild($node)
     * - persistAsFirstChildOf($node, $parent)
     * - persistAsLastChild($node)
     * - persistAsLastChildOf($node, $parent)
     * - persistAsNextSibling($node)
     * - persistAsNextSiblingOf($node, $sibling)
     * - persistAsPrevSibling($node)
     * - persistAsPrevSiblingOf($node, $sibling)
     * Inherited virtual methods:
     * - find*
     *
     * @param string $method
     * @param array  $args
     *
     * @phpstan-param list<mixed> $args
     *
     * @throws \BadMethodCallException  If the method called is an invalid find* or persistAs* method
     *                                  or no find* either persistAs* method at all and therefore an invalid method call
     * @throws InvalidArgumentException If arguments are invalid
     *
     * @return mixed TreeNestedRepository if persistAs* is called
     *
     * @see \Doctrine\ORM\EntityRepository
     */
    protected function doCallWithCompat($method, $args)
    {
        if ('persistAs' === substr($method, 0, 9)) {
            if (!isset($args[0])) {
                throw new InvalidArgumentException('Node to persist must be available as first argument.');
            }
            $node = $args[0];
            $wrapped = new EntityWrapper($node, $this->getEntityManager());
            $meta = $this->getClassMetadata();
            $config = $this->listener->getConfiguration($this->getEntityManager(), $meta->getName());
            $position = substr($method, 9);
            if ('Of' === substr($method, -2)) {
                if (!isset($args[1])) {
                    throw new InvalidArgumentException('If "Of" is specified you must provide parent or sibling as the second argument.');
                }
                $parentOrSibling = $args[1];
                if (strstr($method, 'Sibling')) {
                    $wrappedParentOrSibling = new EntityWrapper($parentOrSibling, $this->getEntityManager());
                    $newParent = $wrappedParentOrSibling->getPropertyValue($config['parent']);
                    if (null === $newParent && isset($config['root'])) {
                        throw new UnexpectedValueException('Cannot persist sibling for a root node, tree operation is not possible');
                    }

                    if (!$node instanceof Node) {
                        Deprecation::trigger(
                            'gedmo/doctrine-extensions',
                            'https://github.com/doctrine-extensions/DoctrineExtensions/pull/2547',
                            'Not implementing the "%s" interface from node "%s" is deprecated since gedmo/doctrine-extensions'
                            .' 3.13 and will throw a "%s" error in version 4.0.',
                            Node::class,
                            \get_class($node),
                            \TypeError::class
                        );
                    }

                    // @todo: In the next major release, remove the previous condition and uncomment the following one.

                    // if (!$node instanceof Node) {
                    //     throw new \TypeError(\sprintf(
                    //         'Node MUST implement "%s" interface.',
                    //         Node::class
                    //     ));
                    // }

                    // @todo: In the next major release, remove the `method_exists()` condition and left the `else` branch.
                    if (!method_exists($node, 'setSibling')) {
                        $node->sibling = $parentOrSibling;
                    } else {
                        $node->setSibling($parentOrSibling);
                    }
                    $parentOrSibling = $newParent;
                }
                $wrapped->setPropertyValue($config['parent'], $parentOrSibling);
                $position = substr($position, 0, -2);
            }
            $wrapped->setPropertyValue($config['left'], 0); // simulate changeset
            $oid = spl_object_id($node);
            $this->listener
                ->getStrategy($this->getEntityManager(), $meta->getName())
                ->setNodePosition($oid, $position)
            ;

            $this->getEntityManager()->persist($node);

            return $this;
        }

        return parent::__call($method, $args);
    }

    protected function validate()
    {
        return Strategy::NESTED === $this->listener->getStrategy($this->getEntityManager(), $this->getClassMetadata()->name)->getName();
    }

    /**
     * Collect errors on given tree if
     * where are any
     *
     * @param array<int, string> $errors
     */
    private function verifyTree(array &$errors, ?object $root = null): void
    {
        $meta = $this->getClassMetadata();
        $config = $this->listener->getConfiguration($this->getEntityManager(), $meta->getName());

        $identifier = $meta->getSingleIdentifierFieldName();
        if (isset($config['root'])) {
            $rootId = $meta->getReflectionProperty($config['root'])->getValue($root);
            if (is_object($rootId)) {
                $rootId = $meta->getReflectionProperty($identifier)->getValue($rootId);
            }
        } else {
            $rootId = null;
        }

        $qb = $this->getQueryBuilder();
        $qb->select($qb->expr()->min('node.'.$config['left']))
            ->from($config['useObjectClass'], 'node')
        ;
        if (isset($config['root'])) {
            $qb->where($qb->expr()->eq('node.'.$config['root'], ':rid'));
            $qb->setParameter('rid', $rootId);
        }
        $min = (int) $qb->getQuery()->getSingleScalarResult();
        $edge = $this->listener->getStrategy($this->getEntityManager(), $meta->getName())->max($this->getEntityManager(), $config['useObjectClass'], $rootId);
        // check duplicate right and left values
        for ($i = $min; $i <= $edge; ++$i) {
            $qb = $this->getQueryBuilder();
            $qb->select($qb->expr()->count('node.'.$identifier))
                ->from($config['useObjectClass'], 'node')
                ->where($qb->expr()->orX(
                    $qb->expr()->eq('node.'.$config['left'], $i),
                    $qb->expr()->eq('node.'.$config['right'], $i)
                ))
            ;
            if (isset($config['root'])) {
                $qb->andWhere($qb->expr()->eq('node.'.$config['root'], ':rid'));
                $qb->setParameter('rid', $rootId);
            }
            $count = (int) $qb->getQuery()->getSingleScalarResult();
            if (1 !== $count) {
                if (0 === $count) {
                    $errors[] = "index [{$i}], missing".($root ? ' on tree root: '.$rootId : '');
                } else {
                    $errors[] = "index [{$i}], duplicate".($root ? ' on tree root: '.$rootId : '');
                }
            }
        }
        // check for missing parents
        $qb = $this->getQueryBuilder();
        $qb->select('node')
            ->from($config['useObjectClass'], 'node')
            ->leftJoin('node.'.$config['parent'], 'parent')
            ->where($qb->expr()->isNotNull('node.'.$config['parent']))
            ->andWhere($qb->expr()->isNull('parent.'.$identifier))
        ;
        if (isset($config['root'])) {
            $qb->andWhere($qb->expr()->eq('node.'.$config['root'], ':rid'));
            $qb->setParameter('rid', $rootId);
        }

        $areMissingParents = false;

        foreach ($qb->getQuery()->toIterable([], Query::HYDRATE_ARRAY) as $node) {
            $areMissingParents = true;
            $errors[] = "node [{$node[$identifier]}] has missing parent".($root ? ' on tree root: '.$rootId : '');
        }

        // loading broken relation can cause infinite loop
        if ($areMissingParents) {
            return;
        }

        // check for nodes that have a right value lower than the left
        $qb = $this->getQueryBuilder();
        $qb->select('node')
            ->from($config['useObjectClass'], 'node')
            ->where($qb->expr()->lt('node.'.$config['right'], 'node.'.$config['left']))
        ;
        if (isset($config['root'])) {
            $qb->andWhere($qb->expr()->eq('node.'.$config['root'], ':rid'));
            $qb->setParameter('rid', $rootId);
        }
        $result = $qb->getQuery()
            ->setMaxResults(1)
            ->getResult(Query::HYDRATE_ARRAY);
        $node = [] !== $result ? array_shift($result) : [];

        if ([] !== $node) {
            $id = $node[$identifier];
            $errors[] = "node [{$id}], left is greater than right".($root ? ' on tree root: '.$rootId : '');
        }

        $qb = $this->getQueryBuilder();
        $qb->select('node')
            ->from($config['useObjectClass'], 'node')
        ;
        if (isset($config['root'])) {
            $qb->andWhere($qb->expr()->eq('node.'.$config['root'], ':rid'));
            $qb->setParameter('rid', $rootId);
        }

        foreach ($qb->getQuery()->toIterable() as $node) {
            $right = $meta->getReflectionProperty($config['right'])->getValue($node);
            $left = $meta->getReflectionProperty($config['left'])->getValue($node);
            $id = $meta->getReflectionProperty($identifier)->getValue($node);
            $parent = $meta->getReflectionProperty($config['parent'])->getValue($node);
            if (!$right || !$left) {
                $errors[] = "node [{$id}] has invalid left or right values";
            } elseif ($right == $left) {
                $errors[] = "node [{$id}] has identical left and right values";
            } elseif ($parent) {
                if ($parent instanceof Proxy && !$parent->__isInitialized()) {
                    $this->getEntityManager()->refresh($parent);
                }
                $parentRight = $meta->getReflectionProperty($config['right'])->getValue($parent);
                $parentLeft = $meta->getReflectionProperty($config['left'])->getValue($parent);
                $parentId = $meta->getReflectionProperty($identifier)->getValue($parent);
                if ($left < $parentLeft) {
                    $errors[] = "node [{$id}] left is less than parent`s [{$parentId}] left value";
                } elseif ($right > $parentRight) {
                    $errors[] = "node [{$id}] right is greater than parent`s [{$parentId}] right value";
                }
                // check that level of node is exactly after its parent's level
                if (isset($config['level'])) {
                    $parentLevel = $meta->getReflectionProperty($config['level'])->getValue($parent);
                    $level = $meta->getReflectionProperty($config['level'])->getValue($node);
                    if ($level !== $parentLevel + 1) {
                        $errors[] = "node [{$id}] should be on the level right after its parent`s [{$parentId}] level";
                    }
                }
            } else {
                // check that level of the root node is the base level defined
                if (isset($config['level'])) {
                    $baseLevel = $config['level_base'] ?? 0;
                    $level = $meta->getReflectionProperty($config['level'])->getValue($node);
                    if ($level !== $baseLevel) {
                        $errors[] = "node [{$id}] should be on level {$baseLevel}, not {$level}";
                    }
                }

                // get number of parents of node, based on left and right values
                $qb = $this->getQueryBuilder();
                $qb->select($qb->expr()->count('node.'.$identifier))
                    ->from($config['useObjectClass'], 'node')
                    ->where($qb->expr()->lt('node.'.$config['left'], $left))
                    ->andWhere($qb->expr()->gt('node.'.$config['right'], $right))
                ;
                if (isset($config['root'])) {
                    $qb->andWhere($qb->expr()->eq('node.'.$config['root'], ':rid'));
                    $qb->setParameter('rid', $rootId);
                }
                if ($count = (int) $qb->getQuery()->getSingleScalarResult()) {
                    $errors[] = "node [{$id}] parent field is blank, but it has a parent";
                }
            }
        }
    }

    /**
     * Removes single node without touching children
     *
     * @param EntityWrapper<object> $wrapped
     *
     * @internal
     */
    private function removeSingle(EntityWrapper $wrapped): void
    {
        $meta = $this->getClassMetadata();
        $config = $this->listener->getConfiguration($this->getEntityManager(), $meta->getName());

        $pk = $meta->getSingleIdentifierFieldName();
        $nodeId = $wrapped->getIdentifier();
        // prevent from deleting whole branch
        $qb = $this->getQueryBuilder();
        $qb->update($config['useObjectClass'], 'node')
            ->set('node.'.$config['left'], 0)
            ->set('node.'.$config['right'], 0);

        $qb->andWhere($qb->expr()->eq('node.'.$pk, ':id'));
        $qb->setParameter('id', $nodeId);
        $qb->getQuery()->getSingleScalarResult();

        // remove the node from database
        $qb = $this->getQueryBuilder();
        $qb->delete($config['useObjectClass'], 'node');
        $qb->andWhere($qb->expr()->eq('node.'.$pk, ':id'));
        $qb->setParameter('id', $nodeId);
        $qb->getQuery()->getSingleScalarResult();

        // remove from identity map
        $this->getEntityManager()->getUnitOfWork()->removeFromIdentityMap($wrapped->getObject());
    }
}
