<?php

namespace Gedmo\References;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\EventArgs;
use Doctrine\Common\Persistence\ObjectManager;
use Gedmo\Exception\InvalidArgumentException;
use Gedmo\Mapping\MappedEventSubscriber;
use Gedmo\Mapping\ObjectManagerHelper as OMH;
use Gedmo\Exception\UnexpectedValueException;

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
     * A list of object managers to link references,
     * in pairs of
     *      managerType => ObjectManagerInstance
     *
     * Supported types are "entity" and "document"
     *
     * @var array
     */
    private $managers;

    /**
     * Listener can be initialized with a list of managers
     * to link references
     *
     * @param array $managers - list of managers, check above
     */
    public function __construct(array $managers = array())
    {
        foreach ($managers as $type => $manager) {
            $this->registerManager($type, $manager);
        }
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
        $config = $this->getConfiguration($om, $meta->name);
        foreach ($config['referenceOne'] as $mapping) {
            $property = $meta->reflClass->getProperty($mapping['field']);
            $property->setAccessible(true);
            if (isset($mapping['identifier'])) {
                $referencedObjectId = $meta->getFieldValue($object, $mapping['identifier']);
                if (null !== $referencedObjectId) {
                    $manager = $this->getManager($mapping['type']);
                    if (get_class($manager) === get_class($om)) {
                        throw new UnexpectedValueException("Referenced manager is of the same type: {$mapping['type']}");
                    }
                    $property->setValue($object, $this->getSingleReference($manager, $mapping['class'], $referencedObjectId));
                }
            }
        }

        foreach ($config['referenceMany'] as $mapping) {
            $property = $meta->reflClass->getProperty($mapping['field']);
            $property->setAccessible(true);
            if (isset($mapping['mappedBy'])) {
                $id = OMH::getIdentifier($om, $object);
                $manager = $this->getManager($mapping['type']);
                $class = $mapping['class'];
                $refMeta = $manager->getClassMetadata($class);
                $refConfig = $this->getConfiguration($manager, $refMeta->name);
                if (isset($refConfig['referenceOne'][$mapping['mappedBy']])) {
                    $refMapping = $refConfig['referenceOne'][$mapping['mappedBy']];
                    $identifier = $refMapping['identifier'];
                    $property->setValue(
                        $object,
                        new LazyCollection(
                            function() use ($id, &$manager, $class, $identifier) {
                                $results = $manager
                                    ->getRepository($class)
                                    ->findBy(array(
                                        $identifier => $id,
                                    ));

                                return new ArrayCollection((is_array($results) ? $results : $results->toArray()));
                            }
                        )
                    );
                }
            }
        }

        $this->updateManyEmbedReferences($eventArgs);
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
     * Registeners a $manager of type $type
     * to support linking references
     *
     * @param string $type - document or entity
     * @param \Doctrine\Common\Persistence\ObjectManager $om
     * @return \Gedmo\References\ReferencesListener
     */
    public function registerManager($type, ObjectManager $manager)
    {
        $this->managers[$type] = $manager;
        return $this;
    }

    /**
     * Get a registered manager of $type
     *
     * @param string $type - document or entity
     * @return \Doctrine\Common\Persistence\ObjectManager
     */
    public function getManager($type)
    {
        if (!isset($this->managers[$type])) {
            throw new UnexpectedValueException("Object manager for type: {$type} is not registered");
        }
        return $this->managers[$type];
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
     * Updates linked references
     *
     * @param \Doctrine\Common\EventArgs $event
     */
    private function updateReferences(EventArgs $event)
    {
        $om = OMH::getObjectManagerFromEvent($event);
        $object = OMH::getObjectFromEvent($event);
        $meta = $om->getClassMetadata(get_class($object));
        $config = $this->getConfiguration($om, $meta->name);
        foreach ($config['referenceOne'] as $mapping) {
            if (isset($mapping['identifier'])) {
                $property = $meta->reflClass->getProperty($mapping['field']);
                $property->setAccessible(true);
                $referencedObject = $property->getValue($object);
                if (is_object($referencedObject)) {
                    $meta->setFieldValue(
                        $object,
                        $mapping['identifier'],
                        OMH::getIdentifier($this->getManager($mapping['type']), $referencedObject)
                    );
                }
            }
        }
        $this->updateManyEmbedReferences($eventArgs);
    }

    public function updateManyEmbedReferences(EventArgs $eventArgs)
    {
        $ea = $this->getEventAdapter($eventArgs);
        $om = $ea->getObjectManager();
        $object = $ea->getObject();
        $meta = $om->getClassMetadata(get_class($object));
        $config = $this->getConfiguration($om, $meta->name);
        foreach ($config['referenceManyEmbed'] as $mapping) {
            $property = $meta->reflClass->getProperty($mapping['field']);
            $property->setAccessible(true);

            $id = $ea->extractIdentifier($om, $object);
            $manager = $this->getManager('document');

            $class = $mapping['class'];
            $refMeta = $manager->getClassMetadata($class);
            $refConfig = $this->getConfiguration($manager, $refMeta->name);

            $identifier = $mapping['identifier'];
            $property->setValue(
                $object,
                new LazyCollection(
                    function() use ($id, &$manager, $class, $identifier) {
                        $results = $manager
                            ->getRepository($class)
                            ->findBy(array(
                                $identifier => $id,
                            ));

                        return new ArrayCollection((is_array($results) ? $results : $results->toArray()));
                    }
                )
            );
        }
    }
}
