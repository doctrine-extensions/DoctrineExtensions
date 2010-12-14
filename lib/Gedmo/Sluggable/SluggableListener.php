<?php

namespace Gedmo\Sluggable;

use Doctrine\Common\EventSubscriber,
    Doctrine\ORM\Events,
    Doctrine\ORM\Event\LifecycleEventArgs,
    Doctrine\ORM\Event\OnFlushEventArgs,
    Doctrine\ORM\Event\LoadClassMetadataEventArgs,
    Doctrine\ORM\EntityManager,
    Doctrine\ORM\Query,
    Doctrine\ORM\Mapping\ClassMetadata,
    Doctrine\ORM\Mapping\ClassMetadataInfo,
    Gedmo\Mapping\ExtensionMetadataFactory;

/**
 * The SluggableListener handles the generation of slugs
 * for entities which implements the Sluggable interface.
 * 
 * This behavior can inpact the performance of your application
 * since it does some additional calculations on persisted entities.
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @subpackage SluggableListener
 * @package Gedmo.Sluggable
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class SluggableListener implements EventSubscriber
{    
    /**
     * List of cached entity configurations
     *  
     * @var array
     */
    protected $_configurations = array();
    
    /**
     * List of entities which needs to be processed
     * after the insertion operations, because
     * query executions will be needed
     * 
     * @var array
     */
    protected $_pendingEntities = array();
    
    /**
     * ExtensionMetadataFactory used to read the extension
     * metadata
     * 
     * @var Gedmo\Mapping\ExtensionMetadataFactory
     */
    protected $_extensionMetadataFactory = null;
    
    /**
     * Specifies the list of events to listen
     * 
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            Events::prePersist,
            Events::postPersist,
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
            $cacheId = ExtensionMetadataFactory::getCacheId($class, __NAMESPACE__);
            if (($cached = $cacheDriver->fetch($cacheId)) !== false) {
                $this->_configurations[$class] = $cached;
                $config = $cached;
            }
        }
        return $config;
    }
    
    /**
     * Get metadata mapping reader
     * 
     * @param EntityManager $em
     * @return Gedmo\Mapping\MetadataReader
     */
    public function getExtensionMetadataFactory(EntityManager $em)
    {
        if (null === $this->_extensionMetadataFactory) {
            $this->_extensionMetadataFactory = new ExtensionMetadataFactory($em, __NAMESPACE__);
        }
        return $this->_extensionMetadataFactory;
    }
    
    /**
     * Scans the entities for Sluggable annotations
     * 
     * @param LoadClassMetadataEventArgs $eventArgs
     * @throws Sluggable\Exception if any mapping data is invalid
     * @throws RuntimeException if ORM version is old
     * @return void
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        $meta = $eventArgs->getClassMetadata();
        $em = $eventArgs->getEntityManager();
        $factory = $this->getExtensionMetadataFactory($em);
        $config = $factory->getExtensionMetadata($meta);
        if ($config) {
            $this->_configurations[$meta->name] = $config;
        }
    }
    
    /**
     * Checks for persisted entity to specify slug
     * 
     * @param LifecycleEventArgs $args
     * @return void
     */
    public function prePersist(LifecycleEventArgs $args)
    {
        $em = $args->getEntityManager();
        $entity = $args->getEntity();
        
        if ($config = $this->getConfiguration($em, get_class($entity))) {
            $this->_generateSlug($em, $entity, false);
        }
    }
    
    /**
     * Checks for inserted entities to update their slugs
     * 
     * @param LifecycleEventArgs $args
     * @return void
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        // there can be other entities being inserted because
        // unitofwork does inserts by class ordered chunks
        if (!$uow->hasPendingInsertions()) {
            while ($entity = array_shift($this->_pendingEntities)) {
                // we know that this slug must be unique and
                // it was preprocessed allready
                $config = $this->getConfiguration($em, get_class($entity));
                $slug = $this->_makeUniqueSlug($em, $entity);
                $uow->scheduleExtraUpdate($entity, array(
                    $config['slug'] => array(null, $slug)
                ));
            }
        }
    }
    
    /**
     * Generate slug on entities being updated during flush
     * if they require changing
     * 
     * @param OnFlushEventArgs $args
     * @return void
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();
        
        // we use onFlush and not preUpdate event to let other
        // event listeners be nested together
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($config = $this->getConfiguration($em, get_class($entity))) {
                if ($config['updatable']) {
                    $this->_generateSlug($em, $entity, $uow->getEntityChangeSet($entity));
                }
            }
        }
    }
    
    /**
     * Creates the slug for entity being flushed
     * 
     * @param EntityManager $em
     * @param object $entity
     * @param mixed $changeSet
     *      case array: the change set array
     *      case boolean(false): entity is not managed
     * @throws Sluggable\Exception if parameters are missing
     *      or invalid
     * @return void
     */
    protected function _generateSlug(EntityManager $em, $entity, $changeSet)
    {
        $entityClass = get_class($entity);
        $uow = $em->getUnitOfWork();
        $meta = $em->getClassMetadata($entityClass);
        $config = $this->getConfiguration($em, $entityClass);
        
        // collect the slug from fields
        $slug = '';
        $needToChangeSlug = false;
        foreach ($config['fields'] as $sluggableField) {
            if ($changeSet === false || isset($changeSet[$sluggableField])) {
                $needToChangeSlug = true;
            }
            $slug .= $meta->getReflectionProperty($sluggableField)->getValue($entity) . ' ';
        }
        // if slug is not changed, no need further processing
        if (!$needToChangeSlug) {
            return; // nothing to do
        }
        
        if (!strlen(trim($slug))) {
            throw Exception::slugIsEmpty();
        }
        
        // build the slug
        $slug = call_user_func_array(
            array('Gedmo\Sluggable\Util\Urlizer', 'urlize'), 
            array($slug, $config['separator'], $entity)
        );

        // stylize the slug
        switch ($config['style']) {
            case 'camel':
                $slug = preg_replace_callback(
                    '@^[a-z]|' . $config['separator'] . '[a-z]@smi', 
                    create_function('$m', 'return strtoupper($m[0]);'), 
                    $slug
                );
                break;
                
            default:
                // leave it as is
                break;
        }
        
        // cut slug if exceeded in length
        $mapping = $meta->getFieldMapping($config['slug']);
        if (strlen($slug) > $mapping['length']) {
            $slug = substr($slug, 0, $mapping['length']);
        }

        // make unique slug if requested
        if ($config['unique'] && !$uow->hasPendingInsertions()) {
            // set the slug for further processing
            $meta->getReflectionProperty($config['slug'])->setValue($entity, $slug);
            $slug = $this->_makeUniqueSlug($em, $entity);
        }
        // set the final slug
        $meta->getReflectionProperty($config['slug'])->setValue($entity, $slug);
        // recompute changeset if entity is managed
        if ($changeSet !== false) {
            $uow->recomputeSingleEntityChangeSet($meta, $entity);
        } elseif ($config['unique'] && $uow->hasPendingInsertions()) {
            // @todo: make support for unique field metadata on concurrent operations
            if ($meta->isUniqueField($config['slug'])) {
                throw Exception::slugFieldIsUnique($config['slug']);
            }
            $this->_pendingEntities[] = $entity;
        }
    }
    
    /**
     * Generates the unique slug
     * 
     * @param EntityManager $em
     * @param object $entity
     * @throws Sluggable\Exception if unit of work has pending inserts
     *      to avoid infinite loop
     * @return string - unique slug
     */
    protected function _makeUniqueSlug(EntityManager $em, $entity)
    {        
        $entityClass = get_class($entity);
        $meta = $em->getClassMetadata($entityClass);
        $config = $this->getConfiguration($em, $entityClass);
        $preferedSlug = $meta->getReflectionProperty($config['slug'])->getValue($entity);
        
        // @todo: optimize
        // search for similar slug
        $qb = $em->createQueryBuilder();
        $qb->select('rec.' . $config['slug'])
            ->from($entityClass, 'rec')
            ->add('where', $qb->expr()->like(
                'rec.' . $config['slug'], 
                $qb->expr()->literal($preferedSlug . '%'))
            );
        // include identifiers
        $entityIdentifiers = $meta->getIdentifierValues($entity);
        foreach ($entityIdentifiers as $field => $value) {
            if (strlen($value)) {
                $qb->add('where', 'rec.' . $field . ' <> ' . $value);
            }
        }
        $q = $qb->getQuery();
        $q->setHydrationMode(Query::HYDRATE_ARRAY);
        $result = $q->execute();
        
        if (is_array($result) && count($result)) {
            $generatedSlug = $preferedSlug;
            $sameSlugs = array();
            foreach ($result as $list) {
                $sameSlugs[] = $list['slug'];
            }

            $i = 0;
            if (preg_match("@{$config['separator']}\d+$@sm", $generatedSlug, $m)) {
                $i = abs(intval($m[0]));
            }
            while (in_array($generatedSlug, $sameSlugs)) {
                $generatedSlug = $preferedSlug . $config['separator'] . ++$i;
            }
            
            $mapping = $meta->getFieldMapping($config['slug']);
            $needRecursion = false;
            if (strlen($generatedSlug) > $mapping['length']) {
                $needRecursion = true;
                $generatedSlug = substr(
                    $generatedSlug, 
                    0, 
                    $mapping['length'] - (strlen($i) + strlen($config['separator']))
                );
                $generatedSlug .= $config['separator'] . $i;
            }
            
            $meta->getReflectionProperty($config['slug'])->setValue($entity, $generatedSlug);
            if ($needRecursion) {
                $generatedSlug = $this->_makeUniqueSlug($em, $entity);
            }
            $preferedSlug = $generatedSlug;
        }
        return $preferedSlug;
    }
}