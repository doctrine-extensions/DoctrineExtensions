<?php

namespace Gedmo\Tree\Entity\Repository;

use Doctrine\ORM\EntityRepository,
    Doctrine\ORM\EntityManager,
    Doctrine\ORM\Mapping\ClassMetadata,
    Gedmo\Tool\Wrapper\EntityWrapper;

abstract class AbstractTreeRepository extends EntityRepository
{
    /**
     * Tree listener on event manager
     *
     * @var AbstractTreeListener
     */
    protected $listener = null;

    /**
     * {@inheritdoc}
     */
    public function __construct(EntityManager $em, ClassMetadata $class)
    {
        parent::__construct($em, $class);
        $treeListener = null;
        foreach ($em->getEventManager()->getListeners() as $listeners) {
            foreach ($listeners as $listener) {
                if ($listener instanceof \Gedmo\Tree\TreeListener) {
                    $treeListener = $listener;
                    break;
                }
            }
            if ($treeListener) {
                break;
            }
        }

        if (is_null($treeListener)) {
            throw new \Gedmo\Exception\InvalidMappingException('Tree listener was not found on your entity manager, it must be hooked into the event manager');
        }

        $this->listener = $treeListener;
        if (!$this->validate()) {
            throw new \Gedmo\Exception\InvalidMappingException('This repository cannot be used for tree type: ' . $treeListener->getStrategy($em, $class->name)->getName());
        }
    }

    /**
     * Retrieves the nested array or the decorated output.
     * Uses @options to handle decorations
     *
     * @throws \Gedmo\Exception\InvalidArgumentException
     * @param object $node - from which node to start reordering the tree
     * @param boolean $direct - true to take only direct children
     * @param array $options :
     *     decorate: boolean (false) - retrieves tree as UL->LI tree
     *     nodeDecorator: Closure (null) - uses $node as argument and returns decorated item as string
     *     rootOpen: string || Closure ('<ul>') - branch start, closure will be given $children as a parameter
     *     rootClose: string ('</ul>') - branch close
     *     childStart: string || Closure ('<li>') - start of node, closure will be given $node as a parameter
     *     childClose: string ('</li>') - close of node
     *     childSort: array || keys allowed: field: field to sort on, dir: direction. 'asc' or 'desc'
     *
     * @return array|string
     */
    public function childrenHierarchy($node = null, $direct = false, array $options = array())
    {
        $meta = $this->getClassMetadata();
        $config = $this->listener->getConfiguration($this->_em, $meta->name);

        if ($node !== null) {
            if ($node instanceof $meta->name) {
                $wrapped = new EntityWrapper($node, $this->_em);
                if (!$wrapped->hasValidIdentifier()) {
                    throw new InvalidArgumentException("Node is not managed by UnitOfWork");
                }
            }
        }

        // Gets the array of $node results. It must be ordered by depth
        $nodes = $this->getNodesHierarchy($node, $direct, $config, $options);

        return $this->buildTree($nodes, $options);
    }

    /**
     * Retrieves the nested array or the decorated output.
     * Uses @options to handle decorations
     * NOTE: @nodes should be fetched and hydrated as array
     *
     * @throws \Gedmo\Exception\InvalidArgumentException
     * @param array $nodes - list o nodes to build tree
     * @param array $options :
     *     decorate: boolean (false) - retrieves tree as UL->LI tree
     *     nodeDecorator: Closure (null) - uses $node as argument and returns decorated item as string
     *     rootOpen: string || Closure ('<ul>') - branch start, closure will be given $children as a parameter
     *     rootClose: string ('</ul>') - branch close
     *     childStart: string || Closure ('<li>') - start of node, closure will be given $node as a parameter
     *     childClose: string ('</li>') - close of node
     *
     * @return array|string
     */
    public function buildTree(array $nodes, array $options = array())
    {
        $meta = $this->getClassMetadata();
        $nestedTree = $this->buildTreeArray($nodes);

        $default = array(
            'decorate' => false,
            'rootOpen' => '<ul>',
            'rootClose' => '</ul>',
            'childOpen' => '<li>',
            'childClose' => '</li>',
            'nodeDecorator' => function ($node) use ($meta) {
                // override and change it, guessing which field to use
                if ($meta->hasField('title')) {
                    $field = 'title';
                } else if ($meta->hasField('name')) {
                    $field = 'name';
                } else {
                    throw new InvalidArgumentException("Cannot find any representation field");
                }
                return $node[$field];
            }
        );
        $options = array_merge($default, $options);
        // If you don't want any html output it will return the nested array
        if (!$options['decorate']) {
            return $nestedTree;
        } elseif (!count($nestedTree)) {
            return '';
        }

        $build = function($tree) use (&$build, &$options) {
            $output = is_string($options['rootOpen']) ? $options['rootOpen'] : $options['rootOpen']($tree);
            foreach ($tree as $node) {
                $output .= is_string($options['childOpen']) ? $options['childOpen'] : $options['childOpen']($node);
                $output .= $options['nodeDecorator']($node);
                if (count($node['__children']) > 0) {
                    $output .= $build($node['__children']);
                }
                $output .= is_string($options['childClose']) ? $options['childClose'] : $options['childClose']($node);
            }
            return $output . (is_string($options['rootClose']) ? $options['rootClose'] : $options['rootClose']($tree));
        };

        return $build($nestedTree);
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
        $config = $this->listener->getConfiguration($this->_em, $meta->name);
        $nestedTree = array();
        $l = 0;

        if (count($nodes) > 0) {
            // Node Stack. Used to help building the hierarchy
            $stack = array();
            foreach ($nodes as $child) {
                $item = $child;
                $item['__children'] = array();
                // Number of stack items
                $l = count($stack);
                // Check if we're dealing with different levels
                while($l > 0 && $stack[$l - 1][$config['level']] >= $item[$config['level']]) {
                    array_pop($stack);
                    $l--;
                }
                // Stack is empty (we are inspecting the root)
                if ($l == 0) {
                    // Assigning the root child
                    $i = count($nestedTree);
                    $nestedTree[$i] = $item;
                    $stack[] = &$nestedTree[$i];
                } else {
                    // Add child to parent
                    $i = count($stack[$l - 1]['__children']);
                    $stack[$l - 1]['__children'][$i] = $item;
                    $stack[] = &$stack[$l - 1]['__children'][$i];
                }
            }
        }

        return $nestedTree;
    }

    /**
     * Checks if current repository is right
     * for currently used tree strategy
     *
     * @return bool
     */
    abstract protected function validate();

    /**
     * Returns an array of nodes suitable for method buildTree
     *
     * @param object - Root node
     * @param bool - Obtain direct children?
     * @param array - Metadata configuration
     * @param array - Options
     *
     * @return array - Array of nodes
     */
    abstract public function getNodesHierarchy($node, $direct, array $config, array $options = array());
}
