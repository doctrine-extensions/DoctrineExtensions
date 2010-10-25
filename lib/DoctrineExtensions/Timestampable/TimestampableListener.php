<?php

namespace DoctrineExtensions\Timestampable;

use Doctrine\Common\EventSubscriber,
    Doctrine\ORM\Events,
    Doctrine\ORM\Event\LifecycleEventArgs,
    Doctrine\ORM\Event\OnFlushEventArgs,
    Doctrine\ORM\EntityManager;

/**
 * The Timestampable listener handles the update of
 * dates on creation and update of entity.
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package DoctrineExtensions.Timestampable
 * @subpackage TimestampableListener
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TimestampableListener implements EventSubscriber
{   
    /**
     * List of types which are valid for timestamp
     * 
     * @var array
     */
    private $_validTypes = array(
        'date',
        'time',
        'datetime'
    );
    
    /**
     * Specifies the list of events to listen
     * 
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            Events::prePersist,
            Events::onFlush
        );
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
            if ($entity instanceof Timestampable) {
                $meta = $em->getClassMetadata(get_class($entity));
                $needChanges = false;
                
                if ($this->_isFieldAvailable($em, $entity, 'updated')) {
                    $needChanges = true;
                    $meta->getReflectionProperty('updated')->setValue($entity, new \DateTime('now'));
                } elseif ($this->_isFieldAvailable($em, $entity, 'modified')) {
                    $needChanges = true;
                    $meta->getReflectionProperty('modified')->setValue($entity, new \DateTime('now'));
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
        $uow = $em->getUnitOfWork();
        
        if ($entity instanceof Timestampable) {
            $meta = $em->getClassMetadata(get_class($entity));
            if ($this->_isFieldAvailable($em, $entity, 'created')) {
                $meta->getReflectionProperty('created')->setValue($entity, new \DateTime('now'));
            }
            if ($this->_isFieldAvailable($em, $entity, 'updated')) {
                $meta->getReflectionProperty('updated')->setValue($entity, new \DateTime('now'));
            } elseif ($this->_isFieldAvailable($em, $entity, 'modified')) {
                $meta->getReflectionProperty('modified')->setValue($entity, new \DateTime('now'));
            }
        }
    }
    
    /**
     * Checks if $field exists on entity and
     * if it is in right type
     * 
     * @param EntityManager $em
     * @param Timestampable $entity
     * @param string $field
     * @return boolean
     */
    protected function _isFieldAvailable(EntityManager $em, Timestampable $entity, $field)
    {
        $meta = $em->getClassMetadata(get_class($entity));
        if ($meta->hasField($field) && in_array($meta->getTypeOfField($field), $this->_validTypes)) {
            return true;
        }
        return false;
    }
}