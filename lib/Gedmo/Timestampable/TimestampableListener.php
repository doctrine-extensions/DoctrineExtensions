<?php

namespace Gedmo\Timestampable;

use Doctrine\Common\EventSubscriber,
    Doctrine\ORM\Events,
    Doctrine\ORM\Event\LifecycleEventArgs,
    Doctrine\ORM\Event\OnFlushEventArgs,
    Gedmo\Mapping\MappedEventSubscriber,
    Doctrine\ORM\EntityManager;

/**
 * The Timestampable listener handles the update of
 * dates on creation and update of entity.
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Timestampable
 * @subpackage TimestampableListener
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TimestampableListener extends MappedEventSubscriber implements EventSubscriber
{
    /**
     * Specifies the list of events to listen
     * 
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            Events::prePersist,
            Events::onFlush,
            Events::loadClassMetadata
        );
    }
    
	/**
     * {@inheritDoc}
     */
    protected function _getNamespace()
    {
        return __NAMESPACE__;
    }
    
    /**
     * Looks for Timestampable entities being updated
     * to update modification date
     * 
     * @param OnFlushEventArgs $args
     * @return void
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();
        // check all scheduled updates
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $entityClass = get_class($entity);
            if ($config = $this->getConfiguration($em, $entityClass)) {
                $meta = $em->getClassMetadata($entityClass);
                $needChanges = false;
                
                if (isset($config['update'])) {
                    $needChanges = true;
                    foreach ($config['update'] as $field) {
                        $meta->getReflectionProperty($field)
                            ->setValue($entity, new \DateTime('now'));
                    }
                }
                
                if (isset($config['change'])) {
                    $changeSet = $uow->getEntityChangeSet($entity);
                    foreach ($config['change'] as $options) {
                        $tracked = $options['trackedField'];
                        $trackedChild = null;
                        $parts = explode('.', $tracked);
                        if (isset($parts[1])) {
                            $tracked = $parts[0];
                            $trackedChild = $parts[1];
                        }
                        
                        if (isset($changeSet[$tracked])) {
                            $changes = $changeSet[$tracked];
                            if (isset($trackedChild)) {
                                $object = $changes[1];
                                if (!is_object($object)) {
                                    throw Exception::objectExpected($tracked, $meta->name);
                                }
                                $objectMeta = $em->getClassMetadata(get_class($object));
                                $value = $objectMeta->getReflectionProperty($trackedChild)
                                    ->getValue($object);
                            } else {
                                $value = $changes[1];
                            }
                            if ($options['value'] == $value) {
                                $needChanges = true;
                                $meta->getReflectionProperty($options['field'])
                                    ->setValue($entity, new \DateTime('now'));
                            }
                        }
                    }
                }
                
                if ($needChanges) {
                    $uow->recomputeSingleEntityChangeSet($meta, $entity);
                }
            }
        }
    }
    
    /**
     * Checks for persisted Timestampable entities
     * to update creation and modification dates
     * 
     * @param LifecycleEventArgs $args
     * @return void
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        $em = $args->getEntityManager();
        $entity = $args->getEntity();
        
        $meta = $em->getClassMetadata(get_class($entity));
        if ($config = $this->getConfiguration($em, $meta->name)) {
            if (isset($config['update'])) {
                foreach ($config['update'] as $field) {
                    $meta->getReflectionProperty($field)
                        ->setValue($entity, new \DateTime('now'));
                }
            }
            
            if (isset($config['create'])) {
                foreach ($config['create'] as $field) {
                    $meta->getReflectionProperty($field)
                        ->setValue($entity, new \DateTime('now'));
                }
            }
        }
    }
}