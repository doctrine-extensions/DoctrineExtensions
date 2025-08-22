<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\References;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\EventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\Event\LoadClassMetadataEventArgs;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Gedmo\Mapping\MappedEventSubscriber;
use Gedmo\References\Mapping\Event\ReferencesAdapter;

/**
 * Listener for loading and persisting cross database references.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 * @author Jonathan H. Wage <jonwage@gmail.com>
 *
 * @phpstan-type ReferenceConfiguration = array{
 *   field?: string,
 *   type?: string,
 *   class?: class-string,
 *   identifier?: string,
 *   mappedBy?: string,
 *   inversedBy?: string,
 * }
 * @phpstan-type ReferencesConfiguration = array{
 *   referenceMany?: array<string, ReferenceConfiguration>,
 *   referenceManyEmbed?: array<string, ReferenceConfiguration>,
 *   referenceOne?: array<string, ReferenceConfiguration>,
 *   useObjectClass?: class-string,
 * }
 *
 * @phpstan-extends MappedEventSubscriber<ReferencesConfiguration, ReferencesAdapter>
 *
 * @final since gedmo/doctrine-extensions 3.11
 */
class ReferencesListener extends MappedEventSubscriber
{
    /**
     * @var array<string, ObjectManager>
     */
    private array $managers;

    /**
     * @param array<string, ObjectManager> $managers
     */
    public function __construct(array $managers = [])
    {
        parent::__construct();

        $this->managers = $managers;
    }

    /**
     * @param LoadClassMetadataEventArgs $eventArgs
     *
     * @phpstan-param LoadClassMetadataEventArgs<ClassMetadata<object>, ObjectManager> $eventArgs
     *
     * @return void
     */
    public function loadClassMetadata(EventArgs $eventArgs)
    {
        $this->loadMetadataForObjectClass(
            $eventArgs->getObjectManager(), $eventArgs->getClassMetadata()
        );
    }

    /**
     * @param LifecycleEventArgs $eventArgs
     *
     * @phpstan-param LifecycleEventArgs<ObjectManager> $eventArgs
     *
     * @return void
     */
    public function postLoad(EventArgs $eventArgs)
    {
        $ea = $this->getEventAdapter($eventArgs);
        $om = $ea->getObjectManager();
        $object = $ea->getObject();
        $meta = $om->getClassMetadata(get_class($object));
        $config = $this->getConfiguration($om, $meta->getName());

        if (isset($config['referenceOne'])) {
            foreach ($config['referenceOne'] as $mapping) {
                $property = $meta->reflClass->getProperty($mapping['field']);
                $property->setAccessible(true);
                if (isset($mapping['identifier'])) {
                    $referencedObjectId = $meta->getFieldValue($object, $mapping['identifier']);
                    if (null !== $referencedObjectId) {
                        $property->setValue(
                            $object,
                            $ea->getSingleReference(
                                $this->getManager($mapping['type']),
                                $mapping['class'],
                                $referencedObjectId
                            )
                        );
                    }
                }
            }
        }

        if (isset($config['referenceMany'])) {
            foreach ($config['referenceMany'] as $mapping) {
                $property = $meta->reflClass->getProperty($mapping['field']);
                $property->setAccessible(true);
                if (isset($mapping['mappedBy'])) {
                    $id = $ea->extractIdentifier($om, $object);
                    $manager = $this->getManager($mapping['type']);
                    $class = $mapping['class'];
                    $refMeta = $manager->getClassMetadata($class);
                    $refConfig = $this->getConfiguration($manager, $refMeta->getName());
                    if (isset($refConfig['referenceOne'][$mapping['mappedBy']])) {
                        $refMapping = $refConfig['referenceOne'][$mapping['mappedBy']];
                        $identifier = $refMapping['identifier'];
                        $property->setValue(
                            $object,
                            new LazyCollection(
                                static fn () => new ArrayCollection(
                                    $manager->getRepository($class)
                                        ->findBy([
                                            $identifier => $id,
                                        ])
                                )
                            )
                        );
                    }
                }
            }
        }

        $this->updateManyEmbedReferences($eventArgs);
    }

    /**
     * @param LifecycleEventArgs $eventArgs
     *
     * @phpstan-param LifecycleEventArgs<ObjectManager> $eventArgs
     *
     * @return void
     */
    public function prePersist(EventArgs $eventArgs)
    {
        $this->updateReferences($eventArgs);
    }

    /**
     * @param LifecycleEventArgs $eventArgs
     *
     * @phpstan-param LifecycleEventArgs<ObjectManager> $eventArgs
     *
     * @return void
     */
    public function preUpdate(EventArgs $eventArgs)
    {
        $this->updateReferences($eventArgs);
    }

    /**
     * @return string[]
     */
    public function getSubscribedEvents()
    {
        return [
            'postLoad',
            'loadClassMetadata',
            'prePersist',
            'preUpdate',
        ];
    }

    /**
     * @param string        $type
     * @param ObjectManager $manager
     *
     * @return void
     */
    public function registerManager($type, $manager)
    {
        $this->managers[$type] = $manager;
    }

    /**
     * @param string $type
     *
     * @return ObjectManager
     */
    public function getManager($type)
    {
        return $this->managers[$type];
    }

    /**
     * @param LifecycleEventArgs $eventArgs
     *
     * @phpstan-param LifecycleEventArgs<ObjectManager> $eventArgs
     *
     * @return void
     */
    public function updateManyEmbedReferences(EventArgs $eventArgs)
    {
        $ea = $this->getEventAdapter($eventArgs);
        $om = $ea->getObjectManager();
        $object = $ea->getObject();
        $meta = $om->getClassMetadata(get_class($object));
        $config = $this->getConfiguration($om, $meta->getName());

        if (isset($config['referenceManyEmbed'])) {
            foreach ($config['referenceManyEmbed'] as $mapping) {
                $property = $meta->reflClass->getProperty($mapping['field']);
                $property->setAccessible(true);

                $id = $ea->extractIdentifier($om, $object);
                $manager = $this->getManager('document');

                $class = $mapping['class'];
                $refMeta = $manager->getClassMetadata($class);
                // Trigger the loading of the configuration to validate the mapping
                $this->getConfiguration($manager, $refMeta->getName());

                $identifier = $mapping['identifier'];
                $property->setValue(
                    $object,
                    new LazyCollection(
                        static fn () => new ArrayCollection(
                            $manager->getRepository($class)
                                ->findBy([
                                    $identifier => $id,
                                ])
                        )
                    )
                );
            }
        }
    }

    protected function getNamespace()
    {
        return __NAMESPACE__;
    }

    /**
     * @param LifecycleEventArgs $eventArgs
     *
     * @phpstan-param LifecycleEventArgs<ObjectManager> $eventArgs
     */
    private function updateReferences(EventArgs $eventArgs): void
    {
        $ea = $this->getEventAdapter($eventArgs);
        $om = $ea->getObjectManager();
        $object = $ea->getObject();
        $meta = $om->getClassMetadata(get_class($object));
        $config = $this->getConfiguration($om, $meta->getName());

        if (isset($config['referenceOne'])) {
            foreach ($config['referenceOne'] as $mapping) {
                if (isset($mapping['identifier'])) {
                    $property = $meta->reflClass->getProperty($mapping['field']);
                    $property->setAccessible(true);
                    $referencedObject = $property->getValue($object);

                    if (is_object($referencedObject)) {
                        $manager = $this->getManager($mapping['type']);
                        $identifier = $ea->getIdentifier($manager, $referencedObject);

                        $meta->setFieldValue(
                            $object,
                            $mapping['identifier'],
                            $identifier
                        );
                    }
                }
            }
        }

        $this->updateManyEmbedReferences($eventArgs);
    }
}
