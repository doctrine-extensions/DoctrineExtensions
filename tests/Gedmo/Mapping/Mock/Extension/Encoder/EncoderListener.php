<?php

// file: vendor/Extension/Encoder/EncoderListener.php

namespace Gedmo\Tests\Mapping\Mock\Extension\Encoder;

use Doctrine\Common\EventArgs;
use Gedmo\Mapping\Event\AdapterInterface as EventAdapterInterface;
use Gedmo\Mapping\MappedEventSubscriber;

class EncoderListener extends MappedEventSubscriber
{
    public function getSubscribedEvents()
    {
        return [
            'onFlush',
            'loadClassMetadata',
        ];
    }

    public function loadClassMetadata(EventArgs $args)
    {
        $ea = $this->getEventAdapter($args);
        // this will check for our metadata
        $this->loadMetadataForObjectClass(
            $ea->getObjectManager(),
            $args->getClassMetadata()
        );
    }

    public function onFlush(EventArgs $args)
    {
        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
        $uow = $om->getUnitOfWork();

        // check all pending updates
        foreach ($ea->getScheduledObjectUpdates($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            // if it has our metadata lets encode the properties
            if ($config = $this->getConfiguration($om, $meta->name)) {
                $this->encode($ea, $object, $config);
            }
        }
        // check all pending insertions
        foreach ($ea->getScheduledObjectInsertions($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            // if it has our metadata lets encode the properties
            if ($config = $this->getConfiguration($om, $meta->name)) {
                $this->encode($ea, $object, $config);
            }
        }
    }

    protected function getNamespace()
    {
        // mapper must know the namespace of extension
        return __NAMESPACE__;
    }

    private function encode(EventAdapterInterface $ea, $object, $config)
    {
        $om = $ea->getObjectManager();
        $meta = $om->getClassMetadata(get_class($object));
        $uow = $om->getUnitOfWork();
        foreach ($config['encode'] as $field => $options) {
            $value = $meta->getReflectionProperty($field)->getValue($object);
            $method = $options['type'];
            $encoded = $method($options['secret'].$value);
            $meta->getReflectionProperty($field)->setValue($object, $encoded);
        }
        // recalculate changeset
        $ea->recomputeSingleObjectChangeSet($uow, $meta, $object);
    }
}
