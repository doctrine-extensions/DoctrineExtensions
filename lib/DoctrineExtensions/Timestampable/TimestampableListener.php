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
     * If set to true it will check only Timestampable
     * entities for annotations
     * 
     * @var boolean
     */
    private $_requireInterface = true;
    
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
     * If set to false it will scan all entities
     * for timestampable annotations
     * 
     * @param boolean $bool
     * @return void
     */
    public function setRequiresInterface($bool)
    {
        $this->_requireInterface = (boolean)$bool;
    }
    
    /**
     * Scans the entities for Timestampable annotations
     * 
     * @param LoadClassMetadataEventArgs $eventArgs
     * @return void
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        $meta = $eventArgs->getClassMetadata();
        $class = $meta->getReflectionClass();
        if ($class->implementsInterface('DoctrineExtensions\Timestampable\Timestampable') || !$this->_requireInterface) {
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
                
                // on change
                $onChange = $reader->getPropertyAnnotation(
                    $property, 
                    'DoctrineExtensions\Timestampable\Mapping\OnChange'
                );
                if ($onChange) {
                    $field = $property->getName();
                    if (!$this->_isValidField($meta, $field)) {
                        throw Exception::notValidFieldType($field, $meta->name);
                    }
                    if (isset($onChange->field) && isset($onChange->value)) {
                        $this->_configurations[$meta->name]['onChange'][] = array(
                            'field' => $field,
                            'trackedField' => $onChange->field,
                            'value' => $onChange->value 
                        );
                    } else {
                        throw Exception::parametersMissing($meta->name);
                    }
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
            if ($entity instanceof Timestampable || !$this->_requireInterface) {
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
                
                if (isset($this->_configurations[$entityClass]['onChange'])) {
                    $changeSet = $uow->getEntityChangeSet($entity);
                    foreach ($this->_configurations[$entityClass]['onChange'] as $options) {
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
        $uow = $em->getUnitOfWork();
        
        if ($entity instanceof Timestampable || !$this->_requireInterface) {
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
     * Checks if $field type is valid
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