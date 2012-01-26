<?php

namespace Gedmo\SoftDeleteable;

use Doctrine\Common\Persistence\ObjectManager,
    Doctrine\Common\Persistence\Mapping\ClassMetadata,
    Gedmo\Mapping\MappedEventSubscriber,
    Gedmo\Loggable\Mapping\Event\LoggableAdapter,
    Doctrine\Common\EventArgs;

/**
 * SoftDeleteable listener
 *
 * @author Gustavo Falco <comfortablynumb84@gmail.com>
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.SoftDeleteable
 * @subpackage SoftDeleteableListener
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SoftDeleteableListener extends MappedEventSubscriber
{
    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return array(
            'loadClassMetadata',
            'preRemove'
        );
    }

    /**
     * Mapps additional metadata
     *
     * @param EventArgs $eventArgs
     * @return void
     */
    public function loadClassMetadata(EventArgs $eventArgs)
    {
        $ea = $this->getEventAdapter($eventArgs);
        $om = $ea->getObjectManager();
        $meta = $eventArgs->getClassMetadata();
        $this->loadMetadataForObjectClass($ea->getObjectManager(), $eventArgs->getClassMetadata());
        $config = isset($this->configurations[$meta->name]) ? $this->configurations[$meta->name] : array();
        
        if (isset($config['softDeleteable']) && $config['softDeleteable']) {
            if ($config['autoMap']) {
                $meta->mapField(array(
                     'fieldName'         => $config['fieldName'],
                     'id'                => false,
                     'type'              => 'datetime',
                     'nullable'          => true
                ));

                if ($cacheDriver = $om->getMetadataFactory()->getCacheDriver()) {
                    $cacheDriver->save($meta->name."\$CLASSMETADATA", $meta, null);
                }
            }
        }
    }

    /**
     * If it's a SoftDeleteable object, update the "deletedAt" field
     * and skip the removal of the object
     *
     * @param EventArgs $args
     * @return void
     */
    public function preRemove(EventArgs $args)
    {
        
    }

    /**
     * {@inheritDoc}
     */
    protected function getNamespace()
    {
        return __NAMESPACE__;
    }
}