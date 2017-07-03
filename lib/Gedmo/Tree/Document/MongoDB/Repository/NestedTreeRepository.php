<?php

namespace Gedmo\Tree\Document\MongoDB\Repository;

use Doctrine\ODM\MongoDB\Query\Builder;
use Doctrine\ODM\MongoDB\Query\Query;
use Gedmo\Exception\InvalidArgumentException;
use Gedmo\Exception\UnexpectedValueException;
use Gedmo\Tool\Wrapper\MongoDocumentWrapper;
use Gedmo\Tree\Strategy;
use Gedmo\Tree\Strategy\ODM\MongoDB\Nested;

/**
 * The NestedTreeRepository has some useful functions
 * to interact with NestedSet tree. Repository uses
 * the strategy used by listener
 *
 * @author Litvinenko Andrey <andreylit@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
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
    /**
     * {@inheritDoc}
     */
    public function getRootNodesQueryBuilder($sortByField = null, $direction = 'asc')
    {
        $meta = $this->getClassMetadata();
        $config = $this->listener->getConfiguration($this->dm, $meta->name);
        $qb = $this->createQueryBuilder();

        $qb->field($config['parent'])->equals(null);

        if ($sortByField !== null) {
            $qb->sort($sortByField, strtolower($direction) === 'asc' ? 1 : -1);
        } else {
            $qb->sort($config['left']);
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
        return $this->getRootNodesQuery($sortByField, $direction)->execute();
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
     * @see \Doctrine\ODM\MongoDB\DocumentRepository
     *
     * @throws InvalidArgumentException - If arguments are invalid
     * @throws \BadMethodCallException  - If the method called is an invalid find* or persistAs* method
     *                                  or no find* either persistAs* method at all and therefore an invalid method call.
     *
     * @return mixed - TreeNestedRepository if persistAs* is called
     */
    public function __call($method, $args)
    {
        if (substr($method, 0, 9) === 'persistAs') {
            if (!isset($args[0])) {
                throw new InvalidArgumentException('Node to persist must be available as first argument');
            }
            $node = $args[0];

            $wrapped = new MongoDocumentWrapper($node, $this->dm);
            $meta = $this->getClassMetadata();
            $config = $this->listener->getConfiguration($this->dm, $meta->name);

            $position = substr($method, 9);

            if (substr($method, -2) === 'Of') {
                if (!isset($args[1])) {
                    throw new InvalidArgumentException('If "Of" is specified you must provide parent or sibling as the second argument');
                }

                $parentOrSibling = $args[1];

                if (strstr($method,'Sibling')) {
                    $wrappedParentOrSibling = new MongoDocumentWrapper($parentOrSibling, $this->dm);
                    $newParent = $wrappedParentOrSibling->getPropertyValue($config['parent']);

                    if (null === $newParent && isset($config['root'])) {
                        throw new UnexpectedValueException("Cannot persist sibling for a root node, tree operation is not possible");
                    }

                    $node->sibling = $parentOrSibling;
                    $parentOrSibling = $newParent;
                }
                $wrapped->setPropertyValue($config['parent'], $parentOrSibling);
                $position = substr($position, 0, -2);
            }
            $wrapped->setPropertyValue($config['left'], 0); // simulate changeset

            $oid = spl_object_hash($node);
            $this->listener
                ->getStrategy($this->dm, $meta->name)
                ->setNodePosition($oid, $position)
            ;

            $this->dm->persist($node);

            return $this;
        }

        return parent::__call($method, $args);
    }

    /**
     * Get the Tree path query builder by given $node
     *
     * @param object $node
     *
     * @throws InvalidArgumentException - if input is not valid
     *
     * @return \Doctrine\ODM\MongoDB\Query\Builder
     */
    public function getPathQueryBuilder($node)
    {
        $meta = $this->getClassMetadata();
        if (!$node instanceof $meta->name) {
            throw new InvalidArgumentException("Node is not related to this repository");
        }

        $config = $this->listener->getConfiguration($this->dm, $meta->name);
        $wrapped = new MongoDocumentWrapper($node, $this->dm);

        if (!$wrapped->hasValidIdentifier()) {
            throw new InvalidArgumentException("Node is not managed by UnitOfWork");
        }
        $left = $wrapped->getPropertyValue($config['left']);
        $right = $wrapped->getPropertyValue($config['right']);

        $qb = $this
            ->createQueryBuilder()
            ->field($config['left'])->lte($left)
            ->field($config['right'])->gte($right)
        ;

        if (isset($config['root'])) {
            $rootId = $wrapped->getPropertyValue($config['root']);
            $qb->field($config['root'])->equals($rootId);
        }

        $qb->sort($config['left'], 1);

        return $qb;
    }

    /**
     * Get the Tree path query by given $node
     *
     * @param object $node
     *
     * @return \Doctrine\ODM\MongoDB\Query\Query
     */
    public function getPathQuery($node)
    {
        return $this->getPathQueryBuilder($node)->getQuery();
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
        return $this->getPathQuery($node)->execute();
    }

    /**
     * @see getChildrenQueryBuilder
     */
    public function childrenQueryBuilder($node = null, $direct = false, $sortByField = null, $direction = 'ASC', $includeNode = false)
    {
        $meta = $this->getClassMetadata();
        $config = $this->listener->getConfiguration($this->dm, $meta->name);

        $qb = $this->createQueryBuilder();

        if ($node !== null) {
            if ($node instanceof $meta->name) {
                $wrapped = new MongoDocumentWrapper($node, $this->dm);

                if (!$wrapped->hasValidIdentifier()) {
                    throw new InvalidArgumentException("Node is not managed by UnitOfWork");
                }

                if ($direct) {
                    $qb->field($config['parent'])->equals($wrapped->getIdentifier());
                } else {
                    $left = $wrapped->getPropertyValue($config['left']);
                    $right = $wrapped->getPropertyValue($config['right']);

                    if ($left && $right) {
                        $qb
                            ->field($config['right'])->lt($right)
                            ->field($config['left'])->gt($left)
                        ;
                    }
                }

                if (isset($config['root'])) {
                    $qb->field($config['root'])->equals($wrapped->getPropertyValue($config['root']));
                }
                if ($includeNode) {
                    $idField = $meta->getIdentifierFieldNames()[0];

                    $qb->addOr($qb->expr()->field($idField)->references($node));
                }
            } else {
                throw new \InvalidArgumentException("Node is not related to this repository");
            }
        } else {
            if ($direct) {
                $qb->field($config['parent'])->equals(null);
            }
        }

        if (!$sortByField) {
            $qb->sort($config['left'], 1);
        } elseif (is_array($sortByField)) {
            $qb->sort($sortByField);
        } else {
            if ($meta->hasField($sortByField) && in_array(strtolower($direction), array('asc', 'desc', 1, -1))) {
                $qb->sort($sortByField, $direction);
            } else {
                throw new InvalidArgumentException("Invalid sort options specified: field - {$sortByField}, direction - {$direction}");
            }
        }

        return $qb;
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
        $q = $this->childrenQuery($node, $direct, $sortByField, $direction, $includeNode);

        return $q->execute();
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
     * {@inheritDoc}
     */
    public function getNodesHierarchyQueryBuilder($node = null, $direct = false, array $options = array(), $includeNode = false)
    {
        $meta = $this->getClassMetadata();
        $config = $this->listener->getConfiguration($this->dm, $meta->name);

        return $this->childrenQueryBuilder(
            $node,
            $direct,
            isset($config['root']) ? array($config['root'], $config['left']) : $config['left'],
            'ASC',
            $includeNode
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getNodesHierarchyQuery($node = null, $direct = false, array $options = array(), $includeNode = false)
    {
        return $this->getNodesHierarchyQueryBuilder($node, $direct, $options, $includeNode)->getQuery();
    }

    /**
     * {@inheritdoc}
     */
    public function getNodesHierarchy($node = null, $direct = false, array $options = array(), $includeNode = false)
    {
        return $this->getNodesHierarchyQuery($node, $direct, $options, $includeNode)->execute();
    }

    /**
     * {@inheritdoc}
     */
    protected function validate()
    {
        return $this->listener->getStrategy($this->dm, $this->getClassMetadata()->name)->getName() === Strategy::NESTED;
    }

    public function childCount($node = null, $direct = false)
    {
        $meta = $this->getClassMetadata();

        if (is_object($node)) {
            if (!($node instanceof $meta->name)) {
                throw new InvalidArgumentException("Node is not related to this repository");
            }

            $wrapped = new MongoDocumentWrapper($node, $this->dm);

            if (!$wrapped->hasValidIdentifier()) {
                throw new InvalidArgumentException("Node is not managed by UnitOfWork");
            }
        }

        $qb = $this->getChildrenQueryBuilder($node, $direct);

        $count = $qb->getQuery()->execute()->count();

        return $count;
    }

    /**
     * Get the query builder for next siblings of the given $node
     *
     * @param object $node
     * @param bool   $includeSelf - include the node itself
     *
     * @throws \Gedmo\Exception\InvalidArgumentException - if input is invalid
     *
     * @return Builder
     */
    public function getNextSiblingsQueryBuilder($node, $includeSelf = false)
    {
        $meta = $this->getClassMetadata();
        if (!$node instanceof $meta->name) {
            throw new InvalidArgumentException("Node is not related to this repository");
        }

        $wrapped = new MongoDocumentWrapper($node, $this->dm);
        if (!$wrapped->hasValidIdentifier()) {
            throw new InvalidArgumentException("Node is not managed by UnitOfWork");
        }

        $config = $this->listener->getConfiguration($this->dm, $meta->name);
        $parent = $wrapped->getPropertyValue($config['parent']);

        if (isset($config['root']) && !$parent) {
            throw new InvalidArgumentException("Cannot get siblings from tree root node");
        }

        $left = $wrapped->getPropertyValue($config['left']);

        $qb = $this->createQueryBuilder();
        if ($includeSelf) {
            $qb->field($config['left'])->gte($left);
        } else {
            $qb->field($config['left'])->gt($left);
        }
        $qb->sort($config['left'], 1);

        if ($parent) {
            $wrappedParent = new MongoDocumentWrapper($parent, $this->dm);
            $qb->field($config['parent'])->equals($wrappedParent->getIdentifier());
        } else {
            // todo: check null? может быть надо проверять еще на существование поля
            $qb->field($config['parent'])->equals(null);
        }

        return $qb;
    }

    /**
     * Get the query for next siblings of the given $node
     *
     * @param object $node
     * @param bool   $includeSelf - include the node itself
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
     * @param bool   $includeSelf - include the node itself
     *
     * @return array
     */
    public function getNextSiblings($node, $includeSelf = false)
    {
        return $this->getNextSiblingsQuery($node, $includeSelf)->toArray();
    }

    /**
     * Get query builder for previous siblings of the given $node
     *
     * @param object $node
     * @param bool   $includeSelf - include the node itself
     *
     * @throws \Gedmo\Exception\InvalidArgumentException - if input is invalid
     *
     * @return Builder
     */
    public function getPrevSiblingsQueryBuilder($node, $includeSelf = false)
    {
        $meta = $this->getClassMetadata();
        if (!$node instanceof $meta->name) {
            throw new InvalidArgumentException("Node is not related to this repository");
        }

        $wrapped = new MongoDocumentWrapper($node, $this->dm);
        if (!$wrapped->hasValidIdentifier()) {
            throw new InvalidArgumentException("Node is not managed by UnitOfWork");
        }

        $config = $this->listener->getConfiguration($this->dm, $meta->name);
        $parent = $wrapped->getPropertyValue($config['parent']);

        if (isset($config['root']) && !$parent) {
            throw new InvalidArgumentException("Cannot get siblings from tree root node");
        }

        $left = $wrapped->getPropertyValue($config['left']);

        $qb = $this->createQueryBuilder();
        if ($includeSelf) {
            $qb->field($config['left'])->lte($left);
        } else {
            $qb->field($config['left'])->lt($left);
        }
        $qb->sort($config['left'], 1);

        if ($parent) {
            $wrappedParent = new MongoDocumentWrapper($parent, $this->dm);
            $qb->field($config['parent'])->equals($wrappedParent->getIdentifier());
        } else {
            // todo: check null? может быть надо проверять еще на существование поля
            $qb->field($config['parent'])->equals(null);
        }

        return $qb;
    }

    /**
     * Get query for previous siblings of the given $node
     *
     * @param object $node
     * @param bool   $includeSelf - include the node itself
     *
     * @throws \Gedmo\Exception\InvalidArgumentException - if input is invalid
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
     * @param bool   $includeSelf - include the node itself
     *
     * @return array
     */
    public function getPrevSiblings($node, $includeSelf = false)
    {
        return $this->getPrevSiblingsQuery($node, $includeSelf)->toArray();
    }

    /**
     * Move the node down in the same level
     *
     * @param object   $node
     * @param int|bool $number integer - number of positions to shift
     *                         boolean - if "true" - shift till last position
     *
     * @throws \RuntimeException - if something fails in transaction
     *
     * @return boolean - true if shifted
     */
    public function moveDown($node, $number = 1)
    {
        $result = false;
        $meta = $this->getClassMetadata();
        if ($node instanceof $meta->name) {
            $nextSiblings = array_values($this->getNextSiblings($node));

            if ($numSiblings = count($nextSiblings)) {
                $result = true;
                if ($number === true) {
                    $number = $numSiblings;
                } elseif ($number > $numSiblings) {
                    $number = $numSiblings;
                }

                $this->listener
                    ->getStrategy($this->dm, $meta->name)
                    ->updateNode($this->dm, $node, $nextSiblings[$number - 1], Nested::NEXT_SIBLING);
            }
        } else {
            throw new InvalidArgumentException("Node is not related to this repository");
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
     * @throws \RuntimeException - if something fails in transaction
     *
     * @return boolean - true if shifted
     */
    public function moveUp($node, $number = 1)
    {
        $result = false;
        $meta = $this->getClassMetadata();
        if ($node instanceof $meta->name) {
            $prevSiblings = array_reverse(array_values($this->getPrevSiblings($node)));
            if ($numSiblings = count($prevSiblings)) {
                $result = true;
                if ($number === true) {
                    $number = $numSiblings;
                } elseif ($number > $numSiblings) {
                    $number = $numSiblings;
                }
                $this->listener
                    ->getStrategy($this->dm, $meta->name)
                    ->updateNode($this->dm, $node, $prevSiblings[$number - 1], Nested::PREV_SIBLING);
            }
        } else {
            throw new InvalidArgumentException("Node is not related to this repository");
        }

        return $result;
    }
}
