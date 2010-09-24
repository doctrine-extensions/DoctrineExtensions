<?php

namespace DoctrineExtensions\Sluggable;

use Doctrine\Common\EventSubscriber,
    Doctrine\ORM\Events,
    Doctrine\ORM\Event\LifecycleEventArgs,
    Doctrine\ORM\Event\OnFlushEventArgs,
    Doctrine\ORM\EntityManager,
    Doctrine\ORM\Query;

/**
 * The SluggableListener handles the generation of slugs
 * for entities which implements the Sluggable interface.
 * 
 * This behavior can inpact the performance of your application
 * since it does some additional calculations on persisted entities.
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @subpackage SluggableListener
 * @package DoctrineExtensions.Sluggable
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
	 * Specifies the list of events to listen
	 * 
	 * @return array
	 */
    public function getSubscribedEvents()
    {
        return array(
            Events::prePersist,
            Events::postPersist,
            Events::onFlush
        );
    }
		
	/**
	 * Get the configuration for entity
	 * 
	 * @param Sluggable $entity
	 * @return Configuration
	 */
	public function getConfiguration(Sluggable $entity)
	{
		$entityClass = get_class($entity);
		if (!isset($this->_configurations[$entityClass])) {
			$this->_configurations[$entityClass] = $entity->getSluggableConfiguration();
		}
		return $this->_configurations[$entityClass];
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

        if ($entity instanceof Sluggable) {
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
                $config = $this->getConfiguration($entity);
                $slugField = $config->getSlugField();
                $slug = $this->_makeUniqueSlug($em, $entity);
                $uow->scheduleExtraUpdate($entity, array(
                    $slugField => array(null, $slug)
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
            if ($entity instanceof Sluggable) {
            	$config = $this->getConfiguration($entity);
            	if ($config->isUpdatable()) {
                    $this->_generateSlug($em, $entity, $uow->getEntityChangeSet($entity));
            	}
            }
        }
    }
    
    /**
     * Creates the slug for entity being flushed
     * 
     * @param EntityManager $em
     * @param Sluggable $entity
     * @param mixed $changeSet
     *      case array: the change set array
     *      case boolean(false): entity is not managed
     * @throws Sluggable\Exception if parameters are missing
     *      or invalid
     * @return void
     */
    protected function _generateSlug(EntityManager $em, Sluggable $entity, $changeSet)
    {
    	$entityClass = get_class($entity);
    	$uow = $em->getUnitOfWork();
        $entityClassMetadata = $em->getClassMetadata($entityClass);
        $config = $this->getConfiguration($entity);
        
        // check if slug field exists
        $slugField = $config->getSlugField();
        if (!$entityClassMetadata->hasField($slugField)) {
        	throw Exception::cannotFindSlugField($slugField);
        }
        
        // check if slug metadata is valid
        $preferedLength = $config->getLength();
        $mapping = $entityClassMetadata->getFieldMapping($slugField);
        if ($mapping['type'] != 'string') {
        	throw Exception::invalidSlugType($mapping['type']);
        } elseif ($preferedLength > $mapping['length']) {
        	throw Exception::invalidSlugLength($mapping['length'], $preferedLength);
        }
        
        // check if there are fields to be slugged
        $sluggableFields = $config->getSluggableFields();
        if (!count($sluggableFields)) {
            throw Exception::noFieldsToSlug();
        }
        
        // collect the slug from fields
        $slug = '';
        $needToChangeSlug = false;
        foreach ($sluggableFields as $sluggableField) {
        	if (!$entityClassMetadata->hasField($sluggableField)) {
        		throw Exception::cannotFindFieldToSlug($sluggableField);
        	}
        	if ($changeSet === false || isset($changeSet[$sluggableField])) {
        		$needToChangeSlug = true;
        	}
        	$slug .= $entityClassMetadata->getReflectionProperty($sluggableField)->getValue($entity) . ' ';
        }
        // if slug is not changed, no need further processing
        if (!$needToChangeSlug) {
        	return; // nothing to do
        }
        
        if (!strlen(trim($slug))) {
            throw Exception::slugIsEmpty();
        }
        
        // build the slug
        $builder = $config->getSlugBuilder();
        $separator = $config->getSeparator();
        $slug = call_user_func_array(
            $builder, 
            array($slug, $separator, $entity)
        );

        // stylize the slug
        $style = $config->getSlugStyle();
        switch ($style) {
        	case Configuration::SLUG_STYLE_CAMEL:
        		$slug = preg_replace_callback(
                    '@^[a-z]|' . $separator . '[a-z]@smi', 
                    create_function('$m', 'return strtoupper($m[0]);'), 
                    $slug
                );
                break;
                
        	default:
        	    // leave it as is
        	    break;
        }
        
        // cut slug if exceeded in length
        if ($preferedLength && strlen($slug) > $preferedLength) {
            $slug = substr($slug, 0, $preferedLength);
        }

        // make unique slug if requested
        if ($config->isUnique() && !$uow->hasPendingInsertions()) {
        	// set the slug for further processing
        	$entityClassMetadata->getReflectionProperty($slugField)->setValue($entity, $slug);
            $slug = $this->_makeUniqueSlug($em, $entity);
        }
        // set the final slug
        $entityClassMetadata->getReflectionProperty($slugField)->setValue($entity, $slug);
        // recompute changeset if entity is managed
        if ($changeSet !== false) {
            $uow->recomputeSingleEntityChangeSet($entityClassMetadata, $entity);
        } elseif ($config->isUnique() && $uow->hasPendingInsertions()) {
            // @todo: make support for unique field metadata
            if ($entityClassMetadata->isUniqueField($slugField)) {
                throw Exception::slugFieldIsUnique($slugField);
            }
            $this->_pendingEntities[] = $entity;
        }
    }
    
    /**
     * Generates the unique slug
     * 
     * @param EntityManager $em
     * @param Sluggable $entity
     * @throws Sluggable\Exception if unit of work has pending inserts
     *      to avoid infinite loop
     * @return string - unique slug
     */
    private function _makeUniqueSlug(EntityManager $em, Sluggable $entity)
    {
        if ($em->getUnitOfWork()->hasPendingInsertions()) {
        	throw Exception::pendingInserts();
        }
    	
        $entityClass = get_class($entity);
        $entityClassMetadata = $em->getClassMetadata($entityClass);
        
    	$config = $this->getConfiguration($entity);
    	$slugField = $config->getSlugField();
        $preferedSlug = $entityClassMetadata->getReflectionProperty($slugField)->getValue($entity);
        
        // @todo: optimize
        // search for similar slug
        $qb = $em->createQueryBuilder();
        $qb->select('rec.' . $slugField)
            ->from($entityClass, 'rec')
            ->where($qb->expr()->like(
                'rec.' . $slugField, 
                $qb->expr()->literal($preferedSlug . '%'))
            );
        // include identifiers
        $entityIdentifiers = $entityClassMetadata->getIdentifierValues($entity);
        foreach ($entityIdentifiers as $field => $value) {
        	$qb->where('rec.' . $field . ' <> ' . $value);
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

	        $separator = $config->getSeparator();
	        $i = 0;
	        if (preg_match("@{$separator}\d+$@sm", $generatedSlug, $m)) {
	        	$i = abs(intval($m[0]));
	        }
            while (in_array($generatedSlug, $sameSlugs)) {
                $generatedSlug = $preferedSlug . $separator . ++$i;
            }
            
            $preferedLength = $config->getLength();
            $needRecursion = false;
            if ($preferedLength && strlen($generatedSlug) > $preferedLength) {
            	$needRecursion = true;
                $generatedSlug = substr(
                    $generatedSlug, 
                    0, 
                    $preferedLength - (strlen($i) + strlen($separator))
                );
                $generatedSlug .= $separator . $i;
            }
            
            $entityClassMetadata->getReflectionProperty($slugField)->setValue($entity, $generatedSlug);
            if ($needRecursion) {
            	$generatedSlug = $this->_makeUniqueSlug($em, $entity);
            }
            $preferedSlug = $generatedSlug;
        }
        return $preferedSlug;
    }
}