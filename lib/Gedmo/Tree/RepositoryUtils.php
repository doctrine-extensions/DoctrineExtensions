<?php

namespace Gedmo\Tree;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Gedmo\Exception\InvalidArgumentException;

class RepositoryUtils implements RepositoryUtilsInterface
{
    /** @var \Doctrine\Common\Persistence\Mapping\ClassMetadata */
    protected $meta;

    /** @var \Gedmo\Tree\TreeListener */
    protected $listener;

    /** @var \Doctrine\Common\Persistence\ObjectManager */
    protected $om;

    /** @var \Gedmo\Tree\RepositoryInterface */
    protected $repo;

    /**
     * This index is used to hold the children of a node
     * when using any of the buildTree related methods.
     *
     * @var string
     */
    protected $childrenIndex = '__children';


    public function __construct(ObjectManager $om, ClassMetadata $meta, $listener, $repo)
    {
        $this->om = $om;
        $this->meta = $meta;
        $this->listener = $listener;
        $this->repo = $repo;
    }

    public function getClassMetadata()
    {
        return $this->meta;
    }

    /**
     * {@inheritDoc}
     */
    public function childrenHierarchy($node = null, $direct = false, array $options = array(), $includeNode = false)
    {
        $meta = $this->getClassMetadata();

        if ($node !== null) {
            if ($node instanceof $meta->name) {
                if (!$this->om->getUnitOfWork()->isInIdentityMap($node)) {
                    throw new InvalidArgumentException("Node is not managed by UnitOfWork");
                }
            } else {
                throw new InvalidArgumentException("Node is not related to this repository");
            }
        } else {
            $includeNode = true;
        }

        // Gets the array of $node results. It must be ordered by depth
        $nodes = $this->repo->getNodesHierarchy($node, $direct, $options, $includeNode);
        return $this->repo->buildTree($nodes, $options);
    }

    /**
     * {@inheritDoc}
     */
    public function buildTree(array $nodes, array $options = array())
    {
        $meta = $this->getClassMetadata();
        $nestedTree = $this->repo->buildTreeArray($nodes);

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
                } elseif ($meta->hasField('name')) {
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
        }

        if (!count($nestedTree)) {
            return '';
        }

        $childrenIndex = $this->childrenIndex;

        $build = function($tree) use (&$build, &$options, $childrenIndex) {
            $output = is_string($options['rootOpen']) ? $options['rootOpen'] : $options['rootOpen']($tree);
            foreach ($tree as $node) {
                $output .= is_string($options['childOpen']) ? $options['childOpen'] : $options['childOpen']($node);
                $output .= $options['nodeDecorator']($node);
                if (count($node[$childrenIndex]) > 0) {
                    $output .= $build($node[$childrenIndex]);
                }
                $output .= is_string($options['childClose']) ? $options['childClose'] : $options['childClose']($node);
            }
            return $output . (is_string($options['rootClose']) ? $options['rootClose'] : $options['rootClose']($tree));
        };

        return $build($nestedTree);
    }

    /**
     * {@inheritDoc}
     */
    public function buildTreeArray(array $nodes)
    {
        $meta = $this->getClassMetadata();
        $config = $this->listener->getConfiguration($this->om, $meta->name);
        $nestedTree = array();
        $l = 0;

        if (count($nodes) > 0) {
            // Node Stack. Used to help building the hierarchy
            $stack = array();
            foreach ($nodes as $child) {
                $item = $child;
                $item[$this->childrenIndex] = array();
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
                    $i = count($stack[$l - 1][$this->childrenIndex]);
                    $stack[$l - 1][$this->childrenIndex][$i] = $item;
                    $stack[] = &$stack[$l - 1][$this->childrenIndex][$i];
                }
            }
        }

        return $nestedTree;
    }

    /**
     * {@inheritDoc}
     */
    public function setChildrenIndex($childrenIndex)
    {
        $this->childrenIndex = $childrenIndex;
    }

    /**
     * {@inheritDoc}
     */
    public function getChildrenIndex()
    {
        return $this->childrenIndex;
    }
}
