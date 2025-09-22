<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\ReferenceIntegrity;

use Doctrine\Common\EventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\Event\LoadClassMetadataEventArgs;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Gedmo\Exception\InvalidMappingException;
use Gedmo\Exception\ReferenceIntegrityStrictException;
use Gedmo\Mapping\Event\AdapterInterface;
use Gedmo\Mapping\MappedEventSubscriber;
use Gedmo\ReferenceIntegrity\Mapping\Validator;

/**
 * The ReferenceIntegrity listener handles the reference integrity on related documents
 *
 * @phpstan-extends MappedEventSubscriber<array, AdapterInterface>
 *
 * @author Evert Harmeling <evert.harmeling@freshheads.com>
 *
 * @final since gedmo/doctrine-extensions 3.11
 */
class ReferenceIntegrityListener extends MappedEventSubscriber
{
    /**
     * @return string[]
     */
    public function getSubscribedEvents()
    {
        return [
            'loadClassMetadata',
            'preRemove',
        ];
    }

    /**
     * Maps additional metadata for the Document
     *
     * @param LoadClassMetadataEventArgs $eventArgs
     *
     * @phpstan-param LoadClassMetadataEventArgs<ClassMetadata<object>, ObjectManager> $eventArgs
     *
     * @return void
     */
    public function loadClassMetadata(EventArgs $eventArgs)
    {
        $this->loadMetadataForObjectClass($eventArgs->getObjectManager(), $eventArgs->getClassMetadata());
    }

    /**
     * Looks for referenced objects being removed
     * to nullify the relation or throw an exception
     *
     * @param LifecycleEventArgs $args
     *
     * @phpstan-param LifecycleEventArgs<ObjectManager> $args
     *
     * @return void
     */
    public function preRemove(EventArgs $args)
    {
        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
        $object = $ea->getObject();
        $class = get_class($object);
        $meta = $om->getClassMetadata($class);

        if ($config = $this->getConfiguration($om, $meta->getName())) {
            foreach ($config['referenceIntegrity'] as $property => $action) {
                $refDoc = $meta->getFieldValue($object, $property);
                $fieldMapping = $meta->getFieldMapping($property);

                switch ($action) {
                    case Validator::NULLIFY:
                        if (!isset($fieldMapping['mappedBy'])) {
                            throw new InvalidMappingException(sprintf("Reference '%s' on '%s' should have 'mappedBy' option defined", $property, $meta->getName()));
                        }

                        assert(class_exists($fieldMapping->targetDocument ?? $fieldMapping['targetDocument']));

                        $subMeta = $om->getClassMetadata($fieldMapping->targetDocument ?? $fieldMapping['targetDocument']);

                        $mappedByField = $fieldMapping->mappedBy ?? $fieldMapping['mappedBy'];

                        if (!$subMeta->hasField($mappedByField)) {
                            throw new InvalidMappingException(sprintf('Unable to find reference integrity [%s] as mapped property in entity - %s', $mappedByField, $fieldMapping->targetDocument ?? $fieldMapping['targetDocument']));
                        }

                        if ($meta->isCollectionValuedReference($property)) {
                            foreach ($refDoc as $refObj) {
                                $subMeta->setFieldValue($refObj, $mappedByField, null);
                                $om->persist($refObj);
                            }
                        } else {
                            $subMeta->setFieldValue($refDoc, $mappedByField, null);
                            $om->persist($refDoc);
                        }

                        break;
                    case Validator::PULL:
                        if (!isset($fieldMapping['mappedBy'])) {
                            throw new InvalidMappingException(sprintf("Reference '%s' on '%s' should have 'mappedBy' option defined", $property, $meta->getName()));
                        }

                        assert(class_exists($fieldMapping->targetDocument ?? $fieldMapping['targetDocument']));

                        $subMeta = $om->getClassMetadata($fieldMapping->targetDocument ?? $fieldMapping['targetDocument']);

                        $mappedByField = $fieldMapping->mappedBy ?? $fieldMapping['mappedBy'];

                        if (!$subMeta->hasField($mappedByField)) {
                            throw new InvalidMappingException(sprintf('Unable to find reference integrity [%s] as mapped property in entity - %s', $mappedByField, $fieldMapping->targetDocument ?? $fieldMapping['targetDocument']));
                        }

                        if (!$subMeta->isCollectionValuedReference($mappedByField)) {
                            throw new InvalidMappingException(sprintf('Reference integrity [%s] mapped property in entity - %s should be a Reference Many', $mappedByField, $fieldMapping->targetDocument ?? $fieldMapping['targetDocument']));
                        }

                        if ($meta->isCollectionValuedReference($property)) {
                            foreach ($refDoc as $refObj) {
                                $collection = $subMeta->getFieldValue($refObj, $mappedByField);
                                $collection->removeElement($object);
                                $subMeta->setFieldValue($refObj, $mappedByField, $collection);
                                $om->persist($refObj);
                            }
                        } elseif (is_object($refDoc)) {
                            $collection = $subMeta->getFieldValue($refDoc, $mappedByField);
                            $collection->removeElement($object);
                            $subMeta->setFieldValue($refDoc, $mappedByField, $collection);
                            $om->persist($refDoc);
                        }

                        break;
                    case Validator::RESTRICT:
                        if ($meta->isCollectionValuedReference($property) && $refDoc->count() > 0) {
                            throw new ReferenceIntegrityStrictException(sprintf("The reference integrity for the '%s' collection is restricted", $fieldMapping->targetDocument ?? $fieldMapping['targetDocument']));
                        }
                        if ($meta->isSingleValuedReference($property) && null !== $refDoc) {
                            throw new ReferenceIntegrityStrictException(sprintf("The reference integrity for the '%s' document is restricted", $fieldMapping->targetDocument ?? $fieldMapping['targetDocument']));
                        }

                        break;
                }
            }
        }
    }

    protected function getNamespace()
    {
        return __NAMESPACE__;
    }
}
