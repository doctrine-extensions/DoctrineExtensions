<?php

namespace Gedmo\Translatable;

use Doctrine\Common\EventArgs,
    Gedmo\Mapping\MappedEventSubscriber;

/**
 * The AbstractTranslationListener is an abstract class
 * of translation listener in order to support diferent
 * object managers.
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Translatable
 * @subpackage AbstractTranslationListener
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
abstract class AbstractTranslationListener extends MappedEventSubscriber
{
    /**
     * Locale which is set on this listener.
     * If Entity being translated has locale defined it
     * will override this one
     *
     * @var string
     */
    protected $locale = 'en_us';

    /**
     * Default locale, this changes behavior
     * to not update the original record field if locale
     * which is used for updating is not default. This
     * will load the default translation in other locales
     * if record is not translated yet
     *
     * @var string
     */
    private $defaultLocale = '';

    /**
     * If this is set to false, when if entity does
     * not have a translation for requested locale
     * it will show a blank value
     *
     * @var boolean
     */
    private $translationFallback = true;

    /**
     * List of translations which do not have the foreign
     * key generated yet - MySQL case. These translations
     * will be updated with new keys on postPersist event
     *
     * @var array
     */
    private $pendingTranslationInserts = array();

    /**
     * Mapps additional metadata
     *
     * @param EventArgs $eventArgs
     * @return void
     */
    public function loadClassMetadata(EventArgs $eventArgs)
    {
        $this->loadMetadataForObjectClass($this->getObjectManager($eventArgs), $eventArgs->getClassMetadata());
    }

    /**
     * Enable or disable translation fallback
     * to original record value
     *
     * @param boolean $bool
     * @return void
     */
    public function setTranslationFallback($bool)
    {
        $this->translationFallback = (bool)$bool;
    }

    /**
     * Get the translation class to be used
     * for the object $class
     *
     * @param string $class
     * @return string
     */
    public function getTranslationClass($class)
    {
        return isset($this->configurations[$class]['translationClass']) ?
            $this->configurations[$class]['translationClass'] :
            $this->getDefaultTranslationClass();
    }

    /**
     * Set the locale to use for translation listener
     *
     * @param string $locale
     * @return void
     */
    public function setTranslatableLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * Gets the locale to use for translation. Loads object
     * defined locale first..
     *
     * @param object $object
     * @param ClassMetadata $meta
     * @throws RuntimeException - if language or locale property is not
     *         found in entity
     * @return string
     */
    public function getTranslatableLocale($object, $meta)
    {
        $locale = $this->locale;
        if (isset($this->configurations[$meta->name]['locale'])) {
            $class = $meta->getReflectionClass();
            $reflectionProperty = $class->getProperty($this->configurations[$meta->name]['locale']);
            if (!$reflectionProperty) {
                $column = $this->configurations[$meta->name]['locale'];
                throw new \Gedmo\Exception\RuntimeException("There is no locale or language property ({$column}) found on object: {$meta->name}");
            }
            $reflectionProperty->setAccessible(true);
            $value = $reflectionProperty->getValue($object);
            if (is_string($value) && strlen($value)) {
                $locale = $value;
            }
        }
        return $locale;
    }

    /**
     * Looks for translatable objects being inserted or updated
     * for further processing
     *
     * @param EventArgs $args
     * @return void
     */
    public function onFlush(EventArgs $args)
    {
        $om = $this->getObjectManager($args);
        $uow = $om->getUnitOfWork();
        // check all scheduled inserts for Translatable objects
        foreach ($this->getScheduledObjectInsertions($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            $config = $this->getConfiguration($om, $meta->name);
            if (isset($config['fields'])) {
                $this->handleTranslatableObjectUpdate($om, $object, true);
            }
        }
        // check all scheduled updates for Translatable entities
        foreach ($this->getScheduledObjectUpdates($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            $config = $this->getConfiguration($om, $meta->name);
            if (isset($config['fields'])) {
                // check if there are translation changes
                $changeSet = $this->getObjectChangeSet($uow, $object);
                foreach ($config['fields'] as $field) {
                    if (array_key_exists($field, $changeSet)) {
                        // needs handling
                        $this->handleTranslatableObjectUpdate($om, $object, false);
                        break;
                    }
                }
            }
        }
        // check scheduled deletions for Translatable entities
        foreach ($this->getScheduledObjectDeletions($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            $config = $this->getConfiguration($om, $meta->name);
            if (isset($config['fields'])) {
                $identifierField = $this->getSingleIdentifierFieldName($meta);
                $objectId = $meta->getReflectionProperty($identifierField)->getValue($object);

                $transClass = $this->getTranslationClass($meta->name);
                $this->removeAssociatedTranslations($om, $objectId, $transClass);
            }
        }
    }

     /**
     * Checks for inserted object to update their translation
     * foreign keys
     *
     * @param EventArgs $args
     * @return void
     */
    public function postPersist(EventArgs $args)
    {
        $om = $this->getObjectManager($args);
        $object = $this->getObject($args);
        $uow = $om->getUnitOfWork();
        $meta = $om->getClassMetadata(get_class($object));
        // check if entity is tracked by translatable and without foreign key
        if (array_key_exists($meta->name, $this->configurations) && count($this->pendingTranslationInserts)) {
            $oid = spl_object_hash($object);

            // there should be single identifier
            $identifierField = $this->getSingleIdentifierFieldName($meta);
            $translationMeta = $om->getClassMetadata($this->getTranslationClass($meta->name));
            if (array_key_exists($oid, $this->pendingTranslationInserts)) {
                // load the pending translations without key
                $translations = $this->pendingTranslationInserts[$oid];
                foreach ($translations as $translation) {
                    $translationMeta->getReflectionProperty('foreignKey')->setValue(
                        $translation,
                        $meta->getReflectionProperty($identifierField)->getValue($object)
                    );
                    $this->insertTranslationRecord($om, $translation);
                }
                unset($this->pendingTranslationInserts[$oid]);
            }
        }
    }

    /**
     * After object is loaded, listener updates the translations
     * by currently used locale
     *
     * @param EventArgs $args
     * @return void
     */
    public function postLoad(EventArgs $args)
    {
        $om = $this->getObjectManager($args);
        $object = $this->getObject($args);
        $meta = $om->getClassMetadata(get_class($object));
        $config = $this->getConfiguration($om, $meta->name);

        if (isset($config['fields'])) {
            // fetch translations
            $result = $this->loadTranslations($om, $object);
            // translate object's translatable properties
            foreach ($config['fields'] as $field) {
                $translated = '';
                foreach ((array)$result as $entry) {
                    if ($entry['field'] == $field) {
                        $translated = $entry['content'];
                        break;
                    }
                }
                // update translation
                if (strlen($translated) || !$this->translationFallback) {
                    $meta->getReflectionProperty($field)->setValue($object, $translated);
                    // ensure clean changeset
                    $this->setOriginalObjectProperty(
                        $om->getUnitOfWork(),
                        spl_object_hash($object),
                        $field,
                        $translated
                    );
                }
            }
        }
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
        $this->defaultLocale = $locale;
    }

    /**
     * {@inheritDoc}
     */
    protected function getNamespace()
    {
        return __NAMESPACE__;
    }

    /**
     * Creates the translation for object being flushed
     *
     * @param ObjectManager $om
     * @param object $object
     * @param boolean $isInsert
     * @throws UnexpectedValueException - if locale is not valid, or
     *      primary key is composite, missing or invalid
     * @return void
     */
    protected function handleTranslatableObjectUpdate($om, $object, $isInsert)
    {
        $meta = $om->getClassMetadata(get_class($object));
        // no need cache, metadata is loaded only once in MetadataFactoryClass
        $translationClass = $this->getTranslationClass($meta->name);
        $translationMetadata = $om->getClassMetadata($translationClass);

        // check for the availability of the primary key
        $identifierField = $this->getSingleIdentifierFieldName($meta);
        $objectId = $meta->getReflectionProperty($identifierField)->getValue($object);
        if (!$object && $isInsert) {
            $objectId = null;
        }

        // load the currently used locale
        $locale = strtolower($this->getTranslatableLocale($object, $meta));
        $this->validateLocale($locale);

        $uow = $om->getUnitOfWork();
        $config = $this->getConfiguration($om, $meta->name);
        $translatableFields = $config['fields'];
        foreach ($translatableFields as $field) {
            $translation = null;
            // check if translation allready is created
            if (!$isInsert) {
                $translation = $this->findTranslation(
                    $om,
                    $objectId,
                    $meta->name,
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
                $translationMetadata->getReflectionProperty('objectClass')
                    ->setValue($translation, $meta->name);
                $translationMetadata->getReflectionProperty('foreignKey')
                    ->setValue($translation, $objectId);
                $scheduleUpdate = !$isInsert;
            }

            // set the translated field, take value using reflection
            $translationMetadata->getReflectionProperty('content')
                ->setValue($translation, $meta->getReflectionProperty($field)->getValue($object));
            if ($isInsert && is_null($objectId)) {
                // if we do not have the primary key yet available
                // keep this translation in memory to insert it later with foreign key
                $this->pendingTranslationInserts[spl_object_hash($object)][$field] = $translation;
            } else {
                // persist and compute change set for translation
                $om->persist($translation);
                $uow->computeChangeSet($translationMetadata, $translation);
            }
        }
        // check if we have default translation and need to reset the translation
        if (!$isInsert && strlen($this->defaultLocale)) {
            $this->validateLocale($this->defaultLocale);
            $changeSet = $modifiedChangeSet = $this->getObjectChangeSet($uow, $object);
            foreach ($changeSet as $field => $changes) {
                if (in_array($field, $translatableFields)) {
                    if ($locale != $this->defaultLocale && strlen($changes[0])) {
                        $meta->getReflectionProperty($field)->setValue($object, $changes[0]);
                        $this->setOriginalObjectProperty($uow, spl_object_hash($object), $field, $changes[0]);
                        unset($modifiedChangeSet[$field]);
                    }
                }
            }
            // cleanup current changeset
            $this->clearObjectChangeSet($uow, spl_object_hash($object));
            // recompute changeset only if there are changes other than reverted translations
            if ($modifiedChangeSet) {
                foreach ($modifiedChangeSet as $field => $changes) {
                    $this->setOriginalObjectProperty($uow, spl_object_hash($object), $field, $changes[0]);
                }
                $uow->computeChangeSet($meta, $object);
            }
        }
    }

    /**
     * Validates the given locale
     *
     * @param string $locale - locale to validate
     * @throws InvalidArgumentException if locale is not valid
     * @return void
     */
    protected function validateLocale($locale)
    {
        if (!is_string($locale) || !strlen($locale)) {
            throw new \Gedmo\Exception\InvalidArgumentException('Locale or language cannot be empty and must be set through Listener or Entity');
        }
    }

    /**
     * Get translation class used to store the translations
     *
     * @return string
     */
    abstract protected function getDefaultTranslationClass();

    /**
     * Get the ObjectManager from EventArgs
     *
     * @param EventArgs $args
     * @return ObjectManager
     */
    abstract protected function getObjectManager(EventArgs $args);

    /**
     * Get the Object from EventArgs
     *
     * @param EventArgs $args
     * @return object
     */
    abstract protected function getObject(EventArgs $args);

    /**
     * Get the object changeset from a UnitOfWork
     *
     * @param UnitOfWork $uow
     * @param Object $object
     * @return array
     */
    abstract protected function getObjectChangeSet($uow, $object);

    /**
     * Get the scheduled object updates from a UnitOfWork
     *
     * @param UnitOfWork $uow
     * @return array
     */
    abstract protected function getScheduledObjectUpdates($uow);

    /**
     * Get the scheduled object insertions from a UnitOfWork
     *
     * @param UnitOfWork $uow
     * @return array
     */
    abstract protected function getScheduledObjectInsertions($uow);

    /**
     * Get the scheduled object deletions from a UnitOfWork
     *
     * @param UnitOfWork $uow
     * @return array
     */
    abstract protected function getScheduledObjectDeletions($uow);

    /**
     * Get the single identifier field name
     *
     * @param ClassMetadata $meta
     * @throws MappingException - if identifier is composite
     * @return string
     */
    abstract protected function getSingleIdentifierFieldName($meta);

    /**
     * Removes all associated translations for given object
     *
     * @param ObjectManager $om
     * @param mixed $objectId
     * @param string $transClass
     * @return void
     */
    abstract protected function removeAssociatedTranslations($om, $objectId, $transClass);

    /**
     * Inserts the translation record
     *
     * @param ObjectManager $om
     * @param object $translation
     * @return void
     */
    abstract protected function insertTranslationRecord($om, $translation);

    /**
     * Search for existing translation record
     *
     * @param ObjectManager $om
     * @param mixed $objectId
     * @param string $objectClass
     * @param string $locale
     * @param string $field
     * @return mixed - null if nothing is found, Translation otherwise
     */
    abstract protected function findTranslation($om, $objectId, $objectClass, $locale, $field);

    /**
     * Sets a property value of the original data array of an object
     *
     * @param UnitOfWork $uow
     * @param string $oid
     * @param string $property
     * @param mixed $value
     * @return void
     */
    abstract protected function setOriginalObjectProperty($uow, $oid, $property, $value);

    /**
     * Clears the property changeset of the object with the given OID.
     *
     * @param UnitOfWork $uow
     * @param string $oid The object's OID.
     */
    abstract protected function clearObjectChangeSet($uow, $oid);

    /**
     * Load the translations for a given object
     *
     * @param ObjectManager $om
     * @param object $object
     * @return array
     */
    abstract protected function loadTranslations($om, $object);
}