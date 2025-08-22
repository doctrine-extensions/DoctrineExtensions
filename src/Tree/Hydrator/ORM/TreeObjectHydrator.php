<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tree\Hydrator\ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Internal\Hydration\ObjectHydrator;
use Doctrine\ORM\PersistentCollection;
use Gedmo\Exception\InvalidMappingException;
use Gedmo\Tool\ORM\Hydration\EntityManagerRetriever;
use Gedmo\Tool\ORM\Hydration\HydratorCompat;
use Gedmo\Tree\TreeListener;

/**
 * Automatically maps the parent and children properties of Tree nodes
 *
 * @author Ilija Tovilo <ilija.tovilo@me.com>
 *
 * @final since gedmo/doctrine-extensions 3.11
 */
class TreeObjectHydrator extends ObjectHydrator
{
    use EntityManagerRetriever;
    use HydratorCompat;

    /**
     * @var array<string, mixed>
     */
    private $config = [];

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
     * @param object $object
     * @param string $property
     * @param mixed  $value
     *
     * @return void
     */
    public function setPropertyValue($object, $property, $value)
    {
        $meta = $this->getEntityManager()->getClassMetadata(get_class($object));
        $meta->getReflectionProperty($property)->setValue($object, $value);
    }

    /**
     * We hook into the `hydrateAllData` to map the children collection of the entity
     *
     * @return array<int, object>
     */
    protected function doHydrateAllData()
    {
        $data = parent::hydrateAllData();

        if ([] === $data) {
            return $data;
        }

        $listener = $this->getTreeListener($this->getEntityManager());
        $entityClass = $this->getEntityClassFromHydratedData($data);
        $this->config = $listener->getConfiguration($this->getEntityManager(), $entityClass);
        $this->idField = $this->getIdField($entityClass);
        $this->parentField = $this->getParentField();
        $this->childrenField = $this->getChildrenField($entityClass);

        $childrenHashmap = $this->buildChildrenHashmap($data);
        $this->populateChildrenArray($data, $childrenHashmap);

        // Only return root elements or elements who's parents haven't been fetched
        // The sub-nodes will be accessible via the `children` property
        return $this->getRootNodes($data);
    }

    /**
     * Creates a hashmap to quickly find the children of a node
     *
     * ```
     * [parentId => [child1, child2, ...], ...]
     * ```
     *
     * @param array<int, object> $nodes
     *
     * @return array<int|string, array<int, object>>
     */
    protected function buildChildrenHashmap($nodes)
    {
        $r = [];

        foreach ($nodes as $node) {
            $parentProxy = $this->getPropertyValue($node, $this->config['parent']);
            $parentId = null;

            if (null !== $parentProxy) {
                $parentId = $this->getPropertyValue($parentProxy, $this->idField);
            }

            $r[$parentId][] = $node;
        }

        return $r;
    }

    /**
     * @param array<int, object>                    $nodes
     * @param array<int|string, array<int, object>> $childrenHashmap
     *
     * @return void
     */
    protected function populateChildrenArray($nodes, $childrenHashmap)
    {
        foreach ($nodes as $node) {
            $nodeId = $this->getPropertyValue($node, $this->idField);
            $childrenCollection = $this->getPropertyValue($node, $this->childrenField);

            if (null === $childrenCollection) {
                $childrenCollection = new ArrayCollection();
                $this->setPropertyValue($node, $this->childrenField, $childrenCollection);
            }

            // Initialize all the children collections in order to avoid "SELECT" queries.
            if ($childrenCollection instanceof PersistentCollection && !$childrenCollection->isInitialized()) {
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
     * @param array<int, object> $nodes
     *
     * @return array<int, object>
     */
    protected function getRootNodes($nodes)
    {
        $idHashmap = $this->buildIdHashmap($nodes);
        $rootNodes = [];

        foreach ($nodes as $node) {
            $parentProxy = $this->getPropertyValue($node, $this->config['parent']);
            $parentId = null;

            if (null !== $parentProxy) {
                $parentId = $this->getPropertyValue($parentProxy, $this->idField);
            }

            if (null === $parentId || !array_key_exists($parentId, $idHashmap)) {
                $rootNodes[] = $node;
            }
        }

        return $rootNodes;
    }

    /**
     * Creates a hashmap of all nodes returned in the query
     *
     * ```
     * [node1.id => true, node2.id => true, ...]
     * ```
     *
     * @param array<int, object> $nodes
     *
     * @return array<mixed, true>
     */
    protected function buildIdHashmap(array $nodes)
    {
        $ids = [];

        foreach ($nodes as $node) {
            $id = $this->getPropertyValue($node, $this->idField);
            $ids[$id] = true;
        }

        return $ids;
    }

    /**
     * @param string $entityClass
     *
     * @phpstan-param class-string $entityClass
     *
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
            throw new InvalidMappingException('The `parent` property is required for the TreeHydrator to work');
        }

        return $this->config['parent'];
    }

    /**
     * @param string $entityClass
     *
     * @phpstan-param class-string $entityClass
     *
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

        throw new InvalidMappingException('The children property could not found. It is identified through the `mappedBy` annotation to your parent property.');
    }

    /**
     * @return TreeListener
     */
    protected function getTreeListener(EntityManagerInterface $em)
    {
        foreach ($em->getEventManager()->getAllListeners() as $listeners) {
            foreach ($listeners as $listener) {
                if ($listener instanceof TreeListener) {
                    return $listener;
                }
            }
        }

        throw new InvalidMappingException('Tree listener was not found on your entity manager, it must be hooked into the event manager');
    }

    /**
     * @param array<int, object> $data
     *
     * @return string
     */
    protected function getEntityClassFromHydratedData($data)
    {
        $firstMappedEntity = array_values($data);
        $firstMappedEntity = $firstMappedEntity[0];

        return $this->getEntityManager()->getClassMetadata(get_class($firstMappedEntity))->rootEntityName;
    }

    /**
     * @param object $object
     * @param string $property
     *
     * @return mixed
     */
    protected function getPropertyValue($object, $property)
    {
        $meta = $this->getEntityManager()->getClassMetadata(get_class($object));

        return $meta->getReflectionProperty($property)->getValue($object);
    }
}
