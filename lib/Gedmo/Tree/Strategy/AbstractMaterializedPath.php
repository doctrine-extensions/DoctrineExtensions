<?php

namespace Gedmo\Tree\Strategy;

use Gedmo\Tree\Strategy;
use Doctrine\ORM\EntityManager;
use Gedmo\Tree\TreeListener;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Query;
use Doctrine\Common\Persistence\ObjectManager;
use Gedmo\Mapping\Event\AdapterInterface;
use Gedmo\Exception\RuntimeException;

/**
 * This strategy makes tree using materialized path strategy
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Tree.Strategy
 * @subpackage AbstractMaterializedPath
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

abstract class AbstractMaterializedPath implements Strategy
{
    /**
     * TreeListener
     *
     * @var AbstractTreeListener
     */
    protected $listener = null;

    /**
     * Array of objects which were scheduled for path processes
     *
     * @var array
     */
    protected $scheduledForPathProcess = array();

    /**
     * Array of objects which were scheduled for path process.
     * This time, this array contains the objects with their ID
     * already set
     *
     * @var array
     */
    protected $scheduledForPathProcessWithIdSet = array();

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
        return Strategy::MATERIALIZED_PATH;
    }

    /**
     * {@inheritdoc}
     */
    public function processScheduledInsertion($om, $node, AdapterInterface $ea)
    {
        $meta = $om->getClassMetadata(get_class($node));
        $config = $this->listener->getConfiguration($om, $meta->name);
        $fieldMapping = $meta->getFieldMapping($config['path_source']);

        if ($meta->isIdentifier($config['path_source']) || $fieldMapping['type'] === 'string') {
            $this->scheduledForPathProcess[spl_object_hash($node)] = $node;
        } else {
            $this->updateNode($om, $node, $ea);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function processScheduledUpdate($om, $node, AdapterInterface $ea)
    {
        $meta = $om->getClassMetadata(get_class($node));
        $config = $this->listener->getConfiguration($om, $meta->name);
        $uow = $om->getUnitOfWork();
        $changeSet = $ea->getObjectChangeSet($uow, $node);

        if (isset($changeSet[$config['parent']]) || isset($changeSet[$config['path_source']])) {
            if (isset($changeSet[$config['path']])) {
                $originalPath = $changeSet[$config['path']][0];
            } else {
                $pathProp = $meta->getReflectionProperty($config['path']);
                $pathProp->setAccessible(true);
                $originalPath = $pathProp->getValue($node);
            }

            $this->updateNode($om, $node, $ea);
            $this->updateChildren($om, $node, $ea, $originalPath);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function processPostPersist($om, $node, AdapterInterface $ea)
    {
        $oid = spl_object_hash($node);

        if ($this->scheduledForPathProcess && array_key_exists($oid, $this->scheduledForPathProcess)) {
            $this->scheduledForPathProcessWithIdSet[$oid] = $node;

            unset($this->scheduledForPathProcess[$oid]);

            if (empty($this->scheduledForPathProcess)) {
                foreach ($this->scheduledForPathProcessWithIdSet as $oid => $node) {
                    $this->updateNode($om, $node, $ea);

                    unset($this->scheduledForPathProcessWithIdSet[$oid]);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onFlushEnd($om)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function processPreRemove($om, $node)
    {}

    /**
     * {@inheritdoc}
     */
    public function processPrePersist($om, $node)
    {}

    /**
     * {@inheritdoc}
     */
    public function processMetadataLoad($om, $meta)
    {}

    /**
     * {@inheritdoc}
     */
    public function processScheduledDelete($om, $node)
    {
        $meta = $om->getClassMetadata(get_class($node));
        $config = $this->listener->getConfiguration($om, $meta->name);

        $this->removeNode($om, $meta, $config, $node);
    }

    /**
     * Update the $node
     *
     * @param ObjectManager $om
     * @param object $node - target node
     * @param object $ea - event adapter
     * @return void
     */
    public function updateNode(ObjectManager $om, $node, AdapterInterface $ea)
    {
        $oid = spl_object_hash($node);
        $meta = $om->getClassMetadata(get_class($node));
        $config = $this->listener->getConfiguration($om, $meta->name);
        $uow = $om->getUnitOfWork();
        $parentProp = $meta->getReflectionProperty($config['parent']);
        $parentProp->setAccessible(true);
        $parent = $parentProp->getValue($node);
        $pathProp = $meta->getReflectionProperty($config['path']);
        $pathProp->setAccessible(true);
        $pathSourceProp = $meta->getReflectionProperty($config['path_source']);
        $pathSourceProp->setAccessible(true);
        $path = $pathSourceProp->getValue($node);

        // We need to avoid the presence of the path separator in the path source
        if (strpos($path, $config['path_separator']) !== false) {
            $msg = 'You can\'t use the Path separator ("%s") as a character for your PathSource field value.';

            throw new RuntimeException(sprintf($msg, $config['path_separator']));
        }

        $fieldMapping = $meta->getFieldMapping($config['path_source']);

        // If PathSource field is a string, we append the ID to the path
        if ($fieldMapping['type'] === 'string') {
            $path .= '-'.$meta->getIdentifierValue($node);
        }

        $path .= $config['path_separator'];

        if ($parent) {
            $changeSet = $uow->isScheduledForUpdate($parent) ? $ea->getObjectChangeSet($uow, $parent) : false;
            $pathOrPathSourceHasChanged = $changeSet && (isset($changeSet[$config['path_source']]) || isset($changeSet[$config['path']]));

            if ($pathOrPathSourceHasChanged || !$pathProp->getValue($parent)) {
                $this->updateNode($om, $parent, $ea);
            }

            $path = $pathProp->getValue($parent).$path;
        }
        
        $pathProp->setValue($node, $path);
        $changes = array(
            $config['path'] => array(null, $path)
        );

        if (isset($config['level'])) {
            $level = substr_count($path, $config['path_separator']);
            $levelProp = $meta->getReflectionProperty($config['level']);
            $levelProp->setAccessible(true);
            $levelProp->setValue($node, $level);
            $changes[$config['level']] = array(null, $level);
        }

        $uow->scheduleExtraUpdate($node, $changes);
        $ea->setOriginalObjectProperty($uow, $oid, $config['path'], $path);
    }

    /**
     * Update $node 's children
     *
     * @param ObjectManager $om
     * @param object $node - target node
     * @param object $ea - event adapter
     * @param string $originalPath - original path of object
     * @return void
     */
    public function updateChildren(ObjectManager $om, $node, AdapterInterface $ea, $originalPath)
    {
        $meta = $om->getClassMetadata(get_class($node));
        $config = $this->listener->getConfiguration($om, $meta->name);
        $children = $this->getChildren($om, $meta, $config, $originalPath);

        foreach ($children as $child) {
            $this->updateNode($om, $child, $ea);
        }
    }

    /**
     * Remove node and its children
     *
     * @param ObjectManager $om
     * @param object $meta - Metadata
     * @param object $config - config
     * @param object $node - node to remove
     * @return void
     */
    abstract public function removeNode($om, $meta, $config, $node);

    /**
     * Returns children of the node with its original path
     *
     * @param ObjectManager $om
     * @param object $meta - Metadata
     * @param object $config - config
     * @param mixed $originalPath - original path of object
     * @return Doctrine\ODM\MongoDB\Cursor
     */
    abstract public function getChildren($om, $meta, $config, $originalPath);
}
