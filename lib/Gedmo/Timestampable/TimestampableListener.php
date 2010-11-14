<?php

namespace Gedmo\Timestampable;

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
 * @package Gedmo.Timestampable
 * @subpackage TimestampableListener
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TimestampableListener implements EventSubscriber
{   
    /**
     * The namespace of annotations for this extension
     */
    const ANNOTATION_NAMESPACE = 'gedmo';
    
    /**
     * Annotation field is timestampable
     */
    const ANNOTATION_TIMESTAMPABLE = 'Gedmo\Timestampable\Mapping\Timestampable';
    
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
            if (($cached = $cacheDriver->fetch("{$class}\$GEDMO_TIMESTAMPABLE_CLASSMETADATA")) !== false) {
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
            throw new \RuntimeException('TimestampableListener: update to latest ORM version, minimal RC1 from github');
        }
        $em = $eventArgs->getEntityManager();
        $cacheDriver = $em->getMetadataFactory()->getCacheDriver();      
        $meta = $eventArgs->getClassMetadata();
        
        $class = $meta->getReflectionClass();
        require_once __DIR__ . '/Mapping/Annotations.php';
        $reader = new AnnotationReader();
        $reader->setAnnotationNamespaceAlias(
            'Gedmo\Timestampable\Mapping\\',
            self::ANNOTATION_NAMESPACE
        );
    
        // property annotations
        foreach ($class->getProperties() as $property) {
            if ($meta->isMappedSuperclass && !$property->isPrivate() ||
                $meta->isInheritedField($property->name) ||
                $meta->isInheritedAssociation($property->name)
            ) {
                continue;
            }
            
            if ($timestampable = $reader->getPropertyAnnotation($property, self::ANNOTATION_TIMESTAMPABLE)) {
                $field = $property->getName();
                if (!$meta->hasField($field)) {
                    throw Exception::fieldMustBeMapped($field, $meta->name);
                }
                if (!$this->_isValidField($meta, $field)) {
                    throw Exception::notValidFieldType($field, $meta->name);
                }
                if (!in_array($timestampable->on, array('update', 'create', 'change'))) {
                    throw Exception::triggerTypeInvalid($field, $meta->name);
                }
                if ($timestampable->on == 'change') {
                    if (!isset($timestampable->field) || !isset($timestampable->value)) {
                        throw Exception::parametersMissing($field, $meta->name);
                    }
                    $field = array(
                        'field' => $field,
                        'trackedField' => $timestampable->field,
                        'value' => $timestampable->value 
                    );
                }
                $this->_configurations[$meta->name][$timestampable->on][] = $field;
            }
        }
        if ($cacheDriver && isset($this->_configurations[$meta->name])) {
            $cacheDriver->save(
                "{$meta->name}\$GEDMO_TIMESTAMPABLE_CLASSMETADATA", 
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
        
        $entityClass = get_class($entity);
        if ($config = $this->getConfiguration($em, $entityClass)) {
            $meta = $em->getClassMetadata($entityClass);

            $config = $this->getConfiguration($em, $entityClass);
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