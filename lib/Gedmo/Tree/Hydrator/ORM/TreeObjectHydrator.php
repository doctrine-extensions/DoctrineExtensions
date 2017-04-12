<?php

namespace Gedmo\Tree\Hydrator\ORM;

use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Internal\Hydration\ObjectHydrator;
use Gedmo\Tool\Wrapper\EntityWrapper;
use Gedmo\Tree\TreeListener;

/**
 * Automatically maps the parent and children properties of Tree nodes
 *
 * @author Ilija Tovilo <ilija.tovilo@me.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TreeObjectHydrator extends ObjectHydrator
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var string
     */
    private $idField;

    /**
     * @var string
     */
    private $parentField;

    /**
     * @var string
     */
    private $childrenField;

    /**
     * We hook into the `hydrateAllData` to map the children collection of the entity
     *
     * {@inheritdoc}
     */
    protected function hydrateAllData()
    {
        $data = parent::hydrateAllData();

        if (count($data) === 0) {
            return $data;
        }

        $listener = $this->getTreeListener($this->_em);
        $entityClass = $this->getEntityClassFromHydratedData($data);
        $this->config = $listener->getConfiguration($this->_em, $entityClass);
        $this->idField = $this->getIdField($entityClass);
        $this->parentField = $this->getParentField();
        $this->childrenField = $this->getChildrenField($entityClass);

        $childrenHashmap = $this->buildChildrenHashmap($data);
        $this->populateChildrenArray($data, $childrenHashmap);

        // Only return root elements
        // The sub-nodes will be accessible via the `children` property
        return isset($childrenHashmap[null])
            ? $childrenHashmap[null]
            : array();
    }

    /**
     * Creates a hashmap to quickly find the children of a node
     *
     * ```
     * [parentId => [child1, child2, ...], ...]
     * ```
     *
     * @param array $nodes
     * @return array
     */
    protected function buildChildrenHashmap($nodes)
    {
        $r = array();

        foreach ($nodes as $node) {
            $wrapper = new EntityWrapper($node, $this->_em);
            $parentProxy = $wrapper->getPropertyValue($this->config['parent']);
            $parentId = null;

            if ($parentProxy !== null) {
                $parentWrapper = new EntityWrapper($parentProxy, $this->_em);
                $parentId = $parentWrapper->getPropertyValue($this->idField);
            }

            $r[$parentId][] = $node;
        }

        return $r;
    }

    /**
     * @param array $nodes
     * @param array $childrenHashmap
     */
    protected function populateChildrenArray($nodes, $childrenHashmap)
    {
        foreach ($nodes as $node) {
            $wrapper = new EntityWrapper($node, $this->_em);
            $nodeId = $wrapper->getPropertyValue($this->idField);
            $childrenCollection = $wrapper->getPropertyValue($this->childrenField);

            if ($childrenCollection === null) {
                $childrenCollection = new ArrayCollection();
                $wrapper->setPropertyValue($this->childrenField, $childrenCollection);
            }

            // Mark all children collections as initialized to avoid select queries
            if ($childrenCollection instanceof AbstractLazyCollection) {
                $childrenCollection->setInitialized(true);
            }

            if (!isset($childrenHashmap[$nodeId])) {
                continue;
            }

            $childrenCollection->clear();

            foreach ($childrenHashmap[$nodeId] as $child) {
                $childrenCollection->add($child);
            }
        }
    }

    /**
     * @return string
     */
    protected function getIdField($entityClass)
    {
        $meta = $this->getClassMetadata($entityClass);
        return $meta->getSingleIdentifierFieldName();
    }

    /**
     * @return string
     */
    protected function getParentField()
    {
        if (!isset($this->config['parent'])) {
            throw new \Gedmo\Exception\InvalidMappingException('The `parent` property is required for the TreeHydrator to work');
        }

        return $this->config['parent'];
    }

    /**
     * @return string
     */
    protected function getChildrenField($entityClass)
    {
        $meta = $this->getClassMetadata($entityClass);

        foreach ($meta->getReflectionProperties() as $property) {

            // Skip properties that have no association
            if (!$meta->hasAssociation($property->getName())) {
                continue;
            }

            $associationMapping = $meta->getAssociationMapping($property->getName());

            // Make sure the association is mapped by the parent property
            if ($associationMapping['mappedBy'] !== $this->parentField) {
                continue;
            }

            return $associationMapping['fieldName'];
        }

        throw new \Gedmo\Exception\InvalidMappingException('The children property could not found. It is identified through the `mappedBy` annotation to your parent property.');
    }

    /**
     * @param EntityManagerInterface $em
     * @return TreeListener
     */
    protected function getTreeListener(EntityManagerInterface $em)
    {
        foreach ($em->getEventManager()->getListeners() as $listeners) {
            foreach ($listeners as $listener) {
                if ($listener instanceof TreeListener) {
                    return $listener;
                }
            }
        }

        throw new \Gedmo\Exception\InvalidMappingException('Tree listener was not found on your entity manager, it must be hooked into the event manager');
    }

    /**
     * @param array $data
     * @return string
     */
    protected function getEntityClassFromHydratedData($data)
    {
        $firstMappedEntity = array_values($data);
        $firstMappedEntity = $firstMappedEntity[0];
        return get_class($firstMappedEntity);
    }
}
