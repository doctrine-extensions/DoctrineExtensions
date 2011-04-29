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
use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * Main class for implementing the materialized path structure
 * with MongoDB and Doctrine
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
    public function processScheduledInsertion($em, $node)
    {
        $meta = $em->getClassMetadata(get_class($node));
        $config = $this->listener->getConfiguration($em, $meta->name);
        $pathProperty = $meta->getReflectionProperty($config['path']);
        $treePathSource = $meta->getReflectionProperty($config['pathSource'])->getValue($node);
        $parentNode = $meta->getReflectionProperty($config['parent'])->getValue($node);

        // We have a new node so generate the path
        $nodePath = call_user_func_array(
            array('Gedmo\Tree\Util\Urlizer', 'transliterate'),
            array($treePathSource, '-')
        );

        // If we have a parent, keep track that we added a child to this parent
        if ($parentNode) {
            $nodePath = $parentNode->getPath() . $nodePath;
            $oid = spl_object_hash($parentNode);
            $nodeHash = spl_object_hash($node);
            $this->pendingChildrenNodes[$oid][$nodeHash] = true;
        }

        $pathProperty->setValue($node, $nodePath . ',');
    }

    /**
     * {@inheritdoc}
     */
    public function processScheduledUpdate($em, $node)
    {
        // @todo
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
     */
    public function processScheduledDelete($em, $node)
    {
        // @todo
    }

    /**
     * {@inheritdoc}
     */
    public function onFlushEnd($em)
    {
        // Reset values
        $this->nodePositions = array();
    }

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
    public function updateNode(DocumentManager $dm, $node, $referenceNode, $position = 'FirstChild')
    {
        $meta = $dm->getClassMetadata(get_class($node));
        $config = $this->listener->getConfiguration($dm, $meta->name);

        $nodeId = $meta->getIdentifierValue($node);
        $repo = $dm->getRepository($config['useObjectClass']);

        $oid = spl_object_hash($node);
        if (isset($this->nodePositions[$oid])) {
            $position = $this->nodePositions[$oid];
        }

        $level = 0;
        $newRootId = $parent = null;
        $sortOrder = 1;
        $equal = false;
        if ($referenceNode) {

            if ($referenceNode instanceof Proxy && !$parent->__isInitialized__) {
                $dm->refresh($referenceNode);
            }

            switch ($position) {

                case self::PREV_SIBLING:

                	$parent = $meta->getReflectionProperty($config['parent'])->getValue($referenceNode);
                    $parentPath = $meta->getReflectionProperty($config['path'])->getValue($parent);
                    $parentDescendants = $dm->getRepository($config['useObjectClass'])->countDescendants($parentPath);
                    $referenceNodeSort = $meta->getReflectionProperty($config['sort'])->getValue($referenceNode);
                    $meta->getReflectionProperty($config['sort'])->setValue($referenceNode, $referenceNodeSort + 1);

                    // Determine new nodes sort based on the sort for the reference
                    // node + how many descendants it has + 1 so it is the last node
                    $sortOrder = $referenceNodeSort;
                    $equal = true;

                break;

                case self::NEXT_SIBLING:

                    $parent = $meta->getReflectionProperty($config['parent'])->getValue($referenceNode);
                    $parentPath = $meta->getReflectionProperty($config['path'])->getValue($referenceNode);
                    $referenceDescendants = $dm->getRepository($config['useObjectClass'])->countDescendants($parentPath);
                    $referenceNodeSort = $meta->getReflectionProperty($config['sort'])->getValue($referenceNode);

                    // Determine new nodes sort based on the sort for the reference
                    // node + how many descendants it has + 1 so it is the last node
                    $sortOrder = $referenceNodeSort + 1;
                    $equal = true;

                break;

                case self::LAST_CHILD:
                    $parent = $referenceNode;
                    $parentPath = $meta->getReflectionProperty($config['path'])->getValue($parent);
                    $parentDescendants = $dm->getRepository($config['useObjectClass'])->countDescendants($parentPath);
                    $parentSort = $meta->getReflectionProperty($config['sort'])->getValue($referenceNode);

                    // Check if we have this object as a pending child of the parent, if we do
                    // we will want to make note of it so we can subtract the pending ones
                    // from the parent descendats count
                    $pendingParentChildNodes = (isset($this->pendingChildrenNodes[spl_object_hash($parent)]))
                        ? count($this->pendingChildrenNodes[spl_object_hash($parent)]) : 0;

                    // Determine new nodes sort based on the sort for the reference
                    // node + how many descendants it has + 1 so it is the last node
                    $sortOrder = $parentSort + ($parentDescendants - $pendingParentChildNodes) + 1;

                    // Unset the node from the parents list of pending children
                    unset($this->pendingChildrenNodes[spl_object_hash($parent)][$oid]);
                break;

                case self::FIRST_CHILD:
                default:
                    $parent = $referenceNode;
                    $parentPath = $meta->getReflectionProperty($config['path'])->getValue($parent);
                    $parentDescendants = $dm->getRepository($config['useObjectClass'])->countDescendants($parentPath);
                    $parentSort = $meta->getReflectionProperty($config['sort'])->getValue($referenceNode);

                    // Determine new nodes sort based on the sort for the reference
                    // node + how many descendants it has + 1 so it is the last node
                    $sortOrder = $parentSort + 1;
                    $equal = true;

                    // Unset the node from the parents list of pending children
                    unset($this->pendingChildrenNodes[spl_object_hash($parent)][$oid]);
                break;
            }

        } else {
            $parent = null;
            $sortOrder = $repo->findMaxSort() + 1;
        }

        // @todo Support for multiple roots
        // Increase the sort for every node that has a greater sort than
        // what we are working with
        $repo->increaseSort($sortOrder, $equal);

        // Make sure we don't have to refresh document in order to get
        // correct values by setting in the UOW
        $meta->getReflectionProperty($config['sort'])->setValue($node, $sortOrder);
        $dm->getUnitOfWork()->setOriginalDocumentProperty($oid, $config['sort'], $sortOrder);

        // If we have a parent, update the object reflection properties and
        // update the objects default values in the UOW
        if ($parent) {
            // Increase the parents child count
            $repo->increaseChildCount($parent);

            // Save new value in object and update orignal value in UOW
            $childProp = $meta->getReflectionProperty($config['childCount']);
            $newChildren = ($childProp->getValue($parent) + 1);
            $childProp->setValue($parent, $newChildren);
            $dm->getUnitOfWork()->setOriginalDocumentProperty(spl_object_hash($parent), $config['childCount'], $newChildren);

            $meta->getReflectionProperty($config['parent'])->setValue($node, $parent);
            $dm->getUnitOfWork()->setOriginalDocumentProperty($oid, $config['parent'], $parent);
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

        // Update parent if we have one
        if ($parent) {
            $ref = $dm->createDBRef($parent);
            $qb->field($config['parent'])->set($ref);
        }

        // Execute the query
        $query = $qb->getQuery()
            ->execute()
        ;

        // @todo This breaks more tests than it fixes. Will reconsider this in
        // refactoring/cleanup
         // Update in memory nodes increases performance, saves some IO
//        foreach ($dm->getUnitOfWork()->getIdentityMap() as $className => $nodes) {
//
//            // For inheritance mapped classes, only root is always in the identity map
//            if ($className !== $meta->rootDocumentName) {
//                continue;
//            }
//
//            foreach ($nodes as $row) {
//
//                if ($row instanceof Proxy && !$row->__isInitialized__) {
//                    continue;
//                }
//
//                if ($row == $node) {
//                	continue;
//                }
//
//                $oid = spl_object_hash($row);
//                $oldSortOrder = $meta->getReflectionProperty($config['sort'])->getValue($row);
//
//                if (($equal && ($sortOrder >= $oldSortOrder)) || (!$equal && ($sortOrder > $oldSortOrder))) {
//                	$meta->getReflectionProperty($config['sort'])->setValue($row, $oldSortOrder + 1);
//                    $dm->getUnitOfWork()->setOriginalDocumentProperty($oid, $config['sort'], $oldSortOrder + 1);
//                }
//            }
//        }
    }

    protected function setNodePath($em, $node)
    {
        $meta = $em->getClassMetadata(get_class($node));
        $config = $this->listener->getConfiguration($em, $meta->name);
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

        return $nodePath;
    }
}