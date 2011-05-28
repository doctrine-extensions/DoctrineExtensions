<?php

/*
 * This file is part of the DoctrineExtensions library.
 *
 * (c) 2011 Gediminas Morkevifcius
 * (c) Funsational <info@funsational.com>
 *
 * This source file is subject to the LICENSE located in
 * the root directory of this distrubution.
 */

namespace Gedmo\Tree\Strategy\ODM;

use Gedmo\Tree\Strategy;
use Gedmo\Tree\TreeListener;
use Gedmo\Tree\Node;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Proxy\Proxy;

/**
 * Main class for implementing the materialized path structure
 * with MongoDB and Doctrine
 *
 * @todo    When inserting nodes, it is possible to persist a child node before
 *          the parent has been inserted or exists in the DB. There needs to be
 *          check added in the the processScheduledInserts() to check for this.
 *          However, since most of the times nodes will not be inserted in batches
 *          and therefore you have a better chance of the node existing already,
 *          this check in the DB should be optional so that queries on inserts
 *          can be as small as possible. @See the
 *          MaterializedPathTest::testInsertChildWithInvalidParent() when this is added.
 *
 * @author Michael Williams <michael.williams@funsational.com>
 */
class Path implements Strategy
{
    /**
     * Previous sibling position
     */
    const PREV_SIBLING = 'PrevSibling';

    /**
     * Next sibling position
     */
    const NEXT_SIBLING = 'NextSibling';

    /**
     * Last child position
     */
    const LAST_CHILD = 'LastChild';

    /**
     * First child position
     */
    const FIRST_CHILD = 'FirstChild';

    /**
     * Represents the stragey name
     *
     * @var string
     */
    const PATH = 'path';

    /**
     * TreeListener
     *
     * @var AbstractTreeListener
     */
    protected $listener = null;

    /**
     * Stores a list of node position strategies
     * for each node by object hash
     *
     * @var array
     */
    private $nodePositions = array();

    /**
     * Holds an array indexed by parent object hash containg all the children
     * the parent is going to have inserted so sort orders can be determined
     * correctly.
     *
     * @var array
     */
    private $pendingChildrenNodes = array();

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
        return self::PATH;
    }

    /**
     * Set node position strategy
     *
     * @param string $oid
     * @param string $position
     */
    public function setNodePosition($oid, $position)
    {
        $valid = array(
            self::FIRST_CHILD,
            self::LAST_CHILD,
            self::NEXT_SIBLING,
            self::PREV_SIBLING
        );

        if (!in_array($position, $valid, false)) {
            throw new \Gedmo\Exception\InvalidArgumentException("Position: {$position} is not valid in nested set tree");
        }

        $this->nodePositions[$oid] = $position;
    }

    /**
     * {@inheritdoc}
     */
    public function processMetadataLoad($om, $meta)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function processScheduledInsertion($em, $node)
    {
        // Don't calculate any paths until nodes are inserted. This way
        // pending inserts do not have to be stored because calculations will not include
        // pending nodes since they do not have a path variable. I.E the
        // countDescendants() method will not return nodes who have not been updated in the tree.
    }

    /**
     * {@inheritdoc}
     */
    public function processScheduledUpdate($dm, $node)
    {
        $meta = $dm->getClassMetadata(get_class($node));
        $config = $this->listener->getConfiguration($dm, $meta->name);
        $uow = $dm->getUnitOfWork();

        $changeSet = $uow->getDocumentChangeSet($node);

        if (isset($changeSet[$config['parent']])) {
            if ($changeSet[$config['parent']][1] == $changeSet[$config['parent']][0]) {
                // The parent node has gotten updated but we do not need to update in the database
                // this helps prevent multiple queries that are not needed if nodes are loaded
                // in the UOW
                $oid = spl_object_hash($node);
                $uow->clearDocumentChangeSet($oid);
                $meta->getReflectionProperty($config['parent'])->setValue($node, $changeSet[$config['parent']][1]);
                $dm->getUnitOfWork()->setOriginalDocumentProperty($oid, $config['parent'], $changeSet[$config['parent']][1]);
                $uow->recomputeSingleDocumentChangeSet($meta, $node);
            } else {
                // Update the parent
                $this->updateNode($dm, $node, $changeSet[$config['parent']][1]);
            }
        }

        if (isset($changeSet[$config['pathSource']])) {
            // Updating the path source
            $this->updateNodesPath($dm, $node);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function processPostPersist($em, $node)
    {
        $meta = $em->getClassMetadata(get_class($node));
        $config = $this->listener->getConfiguration($em, $meta->name);
        $parent = $meta->getReflectionProperty($config['parent'])->getValue($node);

        $this->updateNode($em, $node, $parent, self::LAST_CHILD);
    }

    /**
     * {@inheritdoc}
     * Deleting a parent will also delete all children
     */
    public function processScheduledDelete($dm, $node)
    {
        $meta = $dm->getClassMetadata(get_class($node));
        $config = $this->listener->getConfiguration($dm, $meta->name);
        $repo = $dm->getRepository($config['useObjectClass']);
        $path = $meta->getReflectionProperty($config['path'])->getValue($node);
        $nodeSort = $meta->getReflectionProperty($config['sort'])->getValue($node);
        $descendants = $repo->countDescendants($path);

        $repo->deleteDescendants($path);
        $repo->increaseSortBySortRange($nodeSort, true, -(1 + $descendants));

        $parent = $meta->getReflectionProperty($config['parent'])->getValue($node);

        if ($parent instanceof Proxy && !$parent->__isInitialized__) {
            $dm->refresh($parent);
        }

        $parentId = $meta->getIdentifierValue($parent);
        $repo->decreaseChildCount($parent, 1);

        // Update in memory nodes. Increases performance, saves some IO
        // @todo Try to combine this update logic
        foreach ($dm->getUnitOfWork()->getIdentityMap() as $className => $nodes) {
            // For inheritance mapped classes, only root is always in the identity map
            if ($className !== $meta->rootDocumentName) {
                continue;
            }

            foreach ($nodes as $row) {

                if ($row instanceof Proxy && !$row->__isInitialized__) {
                    continue;
                }

                if ($this->isNodeChildOf($row, $node)) {
                    $dm->detach($row);
                }

                $oldSortOrder = $meta->getReflectionProperty($config['sort'])->getValue($row);

                if (!$oldSortOrder) {
                    continue;
                }

                // Don't update the node
                if (($row == $node)) {
                    continue;
                }

                $oid = spl_object_hash($row);
                $nodePath = $meta->getReflectionProperty($config['path'])->getValue($row);
                $oldChildCount = $meta->getReflectionProperty($config['childCount'])->getValue($row);
                if ($oldSortOrder > $nodeSort) {
                    $meta->getReflectionProperty($config['sort'])->setValue($row, $oldSortOrder - (1 + $descendants));
                    $dm->getUnitOfWork()->setOriginalDocumentProperty($oid, $config['sort'], $oldSortOrder - (1 + $descendants));
                }

                // Decrease child count
                if ($meta->getIdentifierValue($row) == $parentId) {
                    --$oldChildCount;
                    $meta->getReflectionProperty($config['childCount'])->setValue($row, $oldChildCount);
                    $dm->getUnitOfWork()->setOriginalDocumentProperty($oid, $config['childCount'], $oldChildCount);
                }
            }
        }
    }

    /**
     * Checks if the given $node is a child of the $parent node
     *
     * @param Node $node
     * @param Node $parent
     */
    public function isNodeChildOf(Node $node, Node $parent)
    {
        $parentPath = explode(',', $parent->getPath());
        $childPath = explode(',', $node->getPath());

        // Remove the last element which is a empty string
        array_pop($parentPath);
        array_pop($childPath);

        // Remove the childs slug from the path
        array_pop($childPath);

        return $parentPath === $childPath;
    }

    /**
     * {@inheritdoc}
     */
    public function onFlushEnd($em)
    {}

    /**
     * {@inheritdoc}
     */
    public function processPreRemove($em, $node)
    {}

    /**
     * {@inheritdoc}
     */
    public function processPrePersist($em, $node)
    {}

    /**
     * Moves a node to the position specified relating to $referenceNode. If
     * no $referenceNode is passed in, $node will be inserted as the first
     * node in the tree and everything else will be moved down.
     *
     * Note: The current exspense of this method is 3 + n where
     * n = numbder of descendants a node has.
     *
     * @param $dm
     * @param $node
     * @param $referenceNode
     * @param $position
     */
    public function moveNode(DocumentManager $dm, $node, $referenceNode, $position = self::FIRST_CHILD)
    {
        $meta = $dm->getClassMetadata(get_class($node));
        $config = $this->listener->getConfiguration($dm, $meta->name);
        $repo = $dm->getRepository($config['useObjectClass']);
        $oid = spl_object_hash($node);
        $nodeId = $meta->getIdentifierValue($node);
        $newParent = null;

        $nodeParent = $meta->getReflectionProperty($config['parent'])->getValue($node);
        $nodeSort = $meta->getReflectionProperty($config['sort'])->getValue($node);
        $nodePath = $meta->getReflectionProperty($config['path'])->getValue($node);
        $nodeDescendants = $repo->fetchDescendants($nodePath, $config['sort'], 'asc');
        $descendantCount = $nodeDescendants->count();

        if ($referenceNode) {
            $referenceNodeParent = $meta->getReflectionProperty($config['parent'])->getValue($referenceNode);
            $referenceNodeSort = $meta->getReflectionProperty($config['sort'])->getValue($referenceNode);
            $referenceNodePath = $meta->getReflectionProperty($config['path'])->getValue($referenceNode);
            $newParent = $referenceNodeParent;

            switch ($position) {
                case self::PREV_SIBLING:
                    $newSort = $referenceNodeSort;
                    $decreaseBy = 1 + $descendantCount + ($nodeSort - $newSort);
                    $endingSort = $nodeSort + ($descendantCount * 2) + 2;
                    $repo->increaseSortBySortRange($newSort, true, 1 + $descendantCount);
                    $repo->increaseSortBySortRange($newSort + $descendantCount + 1, false, -1 * $decreaseBy, $endingSort);
                break;

                case self::NEXT_SIBLING:
                    if ($nodeSort > $referenceNodeSort) {
                        // We are moving the node up in the tree

                        $refMaxSort = $repo->findMaxSort(false, $referenceNodePath);

                        // First, increase all nodes greater than reference sort by #
                        // of node descendants + 1 to make room for nodes being moved up. Ending
                        // sort is $nodeSort + 1 cause we are not using lte, just lt.
                        $repo->increaseSortBySortRange($refMaxSort, false, $descendantCount + 1, $nodeSort);

                        // Second, our tree is now invalid because the duplicate sort orders exists
                        // we now need to update the nodes we are moving, i.e the $node itself and
                        // all sub children by the difference of the reference node sort - 1 and the node sort
                        $repo->increaseSortByPath($nodePath, -1 * ($nodeSort - ($refMaxSort + 1)));
                    } else {
                        $refMaxSort = $repo->findMaxSort(false, $referenceNodePath);

                        // First, decreasing all nodes greater than reference sort by #
                        // of node descendants + 1 to make room for nodes being moved down. Ending
                        // sort is the reference node max sort.
                        $repo->increaseSortBySortRange($referenceNodeSort, true, -1 * ($descendantCount + 1), $refMaxSort);

                        // Second, our tree is now invalid because the duplicate sort orders exists
                        // we now need to update the nodes we are moving, i.e the $node itself and
                        // all sub children by the difference of the reference node sort - 1 and the node sort
                        $repo->increaseSortByPath($nodePath, $referenceNodeSort - $nodeSort);
                    }
                break;

                case self::FIRST_CHILD:
                    if ($referenceNodeSort > $nodeSort) {
                        // First we need to increase the sort order for all nodes
                        // greater than the reference node, this will make room for
                        // the new node
                        $newParent = $referenceNode;
                        $refMaxSort = $repo->findMaxSort(false, $referenceNodePath);
                        $nodeMaxSort = $repo->findMaxSort(false, $nodePath);
                        $repo->increaseSortBySortRange($nodeMaxSort, false, -1 * ($descendantCount + 1), $referenceNodeSort + 1);

                        // Second, our tree is now invalid because the duplicate sort orders exists
                        // we now need to update the nodes we are moving, i.e the $node itself and
                        // all sub children by the difference of the reference node sort - 1 and the node sort
                        $repo->increaseSortByPath($nodePath, $referenceNodeSort - $nodeMaxSort);
                    } else {
                        // First we need to increase the sort order for all nodes
                        // greater than the reference node, this will make room for
                        // the new node
                        $newParent = $referenceNode;
                        $refMaxSort = $repo->findMaxSort(false, $referenceNodePath);
                        $repo->increaseSortBySortRange($referenceNodeSort, false, $descendantCount + 1, $nodeSort);

                        // Second, our tree is now invalid because the duplicate sort orders exists
                        // we now need to update the nodes we are moving, i.e the $node itself and
                        // all sub children by the difference of the reference node sort and the node sort
                        $repo->increaseSortByPath($nodePath, -1 * ($nodeSort - $referenceNodeSort) + 1);
                    }
                break;

                case self::LAST_CHILD:
                    $newParent = $referenceNode;

                    if ($referenceNodeSort > $nodeSort) {
                        // Moving the node down in the tree
                        $refMaxSort = $repo->findMaxSort(false, $referenceNodePath);
                        $repo->increaseSortBySortRange($nodeSort + $descendantCount, false, -1 * ($descendantCount + 1), $refMaxSort + 1);
                        $repo->increaseSortByPath($nodePath, $refMaxSort - $nodeSort - $descendantCount);
                    } else {
                        // @todo Look for special case of the node being the next node after reference
                        // where we would not have to update sort orders, just paths. This applies to all
                        // move methods I beleive
                        // @todo Check if the node has any children, if no children then we don't need to find
                        // max sort, the max sort would simply be the node sort
                        // Moving node up in the tree
                        $refMaxSort = $repo->findMaxSort(false, $referenceNodePath);
                        $repo->increaseSortBySortRange($refMaxSort, false, $descendantCount + 1, $nodeSort);
                        $repo->increaseSortByPath($nodePath, -1 * ($nodeSort - $refMaxSort) + 1);
                    }
                break;
            }
        } else {
            // We have no reference node, so this will now become the very
            // first node in the tree
            $newSort = 1;
            $decreaseBy = 1 + $descendantCount + ($nodeSort - $newSort);
            $endingSort = $nodeSort + ($descendantCount * 2) + 2;
            $repo->increaseSortBySortRange($newSort, true, 1 + $descendantCount);
            $repo->increaseSortBySortRange($newSort + $descendantCount + 1, false, -1 * $decreaseBy, $endingSort);
        }

        if ($newParent != $nodeParent) {

            // Decrease child count in DB and in memory
            $repo->decreaseChildCount($nodeParent);
            $childCountProp = $meta->getReflectionProperty($config['childCount']);
            $newChildCount = $childCountProp->getValue($nodeParent) - 1;
            $dm->getUnitOfWork()->setOriginalDocumentProperty(spl_object_hash($nodeParent), $config['path'], $newChildCount - 1);

            $qb = $dm->createQueryBuilder($config['useObjectClass']);
            $qb->field('id')->equals(new \MongoId($nodeId))
                ->update()
            ;

            $ref = null;
            if ($newParent) {
                $ref = $dm->createDBRef($newParent, null);
                $repo->increaseChildCount($newParent);
            }

            $qb->field($config['parent'])->set($ref);
            $meta->getReflectionProperty($config['parent'])->setValue($node, $newParent);
            $dm->getUnitOfWork()->setOriginalDocumentProperty($oid, $config['parent'], $newParent);

            // Update node path
            $nodePath = $this->updateNodesPath($dm, $node, $nodeDescendants, true);

            $qb->field($config['path'])->set($nodePath)
                ->getQuery()
                ->execute()
            ;

            $meta->getReflectionProperty($config['path'])->setValue($node, $nodePath);
            $dm->getUnitOfWork()->setOriginalDocumentProperty($oid, $config['path'], $nodePath);
        }
    }

    /**
     * Update the $node with a diferent $parent
     * destination
     *
     * Reference node is the node that we are referencing when we determine how to
     * insert the $node. The reference node will be stored as the nodes parent at first, but then
     * it will be replaced with its true parent when updated in this method.
     *
     * @param EntityManager $dm
     * @param object $node - target node
     * @param object $referenceNode - destination node
     * @param string $position
     * @throws Gedmo\Exception\UnexpectedValueException
     * @return void
     */
    public function updateNode(DocumentManager $dm, $node, $referenceNode, $position = self::FIRST_CHILD)
    {
        $meta = $dm->getClassMetadata(get_class($node));
        $config = $this->listener->getConfiguration($dm, $meta->name);

        $nodeId = $meta->getIdentifierValue($node);
        $repo = $dm->getRepository($config['useObjectClass']);
        $nodeParent = $meta->getReflectionProperty($config['parent'])->getValue($node);

        $oid = spl_object_hash($node);
        if (isset($this->nodePositions[$oid])) {
            $position = $this->nodePositions[$oid];
        }

        $referenceNodeSort = 0;
        if ($referenceNode) {
            $referenceNodeSort = $meta->getReflectionProperty($config['sort'])->getValue($referenceNode);
        }
        $currentNodePath = $meta->getReflectionProperty($config['path'])->getValue($node);
        $nodeCurrentSort = $meta->getReflectionProperty($config['sort'])->getValue($node);
        $parent = null;
        $sortOrder = 1;
        $equal = false;

        if ($referenceNode) {
            if ($referenceNode instanceof Proxy && !$parent->__isInitialized__) {
                $dm->refresh($referenceNode);
            }

            switch ($position) {

                case self::PREV_SIBLING:

                    $refParent = $meta->getReflectionProperty($config['parent'])->getValue($referenceNode);

                    // If refrence node has a parent, set this nodes parent to be the same, else nullify it
                    if ($refParent) {
                        $parent = $refParent;
                        $parentPath = $meta->getReflectionProperty($config['path'])->getValue($parent);
                        $parentDescendants = $dm->getRepository($config['useObjectClass'])->countDescendants($parentPath);
                    }

                    // Set the new nodes sort to be the reference node sort and then update all nodes equal
                    // to the reference node sort
                    $sortOrder = $referenceNodeSort;
                    $equal = true;
                break;

                case self::NEXT_SIBLING:

                    // Get reference nodes parent
                    $parent = $meta->getReflectionProperty($config['parent'])->getValue($referenceNode);
                    $referencePath = $meta->getReflectionProperty($config['path'])->getValue($referenceNode);
                    $referenceDescendants = $dm->getRepository($config['useObjectClass'])->countDescendants($referencePath);

                    // Determine new nodes sort based on the sort for the reference
                    // node + how many descendants it has + 1 so it is the last node
                    $sortOrder = $referenceNodeSort + $referenceDescendants + 1;
                    $equal = true;

                break;

                case self::LAST_CHILD:
                    $parent = $referenceNode;
                    $parentPath = $meta->getReflectionProperty($config['path'])->getValue($parent);
                    $parentDescendants = $dm->getRepository($config['useObjectClass'])->countDescendants($parentPath);
                    $parentSort = $meta->getReflectionProperty($config['sort'])->getValue($referenceNode);
                    $sortOrder = $parentSort + $parentDescendants + 1;
                break;

                case self::FIRST_CHILD:
                default:
                    $parent = $referenceNode;
                    $parentPath = $meta->getReflectionProperty($config['path'])->getValue($parent);
                    $parentDescendants = $dm->getRepository($config['useObjectClass'])->countDescendants($parentPath);
                    $parentSort = $meta->getReflectionProperty($config['sort'])->getValue($parent);

                    // Determine new nodes sort based on the sort for the reference
                    // node + how many descendants it has + 1 so it is the last node
                    $sortOrder = $parentSort + 1;
                    $equal = true;
                break;
            }

        } else {
            // Okay, parent is null, what about sort order?
            $parent = null;
            switch ($position) {

                // Next sibliing and last child of no parent will create this
                // node as a new "root" at the very end of the tree
                case self::NEXT_SIBLING:
                case self::LAST_CHILD:
                    $sortOrder = $repo->findMaxSort() + 1;
                break;

                // Prev sibling and first child nodes with no parent will
                // create a new "root" node at beginning of the tree
                case self::PREV_SIBLING:
                case self::FIRST_CHILD:
                default:
                    $sortOrder = 1;
                    $equal = true;
                break;
            }
        }

        // @todo Support for multiple roots
        // Increase the sort for every node that has a greater sort than
        // what we are working with
        $repo->increaseSortBySortRange($sortOrder, $equal);

        // Make sure we don't have to refresh document in order to get
        // correct values by setting in the UOW
        $meta->getReflectionProperty($config['sort'])->setValue($node, $sortOrder);
        $dm->getUnitOfWork()->setOriginalDocumentProperty($oid, $config['sort'], $sortOrder);

        // Update parent in memory
        if ($parent != $nodeParent) {
            $meta->getReflectionProperty($config['parent'])->setValue($node, $parent);
            $dm->getUnitOfWork()->setOriginalDocumentProperty($oid, $config['parent'], $parent);
        }

        // Check if we have a parent, if so increase child count and update in memory
        if ($parent) {
            // Increase the parents child count
            $repo->increaseChildCount($parent);

            // Save new value in object and update orignal value in UOW
            $childProp = $meta->getReflectionProperty($config['childCount']);
            $newChildren = ($childProp->getValue($parent) + 1);
            $childProp->setValue($parent, $newChildren);
            $dm->getUnitOfWork()->setOriginalDocumentProperty(spl_object_hash($parent), $config['childCount'], $newChildren);
        }

        $nodePath = $this->setNodePath($dm, $node);

        // Set the nodes new sort and path in db
        $qb = $dm->createQueryBuilder($config['useObjectClass']);
        $qb->field('id')->equals(new \MongoId($nodeId))
            ->update()
            ->field($config['path'])->set($nodePath)
            ->field($config['sort'])->set($sortOrder)
        ;

        $meta->getReflectionProperty($config['path'])->setValue($node, $nodePath);
        $dm->getUnitOfWork()->setOriginalDocumentProperty($oid, $config['path'], $node);

        // Update parent in query builder if we have one
        if ($parent) {
            $ref = $dm->createDBRef($parent, null);
            $qb->field($config['parent'])->set($ref);
            $meta->getReflectionProperty($config['parent'])->setValue($node, $parent);
            $dm->getUnitOfWork()->setOriginalDocumentProperty($oid, $config['parent'], $parent);
        }

        // If we have a parent but it is not equal to the nodes current parent, update in db
        // and in memory
        if ($parent != $nodeParent) {

            $ref = null;
            if ($parent) {
                $ref = $dm->createDBRef($parent, null);
            }

            $qb->field($config['parent'])->set($ref);
            $meta->getReflectionProperty($config['parent'])->setValue($node, $parent);
            $dm->getUnitOfWork()->setOriginalDocumentProperty($oid, $config['parent'], $parent);
        }

        // Execute the query
        $query = $qb->getQuery(array('safe' => true))
            ->execute()
        ;

        // Update in memory nodes. Increases performance, saves some IO
        foreach ($dm->getUnitOfWork()->getIdentityMap() as $className => $nodes) {
            // For inheritance mapped classes, only root is always in the identity map
            if ($className !== $meta->rootDocumentName) {
                continue;
            }

            foreach ($nodes as $row) {

                if ($row instanceof Proxy && !$row->__isInitialized__) {
                    continue;
                }

                if (!$meta->getReflectionProperty($config['sort'])->getValue($row)) {
                    continue;
                }

                // Don't update the node or the parent since these have already been updated
                if (($row == $node) || ($row == $parent)) {
                    continue;
                }

                $oid = spl_object_hash($row);
                $oldSortOrder = $meta->getReflectionProperty($config['sort'])->getValue($row);

                if (($equal && ($oldSortOrder >= $sortOrder)) || (!$equal && ($oldSortOrder > $sortOrder))) {
                    $meta->getReflectionProperty($config['sort'])->setValue($row, $oldSortOrder + 1);
                    $dm->getUnitOfWork()->setOriginalDocumentProperty($oid, $config['sort'], $oldSortOrder + 1);
                }
            }
        }

        $dm->getUnitOfWork()->recomputeSingleDocumentChangeSet($meta, $node);
    }

    /**
     * Updates the nodes path as well as the nodes descendants
     * paths
     *
     * @param $node
     */
    public function updateNodesPath(DocumentManager $dm, $parentNode, $descendants = null, $updateDb = false)
    {
        $meta = $dm->getClassMetadata(get_class($parentNode));
        $config = $this->listener->getConfiguration($dm, $meta->name);
        $repo = $dm->getRepository($config['useObjectClass']);
        $pathProp = $meta->getReflectionProperty($config['path']);
        $oldParentPath = $pathProp->getValue($parentNode);

        // Set the new parent path
        $this->setNodePath($dm, $parentNode);
        $dm->getUnitOfWork()->recomputeSingleDocumentChangeSet($meta, $parentNode);

        // @todo Working method but this can use a lot of resources to load all
        // descendants into memory
        if (!$descendants) {
            $descendants = $repo->fetchDescendants($oldParentPath, $config['sort'], 'asc');
        }

        foreach ($descendants as $descendant)
        {
            // @todo Require a single sluggable field so we do not have
            // to recompute the slug for descendants. This will remove
            // the extra method call
            // @todo Use the Sluggable extension to do this work
            $this->setNodePath($dm, $descendant, $updateDb);
            $dm->getUnitOfWork()->computeChangeSet($meta, $descendant);
        }

        return $pathProp->getValue($parentNode);
    }

    /**
     * Returns the path that all ancestors of $node will contain
     *
     * @param Node $node
     */
    public function getAncestorsPath(Node $node)
    {
        $nodePath = $node->getPath();
        $fullPath = explode(',', $nodePath);
        array_pop($fullPath); // Remove extra element
        array_pop($fullPath); // Remove the $nodes path

        $ancestorsPaths = array();
        $previous = null;
        foreach ($fullPath as $path)
        {
            $ancestorsPaths[] = $previous . $path . ',';
            $previous = $previous . $path . ',';
        }

        return $ancestorsPaths;
    }

    /**
     * Sets the nodes path
     * Does not flush changes in the DB. Used mostly internally
     *
     * @param DocumentManager $dm
     * @param unknown_type $node
     */
    protected function setNodePath(DocumentManager $dm, $node, $setNodePath = false)
    {
        $meta = $dm->getClassMetadata(get_class($node));
        $config = $this->listener->getConfiguration($dm, $meta->name);
        $pathProperty = $meta->getReflectionProperty($config['path']);
        $treePathSource = $meta->getReflectionProperty($config['pathSource'])->getValue($node);

        // We don't have a path for this node yet, means it is new
        $nodePath = call_user_func_array(
            array('Gedmo\Tree\Util\Urlizer', 'transliterate'),
            array($treePathSource, '-')
        );

        $parentNode = $meta->getReflectionProperty($config['parent'])->getValue($node);

        // If we have a parent, add the parents path then the nodes path
        if ($parentNode) {
            $nodePath = $parentNode->getPath() . $nodePath;
        }

        $nodePath = $nodePath . ',';
        $pathProperty->setValue($node, $nodePath);

        if ($setNodePath) {
            $dm->getRepository($config['useObjectClass'])->setNodePath($node, $nodePath);
            $dm->getUnitOfWork()->setOriginalDocumentProperty(spl_object_hash($node), $config['path'], $node);
        }

        return $nodePath;
    }
}