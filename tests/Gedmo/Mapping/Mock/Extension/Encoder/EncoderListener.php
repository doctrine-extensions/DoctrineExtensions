<?php

declare(strict_types=1);

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Tests\Mapping\Mock\Extension\Encoder;

use Doctrine\Common\EventArgs;
use Doctrine\Persistence\Event\LoadClassMetadataEventArgs;
use Gedmo\Mapping\Event\AdapterInterface as EventAdapterInterface;
use Gedmo\Mapping\MappedEventSubscriber;

class EncoderListener extends MappedEventSubscriber
{
    public function getSubscribedEvents(): array
    {
        return [
            'onFlush',
            'loadClassMetadata',
        ];
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $args): void
    {
        $ea = $this->getEventAdapter($args);
        // this will check for our metadata
        $this->loadMetadataForObjectClass(
            $ea->getObjectManager(),
            $args->getClassMetadata()
        );
    }

    public function onFlush(EventArgs $args): void
    {
        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
        $uow = $om->getUnitOfWork();

        // check all pending updates
        foreach ($ea->getScheduledObjectUpdates($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            // if it has our metadata lets encode the properties
            if ($config = $this->getConfiguration($om, $meta->getName())) {
                $this->encode($ea, $object, $config);
            }
        }
        // check all pending insertions
        foreach ($ea->getScheduledObjectInsertions($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            // if it has our metadata lets encode the properties
            if ($config = $this->getConfiguration($om, $meta->getName())) {
                $this->encode($ea, $object, $config);
            }
        }
    }

    protected function getNamespace(): string
    {
        // mapper must know the namespace of extension
        return __NAMESPACE__;
    }

    private function encode(EventAdapterInterface $ea, object $object, array $config): void
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
