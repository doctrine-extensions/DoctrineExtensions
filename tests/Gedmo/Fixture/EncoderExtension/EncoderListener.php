<?php

namespace Gedmo\Fixture\EncoderExtension;

use Doctrine\Common\EventArgs;
use Gedmo\Fixture\EncoderExtension\Mapping\EncoderExtensionMetadata;
use Gedmo\Mapping\MappedEventSubscriber;
use Gedmo\Mapping\ObjectManagerHelper as OMH;
use Doctrine\Common\Persistence\ObjectManager;

class EncoderListener extends MappedEventSubscriber
{
    public function getSubscribedEvents()
    {
        return array(
            'onFlush',
            'loadClassMetadata'
        );
    }

    public function loadClassMetadata(EventArgs $args)
    {
        // this will check for our metadata
        $this->loadMetadataForObjectClass(
            OMH::getObjectManagerFromEvent($args),
            $args->getClassMetadata()
        );
    }

    public function onFlush(EventArgs $args)
    {
        $om = OMH::getObjectManagerFromEvent($args);
        $uow = $om->getUnitOfWork();

        // check all pending updates
        foreach (OMH::getScheduledObjectUpdates($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            // if it has our metadata lets encode the properties
            if ($exm = $this->getConfiguration($om, $meta->name)) {
                $this->encode($om, $object, $exm);
            }
        }
        // check all pending insertions
        foreach (OMH::getScheduledObjectInsertions($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            // if it has our metadata lets encode the properties
            if ($exm = $this->getConfiguration($om, $meta->name)) {
                $this->encode($om, $object, $exm);
            }
        }
    }

    protected function getNamespace()
    {
        // mapper must know the namespace of extension
        return __NAMESPACE__;
    }

    private function encode(ObjectManager $om, $object, EncoderExtensionMetadata $exm)
    {
        $meta = $om->getClassMetadata(get_class($object));
        $uow = $om->getUnitOfWork();
        // password should be hashed only when it has changed
        $changeSet = OMH::getObjectChangeSet($uow, $object);
        foreach ($exm->getEncoderFields() as $field) {
            $options = $exm->getEncoderOptions($field);
            if (array_key_exists($field, $changeSet)) {
                $value = $meta->getReflectionProperty($field)->getValue($object);
                $method = $options['type'];
                $encoded = call_user_func($method, $options['secret'] . $value);
                $meta->getReflectionProperty($field)->setValue($object, $encoded);
            }
        }
        // recalculate changeset
        OMH::recomputeSingleObjectChangeSet($uow, $meta, $object);
    }
}
