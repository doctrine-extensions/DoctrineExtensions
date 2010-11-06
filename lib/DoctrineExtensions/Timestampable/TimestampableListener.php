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
     * Get the configuration for specific entity class
     * if cache driver is present it scans it also
     * 
     * @param EntityManager $em
     * @param string $class
     * @return array
     */
    public function getConfiguration(EntityManager $em, $class) {
        $config = array();
        if (isset($this->_configurations[$class])) {
            $config = $this->_configurations[$class];
        } else {
            $cacheDriver = $em->getMetadataFactory()->getCacheDriver();
            if (($cached = $cacheDriver->fetch("{$class}\$TIMESTAMPABLE_CLASSMETADATA")) !== false) {
                $this->_configurations[$class] = $cached;
                $config = $cached;
            }
        }
        return $config;
    }
    
    /**
     * Scans the entities for Timestampable annotations
     * 
     * @param LoadClassMetadataEventArgs $eventArgs
     * @return void
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        if (!method_exists($eventArgs, 'getEntityManager')) {
            throw new RuntimeException('TimestampableListener: update to latest ORM version, minimal RC1 from github');
        }
        $em = $eventArgs->getEntityManager();
        $cacheDriver = $em->getMetadataFactory()->getCacheDriver();      
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
        if ($cacheDriver && isset($this->_configurations[$meta->name])) {
            $cacheDriver->save(
                "{$meta->name}\$TIMESTAMPABLE_CLASSMETADATA", 
                $this->_configurations[$meta->name],
                null
            );
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
                
                $config = $this->getConfiguration($em, $entityClass);
                if (isset($config['onUpdate'])) {
                    $needChanges = true;
                    foreach ($config['onUpdate'] as $field) {
                        $meta->getReflectionProperty($field)
                            ->setValue($entity, new \DateTime('now'));
                    }
                }
                
                if (isset($config['onChange'])) {
                    $changeSet = $uow->getEntityChangeSet($entity);
                    foreach ($config['onChange'] as $options) {
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

            $config = $this->getConfiguration($em, $entityClass);
            if (isset($config['onUpdate'])) {
                foreach ($config['onUpdate'] as $field) {
                    $meta->getReflectionProperty($field)
                        ->setValue($entity, new \DateTime('now'));
                }
            }
            
            if (isset($config['onCreate'])) {
                foreach ($config['onCreate'] as $field) {
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