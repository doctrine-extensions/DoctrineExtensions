<?php

namespace Gedmo\Translatable;

use Doctrine\Common\EventSubscriber,
    Doctrine\ORM\Events,
    Doctrine\ORM\Event\LifecycleEventArgs,
    Doctrine\ORM\Event\OnFlushEventArgs,
    Doctrine\ORM\Event\LoadClassMetadataEventArgs,
    Doctrine\ORM\EntityManager,
    Doctrine\ORM\Query,
    Doctrine\ORM\Mapping\ClassMetadata,
    Doctrine\ORM\Mapping\ClassMetadataInfo,
    Gedmo\Translatable\Entity\Translation,
    Gedmo\Mapping\ExtensionMetadataFactory;

/**
 * The translation listener handles the generation and
 * loading of translations for entities which implements
 * the Translatable interface.
 * 
 * This behavior can inpact the performance of your application
 * since it does an additional query for each field to translate.
 * 
 * Nevertheless the annotation metadata is properly cached and
 * it is not a big overhead to lookup all entity annotations since
 * the caching is activated for metadata
 * 
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Translatable
 * @subpackage TranslationListener
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TranslationListener implements EventSubscriber
{    
    /**
     * The translation entity class used to store the translations
     * 
     * @var string
     */
    protected $_defaultTranslationEntity = 'Gedmo\Translatable\Entity\Translation';
    
    /**
     * Locale which is set on this listener.
     * If Entity being translated has locale defined it
     * will override this one
     *  
     * @var string
     */
    protected $_locale = 'en_us';
    
    /**
     * ExtensionMetadataFactory used to read the extension
     * metadata
     * 
     * @var Gedmo\Mapping\ExtensionMetadataFactory
     */
    protected $_extensionMetadataFactory = null;
    
    /**
     * List of metadata configurations for Translatable
     * classes, from annotations
     * 
     * @var array
     */
    protected $_configurations = array();
    
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
     * List of translations which do not have the foreign
     * key generated yet - MySQL case. These translations
     * will be updated with new keys on postPersist event
     * 
     * @var array
     */
    private $_pendingTranslationInserts = array();

    /**
     * Translations which should be inserted during
     * update must be sheduled for later persisting
     * to avoid query while insert is pending
     * 
     * @var array
     */
    private $_pendingTranslationUpdates = array();
    
    /**
     * Entities which are sheduled for delete and
     * cannot delete its translations now because
     * inserts are pending. They will be processed
     * after inserts are done
     * 
     * @var array
     */
    private $_pendingEntityDeletions = array();
    
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
     * Get the entity translation class to be used
     * for the entity $class
     * 
     * @param string $class
     * @return string
     */
    public function getTranslationClass($class)
    {
        return isset($this->_configurations[$class]['translationClass']) ?
            $this->_configurations[$class]['translationClass'] : 
            $this->_defaultTranslationEntity;
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
     * @param object $entity
     * @param ClassMetadata $meta
     * @return string
     */
    public function getTranslatableLocale($entity, ClassMetadata $meta)
    {
        $locale = $this->_locale;
        if (isset($this->_configurations[$meta->name]['locale'])) {
            $class = $meta->getReflectionClass();
            $reflectionProperty = $class->getProperty($this->_configurations[$meta->name]['locale']);
            if (!$reflectionProperty) {
                throw Exception::entityMissingLocaleProperty(
                    $this->_configurations[$meta->name]['locale'],
                    $meta->name
                );
            }
            $reflectionProperty->setAccessible(true);
            $value = $reflectionProperty->getValue($entity);
            if (is_string($value) && strlen($value)) {
                $locale = $value;
            }
        }
        return $locale;
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
     * Scans the entities for Translatable extension metadata
     * 
     * @param LoadClassMetadataEventArgs $eventArgs
     * @throws RuntimeException if metadata driver is invalid
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
            $config = $this->getConfiguration($em, get_class($entity));
            if (isset($config['fields'])) {
                $this->_handleTranslatableEntityUpdate($em, $entity, true);
            }
        }
        // check all scheduled updates for Translatable entities
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $config = $this->getConfiguration($em, get_class($entity));
            if (isset($config['fields'])) {
                $this->_handleTranslatableEntityUpdate($em, $entity, false);
            }
        }
        // check scheduled deletions for Translatable entities
        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            $entityClass = get_class($entity);
            $config = $this->getConfiguration($em, $entityClass);
            if (isset($config['fields'])) {
                $meta = $em->getClassMetadata($entityClass);
                $identifierField = $meta->getSingleIdentifierFieldName();
                $entityId = $meta->getReflectionProperty($identifierField)->getValue($entity);
                
                $transClass = $this->getTranslationClass($entityClass);
                if ($uow->hasPendingInsertions()) {
                    $this->_pendingEntityDeletions[$transClass] = $entityId;
                } else {
                    $this->_removeAssociatedTranslations($em, $entityId, $transClass);
                }
            }
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
        $uow = $em->getUnitOfWork();
        $entityClass = get_class($entity);
        // check if entity is tracked by translatable and without foreign key
        if (array_key_exists($entityClass, $this->_configurations) && count($this->_pendingTranslationInserts)) {
            $oid = spl_object_hash($entity);
            
            $meta = $em->getClassMetadata($entityClass);
            // there should be single identifier
            $identifierField = $meta->getSingleIdentifierFieldName();
            $translationMeta = $em->getClassMetadata($this->getTranslationClass($entityClass));
            if (array_key_exists($oid, $this->_pendingTranslationInserts)) {
                // load the pending translations without key
                $translations = $this->_pendingTranslationInserts[$oid];
                foreach ($translations as $translation) {
                    $translationMeta->getReflectionProperty('foreignKey')->setValue(
                        $translation,
                        $meta->getReflectionProperty($identifierField)->getValue($entity)
                    );
                    $this->_insertTranslationRecord($em, $translation);
                }
            }
        }

        if (!$uow->hasPendingInsertions()) {
            // all translations which should have been inserted are processed now
            // this prevents new pending insertions during sheduled updates process
            foreach ($this->_pendingTranslationUpdates as $candidate) {
                $meta = $em->getClassMetadata(get_class($candidate));
                $translation = $this->_findTranslation(
                    $em,
                    $meta->getReflectionProperty('foreignKey')->getValue($candidate),
                    $meta->getReflectionProperty('entity')->getValue($candidate),
                    $meta->getReflectionProperty('locale')->getValue($candidate),
                    $meta->getReflectionProperty('field')->getValue($candidate)
                );
                if (!$translation) {
                    $this->_insertTranslationRecord($em, $candidate);
                } else {
                    $uow->scheduleExtraUpdate($translation, array(
                        'content' => array(
                            null, 
                            $meta->getReflectionProperty('content')->getValue($candidate)
                        )
                    ));
                }
            }
            // run all pending deletions
            foreach ($this->_pendingEntityDeletions as $transClass => $id) {
                $this->_removeAssociatedTranslations($em, $id, $transClass);
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
        $entityClass = get_class($entity);
        $config = $this->getConfiguration($em, $entityClass);

        if (isset($config['fields'])) {
            $meta = $em->getClassMetadata($entityClass);
            $locale = strtolower($this->getTranslatableLocale($entity, $meta));
            $this->_validateLocale($locale);
            
            // there should be single identifier
            $identifierField = $meta->getSingleIdentifierFieldName();
            // load translated content for all translatable fields
            $translationClass = $this->getTranslationClass($entityClass);
            $entityId = $meta->getReflectionProperty($identifierField)->getValue($entity);
            // construct query
            $dql = 'SELECT t.content, t.field FROM ' . $translationClass . ' t';
            $dql .= ' WHERE t.foreignKey = :entityId';
            $dql .= ' AND t.locale = :locale';
            $dql .= ' AND t.entity = :entityClass';
            // fetch results
            $q = $em->createQuery($dql);
            $q->setParameters(compact('entityId', 'locale', 'entityClass'));
            $result = $q->getArrayResult();
            // translate entity translatable properties
            foreach ($config['fields'] as $field) {
                foreach ((array)$result as $entry) {
                    if ($entry['field'] == $field && strlen($entry['content'])) {
                        // update translation only if it has it
                        $meta->getReflectionProperty($field)
                            ->setValue($entity, $entry['content']);
                    }
                }
            }    
        }
    }
    
    /**
     * Creates the translation for entity being flushed
     * 
     * @param EntityManager $em
     * @param object $entity
     * @param boolean $isInsert
     * @throws Translatable\Exception if locale is not valid, or
     *      primary key is composite, missing or invalid
     * @return void
     */
    protected function _handleTranslatableEntityUpdate(EntityManager $em, $entity, $isInsert)
    {
        $entityClass = get_class($entity);
        // no need cache, metadata is loaded only once in MetadataFactoryClass
        $translationMetadata = $em->getClassMetadata($this->getTranslationClass($entityClass));
        $meta = $em->getClassMetadata($entityClass);
        
        // check for the availability of the primary key
        $entityId = $meta->getIdentifierValues($entity);
        if (count($entityId) == 1 && current($entityId)) {
            $entityId = current($entityId);
        } elseif ($isInsert) {
            $entityId = null;
        } else {
            throw Exception::singleIdentifierRequired($entityClass);
        }
        
        // load the currently used locale
        $locale = strtolower($this->getTranslatableLocale($entity, $meta));
        $this->_validateLocale($locale);

        $uow = $em->getUnitOfWork();
        $translationClass = $this->getTranslationClass($entityClass);
        $config = $this->getConfiguration($em, $entityClass);
        $translatableFields = $config['fields'];
        foreach ($translatableFields as $field) {
            $translation = null;
            $scheduleUpdate = false;
            // check if translation allready is created
            if (!$isInsert && !$uow->hasPendingInsertions()) {
                $translation = $this->_findTranslation(
                    $em,
                    $entityId,
                    $entityClass,
                    $locale,
                    $field
                );
            }
            // create new translation
            if (!$translation) {
                $translation = new $translationClass();
                $translationMetadata->getReflectionProperty('locale')
                    ->setValue($translation, $locale);
                $translationMetadata->getReflectionProperty('field')
                    ->setValue($translation, $field);
                $translationMetadata->getReflectionProperty('entity')
                    ->setValue($translation, $entityClass);
                $translationMetadata->getReflectionProperty('foreignKey')
                    ->setValue($translation, $entityId);
                $scheduleUpdate = !$isInsert;
            }
            
            // set the translated field, take value using reflection
            $translationMetadata->getReflectionProperty('content')
                    ->setValue($translation, $meta->getReflectionProperty($field)->getValue($entity));
            if ($scheduleUpdate && $uow->hasPendingInsertions()) {
                // need to shedule new Translation insert to avoid query on pending insert
                $this->_pendingTranslationUpdates[] = $translation;
            } elseif ($isInsert && is_null($entityId)) {
                // if we do not have the primary key yet available
                // keep this translation in memory to insert it later with foreign key
                $this->_pendingTranslationInserts[spl_object_hash($entity)][$field] = $translation;
            } else {
                // persist and compute change set for translation
                $em->persist($translation);
                $uow->computeChangeSet($translationMetadata, $translation);
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
                        $meta->getReflectionProperty($field)->setValue($entity, $changes[0]);
                        $needsUpdate = true;
                    }
                }
            }
            if ($needsUpdate) {
                $uow->recomputeSingleEntityChangeSet($meta, $entity);
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
     * @throws Translatable\Exception if unit of work has pending inserts
     *      to avoid infinite loop
     * @return mixed - null if nothing is found, Translation otherwise
     */
    protected function _findTranslation(EntityManager $em, $entityId, $entityClass, $locale, $field)
    {
        if ($em->getUnitOfWork()->hasPendingInsertions()) {
            throw Exception::pendingInserts();
        }
        
        $qb = $em->createQueryBuilder();
        $qb->select('trans')
            ->from($this->getTranslationClass($entityClass), 'trans')
            ->where(
                'trans.foreignKey = :entityId',
                'trans.locale = :locale',
                'trans.field = :field',
                'trans.entity = :entityClass'
            );
        $q = $qb->getQuery();
        $result = $q->execute(
            compact('field', 'locale', 'entityId', 'entityClass'),
            Query::HYDRATE_OBJECT
        );
        
        if ($result) {
            return array_shift($result);
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
        if (!is_string($locale) || !strlen($locale)) {
            throw Exception::undefinedLocale();
        }
    }
    
    /**
     * Removes all associated translations
     * 
     * @param EntityManager $em
     * @param mixed $entityId
     * @param string $translationClass
     * @return integer
     */
    protected function _removeAssociatedTranslations(EntityManager $em, $entityId, $translationClass)
    {
        $dql = 'DELETE ' . $translationClass . ' trans';
        $dql .= ' WHERE trans.foreignKey = :entityId';
            
        $q = $em->createQuery($dql);
        $q->setParameters(compact('entityId'));
        return $q->getSingleScalarResult();
    }
    
    /**
     * Does the standard insert. Which is not managed by entity manager.
     * 
     * @param EntityManager $em
     * @param object $translation
     * @throws Translatable\Exception if insert fails
     * @return void
     */
    private function _insertTranslationRecord(EntityManager $em, $translation)
    {
        $translationMetadata = $em->getClassMetadata(get_class($translation));        
        $data = array();

        foreach ($translationMetadata->getReflectionProperties() as $fieldName => $reflProp) {
            if (!$translationMetadata->isIdentifier($fieldName)) {
                $data[$translationMetadata->getColumnName($fieldName)] = $reflProp->getValue($translation);
            }
        }
        
        $table = $translationMetadata->getTableName();
        if (!$em->getConnection()->insert($table, $data)) {
            throw Exception::failedToInsert();
        }
    }
}