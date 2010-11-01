<?php

namespace DoctrineExtensions\Timestampable;

use Doctrine\Common\EventSubscriber,
    Doctrine\ORM\Events,
    Doctrine\ORM\Event\LifecycleEventArgs,
    Doctrine\ORM\Event\OnFlushEventArgs,
    Doctrine\ORM\Event\LoadClassMetadataEventArgs,
    Doctrine\ORM\EntityManager,
    Doctrine\ORM\Mapping\ClassMetadata,
    Doctrine\Common\Annotations\AnnotationReader;

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
     * List of metadata configurations for Timestampable
     * classes, from annotations
     * 
     * @var array
     */
    protected $_configurations = array();
    
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
            Events::onFlush,
            Events::loadClassMetadata
        );
    }
    
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        $meta = $eventArgs->getClassMetadata();
        $class = $meta->getReflectionClass();
        if ($class->implementsInterface('DoctrineExtensions\Timestampable\Timestampable')) {
            require_once __DIR__ . '/Mapping/Annotations.php';
            $reader = new AnnotationReader();
            $reader->setAnnotationNamespaceAlias(
                'DoctrineExtensions\Timestampable\Mapping\\', 'Timestampable'
            );
    
            // property annotations
            foreach ($class->getProperties() as $property) {
                // on create
                $onCreate = $reader->getPropertyAnnotation(
                    $property, 
                    'DoctrineExtensions\Timestampable\Mapping\OnCreate'
                );
                if ($onCreate) {
                    $field = $property->getName();
                    if (!$this->_isValidField($meta, $field)) {
                        throw Exception::notValidFieldType($field, $meta->name);
                    }
                    $this->_configurations[$meta->name]['onCreate'][] = $field;
                }
                // on update
                $onUpdate = $reader->getPropertyAnnotation(
                    $property, 
                    'DoctrineExtensions\Timestampable\Mapping\OnUpdate'
                );
                if ($onUpdate) {
                    $field = $property->getName();
                    if (!$this->_isValidField($meta, $field)) {
                        throw Exception::notValidFieldType($field, $meta->name);
                    }
                    $this->_configurations[$meta->name]['onUpdate'][] = $field;
                }
            }
        }
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
                $entityClass = get_class($entity);
                $meta = $em->getClassMetadata($entityClass);
                $needChanges = false;
                
                if (isset($this->_configurations[$entityClass]['onUpdate'])) {
                    $needChanges = true;
                    foreach ($this->_configurations[$entityClass]['onUpdate'] as $field) {
                        $meta->getReflectionProperty($field)
                            ->setValue($entity, new \DateTime('now'));
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
        $uow = $em->getUnitOfWork();
        
        if ($entity instanceof Timestampable) {
            $entityClass = get_class($entity);
            $meta = $em->getClassMetadata($entityClass);
                
            if (isset($this->_configurations[$entityClass]['onUpdate'])) {
                foreach ($this->_configurations[$entityClass]['onUpdate'] as $field) {
                    $meta->getReflectionProperty($field)
                        ->setValue($entity, new \DateTime('now'));
                }
            }
            
            if (isset($this->_configurations[$entityClass]['onCreate'])) {
                foreach ($this->_configurations[$entityClass]['onCreate'] as $field) {
                    $meta->getReflectionProperty($field)
                        ->setValue($entity, new \DateTime('now'));
                }
            }
        }
    }
    
    /**
     * Checks if $field exists on entity and
     * if it is in right type
     * 
     * @param ClassMetadata $meta
     * @param string $field
     * @return boolean
     */
    protected function _isValidField(ClassMetadata $meta, $field)
    {
        return in_array($meta->getTypeOfField($field), $this->_validTypes);
    }
}