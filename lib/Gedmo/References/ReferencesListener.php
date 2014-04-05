<?php

namespace Gedmo\References;

use Gedmo\Exception\InvalidArgumentException;
use Gedmo\Mapping\MappedEventSubscriber;
use Gedmo\Mapping\ObjectManagerHelper as OMH;
use Gedmo\Exception\UnexpectedValueException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\EventArgs;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * Listener for loading and persisting cross database references.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 * @author Jonathan H. Wage <jonwage@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class ReferencesListener extends MappedEventSubscriber
{
    /**
     * Object manager registry
     *
     * @var \Doctrine\Common\Persistence\ManagerRegistry
     */
    private $managerRegistry;

    /**
     * Listener should be initialized with manager registry
     * to link references
     *
     * @param \Doctrine\Common\Persistence\ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * {@inheritDoc}
     */
    public function getSubscribedEvents()
    {
        return array(
            'postLoad',
            'loadClassMetadata',
            'prePersist',
            'preUpdate',
        );
    }

    /**
     * Load the extension metadata
     *
     * @param \Doctrine\Common\EventArgs $event
     */
    public function loadClassMetadata(EventArgs $event)
    {
        $this->loadMetadataForObjectClass(OMH::getObjectManagerFromEvent($event), $event->getClassMetadata());
    }

    /**
     * This event triggers on post initialization of an object,
     * currently it initializes references to foreign manager
     * if any are confihured
     *
     * @param \Doctrine\Common\EventArgs $event
     */
    public function postLoad(EventArgs $event)
    {
        $om = OMH::getObjectManagerFromEvent($event);
        $object = OMH::getObjectFromEvent($event);
        $meta = $om->getClassMetadata(get_class($object));
        if ($exm = $this->getConfiguration($om, $meta->name)) {
            foreach ($exm->getReferencesOfType('referenceOne') as $field => $mapping) {
                $property = $meta->reflClass->getProperty($field);
                $property->setAccessible(true);
                if (isset($mapping['identifier'])) {
                    $referencedObjectId = $meta->getFieldValue($object, $mapping['identifier']);
                    if (null !== $referencedObjectId) {
                        if ($om === $manager = $this->getManager($mapping['class'])) {
                            throw new UnexpectedValueException("Referenced manager manages the same class: {$mapping['class']}, use standard relation mapping");
                        }
                        $property->setValue($object, $this->getSingleReference($manager, $mapping['class'], $referencedObjectId));
                    }
                }
            }

            foreach ($exm->getReferencesOfType('referenceMany') as $field => $mapping) {
                $property = $meta->reflClass->getProperty($field);
                $property->setAccessible(true);
                if (isset($mapping['mappedBy'])) {
                    $id = OMH::getIdentifier($om, $object);
                    $class = $mapping['class'];
                    if ($om === $manager = $this->getManager($class)) {
                        throw new UnexpectedValueException("Referenced manager manages the same class: $class, use standard relation mapping");
                    }
                    $refMeta = $manager->getClassMetadata($class);
                    $refConfig = $this->getConfiguration($manager, $refMeta->name);
                    if ($ref = $refConfig->getReferenceMapping('referenceOne', $mapping['mappedBy'])) {
                        $identifier = $ref['identifier'];
                        $sort = $mapping['sort'];
                        $limit = $mapping['limit'];
                        $skip = $mapping['skip'];
                        $property->setValue($object, new LazyCollection(function() use ($id, &$manager, $class, $identifier, $sort, $limit, $skip) {
                            $results = $manager->getRepository($class)->findBy(
                                array(
                                    $identifier => $id,
                                ),
                                $sort,
                                $limit,
                                $skip
                            );
                            return new ArrayCollection((is_array($results) ? $results : $results->toArray()));
                        }));
                    }
                }
            }
        }

        $this->updateManyEmbedReferences($event);
    }

    /**
     * Hook to update references
     *
     * @param \Doctrine\Common\EventArgs $event
     */
    public function prePersist(EventArgs $event)
    {
        $this->updateReferences($event);
    }

    /**
     * Hook to update references
     *
     * @param \Doctrine\Common\EventArgs $event
     */
    public function preUpdate(EventArgs $event)
    {
        $this->updateReferences($event);
    }

    /**
     * {@inheritDoc}
     */
    protected function getNamespace()
    {
        return __NAMESPACE__;
    }

    /**
     * Get a reference to relation managed by another
     * manager $om
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $om
     * @param string $class
     * @param mixed $identifier
     * @return mixed - object or proxy
     */
    protected function getSingleReference(ObjectManager $om, $class, $identifier)
    {
        $meta = $om->getClassMetadata($class);
        if (!$meta->isInheritanceTypeNone()) {
            return $om->find($class, $identifier);
        }
        return $om->getReference($class, $identifier);
    }

    /**
     * Get object manager from registry which handles $class
     *
     * @param string $class
     * @return \Doctrine\Common\Persistence\ObjectManager
     */
    private function getManager($class)
    {
        if (null === $om = $this->managerRegistry->getManagerForClass($class)) {
            throw new UnexpectedValueException("Could not find any manager for object class: {$class}");
        }
        return $om;
    }

    /**
     * Updates linked references
     *
     * @param \Doctrine\Common\EventArgs $event
     */
    private function updateReferences(EventArgs $event)
    {
        $om = OMH::getObjectManagerFromEvent($event);
        $object = OMH::getObjectFromEvent($event);
        $meta = $om->getClassMetadata(get_class($object));
        if ($exm = $this->getConfiguration($om, $meta->name)) {
            foreach ($exm->getReferencesOfType('referenceOne') as $field => $mapping) {
                if (isset($mapping['identifier'])) {
                    $property = $meta->reflClass->getProperty($field);
                    $property->setAccessible(true);
                    $referencedObject = $property->getValue($object);
                    if (is_object($referencedObject)) {
                        if ($om === $manager = $this->getManager($mapping['class'])) {
                            throw new UnexpectedValueException("Referenced manager manages the same class: {$mapping['class']}, use standard relation mapping");
                        }
                        $meta->setFieldValue($object, $mapping['identifier'], OMH::getIdentifier($manager, $referencedObject));
                    }
                }
            }
        }
        $this->updateManyEmbedReferences($event);
    }

    /**
     * Updates linked embedded references
     *
     * @param \Doctrine\Common\EventArgs $event
     */
    public function updateManyEmbedReferences(EventArgs $event)
    {
        $om = OMH::getObjectManagerFromEvent($event);
        $object = OMH::getObjectFromEvent($event);
        $meta = $om->getClassMetadata(get_class($object));
        if ($exm = $this->getConfiguration($om, $meta->name)) {
            foreach ($exm->getReferencesOfType('referenceManyEmbed') as $field => $mapping) {
                $property = $meta->reflClass->getProperty($field);
                $property->setAccessible(true);

                $id = OMH::getIdentifier($om, $object);
                if ($om === $manager = $this->getManager($mapping['class'])) {
                    throw new UnexpectedValueException("Referenced manager manages the same class: {$mapping['class']}, use standard relation mapping");
                }

                $class = $mapping['class'];
                $refMeta = $manager->getClassMetadata($class);
                $refConfig = $this->getConfiguration($manager, $refMeta->name);

                $identifier = $mapping['identifier'];
                $sort = $mapping['sort'];
                $limit = $mapping['limit'];
                $skip = $mapping['skip'];
                $property->setValue($object, new LazyCollection(function() use ($id, &$manager, $class, $identifier, $sort, $limit, $skip) {
                    $results = $manager->getRepository($class)->findBy(
                        array(
                            $identifier => $id,
                        ),
                        $sort,
                        $limit,
                        $skip
                    );
                    return new ArrayCollection((is_array($results) ? $results : $results->toArray()));
                }));
            }
        }
    }
}
