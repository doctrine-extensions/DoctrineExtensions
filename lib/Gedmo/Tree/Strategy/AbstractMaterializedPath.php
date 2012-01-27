<?php

namespace Gedmo\Tree\Strategy;

use Gedmo\Tree\Strategy;
use Doctrine\ORM\EntityManager;
use Gedmo\Tree\TreeListener;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Query;
use Doctrine\Common\Persistence\ObjectManager;

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
    public function processScheduledUpdate($om, $node, $ea)
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
            $this->updateNodesChildren($om, $node, $ea, $originalPath);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function processPostPersist($om, $node, $ea)
    {
        $this->updateNode($om, $node, $ea);
    }

    /**
     * {@inheritdoc}
     */
    public function onFlushEnd($om)
    {
        $this->alreadyProcessedObjects = array();
        $this->scheduledForPathProcess = array();
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
    public function updateNode(ObjectManager $om, $node, $ea)
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
        $path = $pathSourceProp->getValue($node).$config['path_separator'];

        if ($parent) {
            $changeSet = $uow->isScheduledForUpdate($parent) ? $ea->getObjectChangeSet($uow, $parent) : false;
            $pathOrPathSourceHasChanged = $changeSet && (isset($changeSet[$config['path_source']]) || isset($changeSet[$config['path']]));

            if ($pathOrPathSourceHasChanged || !$pathProp->getValue($parent)) {
                $this->updateNode($om, $parent, $ea);
            }

            $path = $pathProp->getValue($parent).$path;
        }

        $pathProp->setValue($node, $path);
        $uow->scheduleExtraUpdate($node, array(
            $config['path'] => array(null, $path)
        ));
        $ea->setOriginalObjectProperty($uow, $oid, $config['path'], $path);
    }

    /**
     * Update $node 's children
     *
     * @param ObjectManager $om
     * @param object $node - target node
     * @param object $ea - event adapter
     * @param mixed $originalPath - original path of object
     * @return void
     */
    public function updateNodesChildren(ObjectManager $om, $node, $ea, $originalPath)
    {
        $meta = $om->getClassMetadata(get_class($node));
        $config = $this->listener->getConfiguration($om, $meta->name);
        $children = $this->getChildren($om, $meta, $config, $originalPath);

        foreach ($children as $child) {
            $this->updateNode($om, $child, $ea);
        }
    }
}
