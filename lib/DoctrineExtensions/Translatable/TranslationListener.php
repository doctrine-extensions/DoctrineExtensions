<?php

namespace DoctrineExtensions\Translatable;

use Doctrine\Common\EventSubscriber,
    Doctrine\ORM\Events,
    Doctrine\ORM\Event\LifecycleEventArgs,
    Doctrine\ORM\Event\OnFlushEventArgs,
    Doctrine\ORM\EntityManager,
    Doctrine\ORM\Query,
    DoctrineExtensions\Translatable\Entity\Translation;

/**
 * The translation listener handles the generation and
 * loading of translations for entities which implements
 * the Translatable interface.
 * 
 * This behavior can inpact the performance of your application
 * since it does an additional query for each field to translate.
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package DoctrineExtensions.Translatable
 * @subpackage TranslationListener
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TranslationListener implements EventSubscriber
{
	/**
	 * The translation entity class used to store the translations
	 */
	const TRANSLATION_ENTITY_CLASS = 'DoctrineExtensions\Translatable\Entity\Translation';
	
	/**
	 * Default locale which is set on this listener.
	 * If Entity being translated has locale defined it
	 * will override this one
	 *  
	 * @var string
	 */
	protected $_locale = 'en_us';
	
	/**
	 * List of translations which do not have the foreign
	 * key generated yet - MySQL case. These translations
	 * will be updated with new keys on postPersist event
	 * 
	 * @var array
	 */
	protected $_pendingTranslationInserts = array();

	/**
	 * Translations which should be inserted during
	 * update must be sheduled for later persisting
	 * to avoid query while insert is pending
	 * 
	 * @var array
	 */
	protected $_pendingTranslationUpdates = array();
	
	/**
	 * Default locale, this changes behavior
     * to not update the original record field if locale
     * which is used for updating is not default. This
     * will load the default translation in other locales
     * if record is not translated yet
	 * 
	 * @var string
	 */
	private $_defaultLocale = '';
	
	/**
	 * Specifies the list of events to listen
	 * 
	 * @return array
	 */
    public function getSubscribedEvents()
    {
        return array(
            Events::postLoad,
            Events::postPersist,
            Events::onFlush
        );
    }
	
    /**
     * Set the locale to use for translation listener
     * 
     * @param string $locale
     * @return void
     */
	public function setTranslatableLocale($locale)
	{
		$this->_locale = $locale;
	}
	
	/**
	 * Gets the locale to use for translation. Loads entity
	 * defined locale first..
	 * 
	 * @param Translatable $entity
	 * @return string
	 */
	public function getTranslatableLocale(Translatable $entity)
	{
		return $entity->getTranslatableLocale() ?: $this->_locale;
	}
    
	/**
	 * Sets the default locale, this changes behavior
	 * to not update the original record field if locale
	 * which is used for updating is not default
	 * 
	 * @param string $locale
	 * @return void
	 */
	public function setDefaultLocale($locale)
	{
		$this->_defaultLocale = $locale;
	}
	
	/**
	 * Looks for translatable entities being inserted or updated
	 * for further processing
	 * 
	 * @param OnFlushEventArgs $args
	 * @return void
	 */
    public function onFlush(OnFlushEventArgs $args)
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();
        // check all scheduled inserts for Translatable entities
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof Translatable && count($entity->getTranslatableFields())) {
                $this->_handleTranslatableEntityUpdate($em, $entity, true);
            }
        }
        // check all scheduled updates for Translatable entities
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof Translatable && count($entity->getTranslatableFields())) {
                $this->_handleTranslatableEntityUpdate($em, $entity, false);
            }
        }
        
        // all translations which should have been inserted are processed now
        // this prevents new pending insertions during sheduled updates process
        $translationMetadata = $em->getClassMetadata(self::TRANSLATION_ENTITY_CLASS);
        foreach ($this->_pendingTranslationUpdates as $translation) {
        	$em->persist($translation);
            $uow->computeChangeSet($translationMetadata, $translation);
        }
    }
    
    /**
     * Checks for inserted entities to update their translation
     * foreign keys
     * 
     * @param LifecycleEventArgs $args
     * @return void
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $em = $args->getEntityManager();
        $entity = $args->getEntity();
        // check if entity is Translatable and without foreign key
        if ($entity instanceof Translatable && count($this->_pendingTranslationInserts)) {
        	$oid = spl_object_hash($entity);
        	if (array_key_exists($oid, $this->_pendingTranslationInserts)) {
                // load the pending translations without key
        		$translations = $this->_pendingTranslationInserts[$oid];
        		foreach ($translations as $translation) {
	                // schedule an extra update for the foreign key
	                $uow = $em->getUnitOfWork();
	                $uow->scheduleExtraUpdate($translation, array(
	                    'foreignKey' => array(null, $entity->getId())
	                ));
        		}
            }
        }
    }
    
    /**
     * After entity is loaded, listener updates the translations
     * by currently used locale
     * 
     * @param LifecycleEventArgs $args
     * @throws Translatable\Exception if locale is not valid
     * @return void
     */
    public function postLoad(LifecycleEventArgs $args)
    {
    	$em = $args->getEntityManager();
    	$entity = $args->getEntity();

    	if ($entity instanceof Translatable && count($entity->getTranslatableFields())) {
    		$locale = strtolower($this->getTranslatableLocale($entity));
	    	$this->_validateLocale($locale);
            
	    	// load translated content for all translatable fields
            foreach ($entity->getTranslatableFields() as $field) {
            	$content = $this->_findTranslation(
            	    $em,
            	    $entity->getId(),
            	    get_class($entity),
                    $locale,
            	    $field,
            	    true
            	);
            	// update translation only if it has it
            	if (strlen($content)) {
            		$setter = 'set' . ucfirst($field);
            		$entity->{$setter}($content);
            	}
            }	
    	}
    }
    
    /**
     * Creates the translation for entity being flushed
     * 
     * @param EntityManager $em
     * @param Translatable $entity
     * @param boolean $isInsert
     * @throws Translatable\Exception if locale is not valid, or
     *      primary key is composite, missing or invalid
     * @return void
     */
    protected function _handleTranslatableEntityUpdate(EntityManager $em, Translatable $entity, $isInsert)
    {
    	$entityClass = get_class($entity);
    	// no need cache, metadata is loaded only once in MetadataFactoryClass
        $translationMetadata = $em->getClassMetadata(self::TRANSLATION_ENTITY_CLASS);
        $entityClassMetadata = $em->getClassMetadata($entityClass);
        
        // check for the availability of the primary key
        $entityId = $entityClassMetadata->getIdentifierValues($entity);
        if (count($entityId) == 1 && current($entityId)) {
            $entityId = current($entityId);
        } elseif ($isInsert) {
            $entityId = null;
        } else {
            throw Exception::singleIdentifierRequired($entityClass);
        }
        
        // @todo: add support for string type identifier also 
        if (!is_int($entityId) && !is_null($entityId)) {
        	throw Exception::invalidIdentifierType($entityId);
        }
        
        // load the currently used locale
        $locale = strtolower($this->getTranslatableLocale($entity));
        $this->_validateLocale($locale);

        $uow = $em->getUnitOfWork();
        $translatableFields = $entity->getTranslatableFields();
        foreach ($translatableFields as $field) {
        	$translation = null;
        	// check if translation allready is created
        	if (!$isInsert) {
                $translation = $this->_findTranslation(
                    $em,
                    $entityId,
                    $entityClass,
                    $locale,
                    $field
                );
        	}
            // create new translation
            $scheduleUpdate = false;
            if (!$translation) {
                $translation = new Translation;
                $translation->setLocale($locale);
	            $translation->setField($field);
	            $translation->setEntity($entityClass);
	            $translation->setForeignKey($entityId);
	            $scheduleUpdate = !$isInsert;
            }
            
            // set the translated field, take value using getter
            $getter = 'get' . ucfirst($field);
            $translation->setContent($entity->{$getter}());
            
            if ($scheduleUpdate) {
                // need to shedule new Translation insert to avoid query on pending insert
                $this->_pendingTranslationUpdates[] = $translation;
            } else {
            	// persist and compute change set for translation
                $em->persist($translation);
                $uow->computeChangeSet($translationMetadata, $translation);
            }
            // if we do not have the primary key yet available
            // keep this translation in memory for later update
            if ($isInsert && is_null($entityId)) {
            	$this->_pendingTranslationInserts[spl_object_hash($entity)][$field] = $translation;
            }
        }
        // check if we have default translation and need to reset the translation
        if (!$isInsert && strlen($this->_defaultLocale)) {
        	$this->_validateLocale($this->_defaultLocale);
        	$changeSet = $uow->getEntityChangeSet($entity);
        	$needsUpdate = false;
        	foreach ($changeSet as $field => $changes) {
        		if (in_array($field, $translatableFields)) {
        			if ($locale != $this->_defaultLocale && strlen($changes[0])) {
        				$setter = 'set' . ucfirst($field);
        				$entity->{$setter}($changes[0]);
        				$needsUpdate = true;
        			}
        		}
        	}
        	if ($needsUpdate) {
        		$uow->recomputeSingleEntityChangeSet($entityClassMetadata, $entity);
        	}
        }
    }
    
    /**
     * Search for existing translation record or
     * it`s field translation only
     * 
     * @param EntityManager $em
     * @param mixed $entityId
     * @param string $entityClass
     * @param string $locale
     * @param string $field
     * @param boolean $contentOnly - true if field translation only
     * @throws Translatable\Exception if unit of work has pending inserts
     *      to avoid infinite loop
     * @return mixed - null if nothing is found, Translation otherwise
     */
    protected function _findTranslation(EntityManager $em, $entityId, $entityClass, $locale, $field, $contentOnly = false)
    {
    	// @TODO: cannot use query if doctrine has pending inserts
    	if ($em->getUnitOfWork()->hasPendingInsertions()) {
    		throw Exception::pendingInserts();
    	}
    	
        $qb = $em->createQueryBuilder();
        $qb->select('trans')
            ->from(self::TRANSLATION_ENTITY_CLASS, 'trans')
            ->where(
                'trans.foreignKey = :entityId',
                'trans.locale = :locale',
                'trans.field = :field',
                'trans.entity = :entityClass'
            );
        $q = $qb->getQuery();
        $result = $q->execute(
            compact('field', 'locale', 'entityId', 'entityClass'),
            $contentOnly ? Query::HYDRATE_ARRAY : Query::HYDRATE_OBJECT
        );
        
        if ($result && is_array($result) && count($result)) {
            $result = array_shift($result);
            if ($contentOnly) {
            	$result = $result['content'];
            }
            return $result;
        }
        return null;
    }
    
    /**
     * Validates the given locale
     * 
     * @param string $locale - locale to validate
     * @throws Translatable\Exception if locale is not valid
     * @return void
     */
    protected function _validateLocale($locale)
    {
    	if (!strlen($locale)) {
    		throw Exception::undefinedLocale();
    	}
    }
}