<?php

namespace Gedmo\ReferenceIntegrity;

use Doctrine\Common\EventArgs;
use Gedmo\Exception\InvalidMappingException;
use Gedmo\Exception\ReferenceIntegrityStrictException;
use Gedmo\Mapping\MappedEventSubscriber;
use Gedmo\ReferenceIntegrity\Mapping\Validator;

/**
 * The ReferenceIntegrity listener handles the reference integrity on related documents
 *
 * @author Evert Harmeling <evert.harmeling@freshheads.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class ReferenceIntegrityListener extends MappedEventSubscriber
{
    /**
     * {@inheritDoc}
     */
    public function getSubscribedEvents()
    {
        return array(
            'loadClassMetadata',
            'preRemove',
        );
    }

    /**
     * Maps additional metadata for the Document
     *
     * @param  EventArgs $eventArgs
     * @return void
     */
    public function loadClassMetadata(EventArgs $eventArgs)
    {
        $ea = $this->getEventAdapter($eventArgs);
        $this->loadMetadataForObjectClass($ea->getObjectManager(), $eventArgs->getClassMetadata());
    }

    /**
     * Looks for referenced objects being removed
     * to nullify the relation or throw an exception
     *
     * @param  EventArgs $args
     * @return void
     */
    public function preRemove(EventArgs $args)
    {
        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
        $object = $ea->getObject();
        $class = get_class($object);
        $meta = $om->getClassMetadata($class);

        if ($config = $this->getConfiguration($om, $class)) {
            foreach ($config['referenceIntegrity'] as $property => $action) {
                $reflProp = $meta->getReflectionProperty($property);
                $refDoc = $reflProp->getValue($object);
                $fieldMapping = $meta->getFieldMapping($property);

                switch ($action) {
                    case Validator::NULLIFY:
                        if (!isset($fieldMapping['mappedBy'])) {
                            throw new InvalidMappingException(
                                sprintf(
                                    "Reference '%s' on '%s' should have 'mappedBy' option defined",
                                    $property,
                                    $meta->name
                                )
                            );
                        }

                        $subMeta = $om->getClassMetadata($fieldMapping['targetDocument']);

                        if (!$subMeta->hasField($fieldMapping['mappedBy'])) {
                            throw new InvalidMappingException(
                                sprintf(
                                    "Unable to find reference integrity [%s] as mapped property in entity - %s",
                                    $fieldMapping['mappedBy'],
                                    $fieldMapping['targetDocument']
                                )
                            );
                        }

                        $refReflProp = $subMeta->getReflectionProperty($fieldMapping['mappedBy']);

                        if ($meta->isCollectionValuedReference($property)) {
                            foreach ($refDoc as $refObj) {
                                $refReflProp->setValue($refObj, null);
                                $om->persist($refObj);
                            }
                        } else {
                            $refReflProp->setValue($refDoc, null);
                            $om->persist($refDoc);
                        }

                        break;
                    case Validator::PULL:
                        if (!isset($fieldMapping['mappedBy'])) {
                            throw new InvalidMappingException(
                                sprintf(
                                    "Reference '%s' on '%s' should have 'mappedBy' option defined",
                                    $property,
                                    $meta->name
                                )
                            );
                        }

                        $subMeta = $om->getClassMetadata($fieldMapping['targetDocument']);

                        if (!$subMeta->hasField($fieldMapping['mappedBy'])) {
                            throw new InvalidMappingException(
                                sprintf(
                                    "Unable to find reference integrity [%s] as mapped property in entity - %s",
                                    $fieldMapping['mappedBy'],
                                    $fieldMapping['targetDocument']
                                )
                            );
                        }

                        if (!$subMeta->isCollectionValuedReference($fieldMapping['mappedBy'])) {
                            throw new InvalidMappingException(
                                sprintf(
                                    "Reference integrity [%s] mapped property in entity - %s should be a Reference Many",
                                    $fieldMapping['mappedBy'],
                                    $fieldMapping['targetDocument']
                                )
                            );
                        }
						
                        $refReflProp = $subMeta->getReflectionProperty($fieldMapping['mappedBy']);

                        if ($meta->isCollectionValuedReference($property)) {
                            foreach ($refDoc as $refObj) {
                                $collection = $refReflProp->getValue($refObj);
                                $collection->removeElement($object);
                                $refReflProp->setValue($refObj, $collection);
                                $om->persist($refObj);
                            }
                        } else if (is_object($refDoc)) {
                            $collection = $refReflProp->getValue($refDoc);
                            $collection->removeElement($object);
                            $refReflProp->setValue($refDoc, $collection);
                            $om->persist($refDoc);
                        }

                        break;
                    case Validator::RESTRICT:
                        if ($meta->isCollectionValuedReference($property) && $refDoc->count() > 0) {
                            throw new ReferenceIntegrityStrictException(
                                sprintf(
                                    "The reference integrity for the '%s' collection is restricted",
                                    $fieldMapping['targetDocument']
                                )
                            );
                        }
                        if ($meta->isSingleValuedReference($property) && !is_null($refDoc)) {
                            throw new ReferenceIntegrityStrictException(
                                sprintf(
                                    "The reference integrity for the '%s' document is restricted",
                                    $fieldMapping['targetDocument']
                                )
                            );
                        }

                        break;
                }
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function getNamespace()
    {
        return __NAMESPACE__;
    }
}
