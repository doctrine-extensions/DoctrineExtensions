<?php

namespace Gedmo\Translatable;

use Doctrine\Common\EventArgs;
use Gedmo\Tool\Wrapper\AbstractWrapper;
use Gedmo\Mapping\MappedEventSubscriber;
use Gedmo\Translatable\Mapping\Event\TranslatableAdapter;

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
 * @subpackage TranslatableListener
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class TranslatableListener extends MappedEventSubscriber
{
    /**
     * Query hint to override the fallback of translations
     * integer 1 for true, 0 false
     */
    const HINT_FALLBACK = 'gedmo.translatable.fallback';

    /**
     * Query hint to override the fallback locale
     */
    const HINT_TRANSLATABLE_LOCALE = 'gedmo.translatable.locale';

    /**
     * Query hint to use inner join strategy for translations
     */
    const HINT_INNER_JOIN = 'gedmo.translatable.inner_join.translations';

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
    private $defaultLocale = 'en_us';

    /**
     * If this is set to false, when if entity does
     * not have a translation for requested locale
     * it will show a blank value
     *
     * @var boolean
     */
    private $translationFallback = false;

    /**
     * List of translations which do not have the foreign
     * key generated yet - MySQL case. These translations
     * will be updated with new keys on postPersist event
     *
     * @var array
     */
    private $pendingTranslationInserts = array();

    /**
     * Currently in case if there is TranslationQueryWalker
     * in charge. We need to skip issuing additional queries
     * on load
     *
     * @var boolean
     */
    private $skipOnLoad = false;

    /**
     * Tracks locale the objects currently translated in
     *
     * @var array
     */
    private $translatedInLocale = array();

    /**
     * Wether or not, to persist default locale
     * translation or keep it in original record
     *
     * @var boolean
     */
    private $persistDefaultLocaleTranslation = false;

    /**
     * Specifies the list of events to listen
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            'postLoad',
            'postPersist',
            'onFlush',
            'loadClassMetadata'
        );
    }

    /**
     * Set to skip or not onLoad event
     *
     * @param boolean $bool
     * @return TranslatableListener
     */
    public function setSkipOnLoad($bool)
    {
        $this->skipOnLoad = (bool)$bool;
        return $this;
    }

    /**
     * Wether or not, to persist default locale
     * translation or keep it in original record
     *
     * @param boolean $bool
     * @return \Gedmo\Translatable\TranslatableListener
     */
    public function setPersistDefaultLocaleTranslation($bool)
    {
        $this->persistDefaultLocaleTranslation = (bool)$bool;
        return $this;
    }

    /**
     * Add additional $translation for pending $oid object
     * which is being inserted
     *
     * @param string $oid
     * @param object $translation
     */
    public function addPendingTranslationInsert($oid, $translation)
    {
        $this->pendingTranslationInserts[$oid][] = $translation;
    }

    /**
     * Mapps additional metadata
     *
     * @param EventArgs $eventArgs
     * @return void
     */
    public function loadClassMetadata(EventArgs $eventArgs)
    {
        $ea = $this->getEventAdapter($eventArgs);
        $this->loadMetadataForObjectClass($ea->getObjectManager(), $eventArgs->getClassMetadata());
    }

    /**
     * Get the translation class to be used
     * for the object $class
     *
     * @param TranslatableAdapter $ea
     * @param string $class
     * @return string
     */
    public function getTranslationClass(TranslatableAdapter $ea, $class)
    {
        return isset(self::$configurations[$this->name][$class]['translationClass']) ?
            self::$configurations[$this->name][$class]['translationClass'] :
            $ea->getDefaultTranslationClass()
        ;
    }

    /**
     * Enable or disable translation fallback
     * to original record value
     *
     * @param boolean $bool
     * @return TranslatableListener
     */
    public function setTranslationFallback($bool)
    {
        $this->translationFallback = (bool)$bool;
        return $this;
    }

    /**
     * Weather or not is using the translation
     * fallback to original record
     *
     * @return boolean
     */
    public function getTranslationFallback()
    {
        return $this->translationFallback;
    }

    /**
     * Set the locale to use for translation listener
     *
     * @param string $locale
     * @return TranslatableListener
     */
    public function setTranslatableLocale($locale)
    {
        $this->validateLocale($locale);
        $this->locale = strtolower($locale);
        return $this;
    }

    /**
     * Sets the default locale, this changes behavior
     * to not update the original record field if locale
     * which is used for updating is not default
     *
     * @param string $locale
     * @return TranslatableListener
     */
    public function setDefaultLocale($locale)
    {
        $this->validateLocale($locale);
        $this->defaultLocale = strtolower($locale);
        return $this;
    }

    /**
     * Gets the default locale
     *
     * @return string
     */
    public function getDefaultLocale()
    {
        return $this->defaultLocale;
    }

    /**
     * Get currently set global locale, used
     * extensively during query execution
     *
     * @return string
     */
    public function getListenerLocale()
    {
        return $this->locale;
    }

    /**
     * Gets the locale to use for translation. Loads object
     * defined locale first..
     *
     * @param object $object
     * @param object $meta
     * @throws RuntimeException - if language or locale property is not
     *         found in entity
     * @return string
     */
    public function getTranslatableLocale($object, $meta)
    {
        $locale = $this->locale;
        if (isset(self::$configurations[$this->name][$meta->name]['locale'])) {
            $class = $meta->getReflectionClass();
            $reflectionProperty = $class->getProperty(self::$configurations[$this->name][$meta->name]['locale']);
            if (!$reflectionProperty) {
                $column = self::$configurations[$this->name][$meta->name]['locale'];
                throw new \Gedmo\Exception\RuntimeException("There is no locale or language property ({$column}) found on object: {$meta->name}");
            }
            $reflectionProperty->setAccessible(true);
            $value = $reflectionProperty->getValue($object);
            try {
                $this->validateLocale($value);
                $locale = strtolower($value);
            } catch(\Gedmo\Exception\InvalidArgumentException $e) {}
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
        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
        $uow = $om->getUnitOfWork();
        // check all scheduled inserts for Translatable objects
        foreach ($ea->getScheduledObjectInsertions($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            $config = $this->getConfiguration($om, $meta->name);
            if (isset($config['fields'])) {
                $this->handleTranslatableObjectUpdate($ea, $object, true);
            }
        }
        // check all scheduled updates for Translatable entities
        foreach ($ea->getScheduledObjectUpdates($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            $config = $this->getConfiguration($om, $meta->name);
            if (isset($config['fields'])) {
                $this->handleTranslatableObjectUpdate($ea, $object, false);
            }
        }
        // check scheduled deletions for Translatable entities
        foreach ($ea->getScheduledObjectDeletions($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            $config = $this->getConfiguration($om, $meta->name);
            if (isset($config['fields'])) {
                $wrapped = AbstractWrapper::wrap($object, $om);
                $transClass = $this->getTranslationClass($ea, $meta->name);
                $ea->removeAssociatedTranslations($wrapped, $transClass, $config['useObjectClass']);
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
        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
        $object = $ea->getObject();
        $meta = $om->getClassMetadata(get_class($object));
        // check if entity is tracked by translatable and without foreign key
        if ($this->getConfiguration($om, $meta->name) && count($this->pendingTranslationInserts)) {
            $oid = spl_object_hash($object);
            if (array_key_exists($oid, $this->pendingTranslationInserts)) {
                // load the pending translations without key
                $wrapped = AbstractWrapper::wrap($object, $om);
                $objectId = $wrapped->getIdentifier();
                foreach ($this->pendingTranslationInserts[$oid] as $translation) {
                    $translation->setForeignKey($objectId);
                    $ea->insertTranslationRecord($translation);
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
        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
        $object = $ea->getObject();
        $meta = $om->getClassMetadata(get_class($object));
        $config = $this->getConfiguration($om, $meta->name);
        if (isset($config['fields'])) {
            $locale = $this->getTranslatableLocale($object, $meta);
            $oid = spl_object_hash($object);
            $this->translatedInLocale[$oid] = $locale;
        }

        if ($this->skipOnLoad) {
            return;
        }

        if (isset($config['fields']) && $locale !== $this->defaultLocale) {
            // fetch translations
            $translationClass = $this->getTranslationClass($ea, $config['useObjectClass']);
            $result = $ea->loadTranslations(
                $object,
                $translationClass,
                $locale,
                $config['useObjectClass']
            );
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
                if ($translated || !$this->translationFallback) {
                    $ea->setTranslationValue($object, $field, $translated);
                    // ensure clean changeset
                    $ea->setOriginalObjectProperty(
                        $om->getUnitOfWork(),
                        $oid,
                        $field,
                        $meta->getReflectionProperty($field)->getValue($object)
                    );
                }
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function getNamespace()
    {
        return __NAMESPACE__;
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
     * Creates the translation for object being flushed
     *
     * @param TranslatableAdapter $ea
     * @param object $object
     * @param boolean $isInsert
     * @throws UnexpectedValueException - if locale is not valid, or
     *      primary key is composite, missing or invalid
     * @return void
     */
    private function handleTranslatableObjectUpdate(TranslatableAdapter $ea, $object, $isInsert)
    {
        $om = $ea->getObjectManager();
        $wrapped = AbstractWrapper::wrap($object, $om);
        $meta = $wrapped->getMetadata();
        $config = $this->getConfiguration($om, $meta->name);
        // no need cache, metadata is loaded only once in MetadataFactoryClass
        $translationClass = $this->getTranslationClass($ea, $config['useObjectClass']);
        $translationMetadata = $om->getClassMetadata($translationClass);

        // check for the availability of the primary key
        $objectId = $wrapped->getIdentifier();
        // load the currently used locale
        $locale = $this->getTranslatableLocale($object, $meta);

        $uow = $om->getUnitOfWork();
        $oid = spl_object_hash($object);
        $changeSet = $ea->getObjectChangeSet($uow, $object);

        $translatableFields = $config['fields'];
        foreach ($translatableFields as $field) {
            $wasPersistedSeparetely = false;
            $skip = isset($this->translatedInLocale[$oid]) && $locale === $this->translatedInLocale[$oid];
            $skip = $skip && !isset($changeSet[$field]);
            if ($skip) {
                continue; // locale is same and nothing changed
            }
            $translation = null;
            // lookup persisted translations
            if ($ea->usesPersonalTranslation($translationClass)) {
                foreach ($ea->getScheduledObjectInsertions($uow) as $trans) {
                    $wasPersistedSeparetely = get_class($trans) === $translationClass
                        && $trans->getLocale() === $locale
                        && $trans->getField() === $field
                        && $trans->getObject() === $object
                    ;
                    if ($wasPersistedSeparetely) {
                        $translation = $trans;
                        break;
                    }
                }
            }
            // check if translation allready is created
            if (!$isInsert && !$translation) {
                $translation = $ea->findTranslation(
                    $wrapped,
                    $locale,
                    $field,
                    $translationClass,
                    $config['useObjectClass']
                );
            }
            // create new translation if translation not already created and locale is differentent from default locale, otherwise, we have the date in the original record
            $persistNewTranslation = !$translation
                && ($locale !== $this->defaultLocale || $this->persistDefaultLocaleTranslation)
            ;
            if ($persistNewTranslation) {
                $translation = $translationMetadata->newInstance();
                $translation->setLocale($locale);
                $translation->setField($field);
                if ($ea->usesPersonalTranslation($translationClass)) {
                    $translation->setObject($object);
                } else {
                    $translation->setObjectClass($config['useObjectClass']);
                    $translation->setForeignKey($objectId);
                }
            }

            if ($translation) {
                // set the translated field, take value using reflection
                $value = $wrapped->getPropertyValue($field);
                $translation->setContent($ea->getTranslationValue($object, $field));
                if ($isInsert && !$objectId && !$ea->usesPersonalTranslation($translationClass)) {
                    // if we do not have the primary key yet available
                    // keep this translation in memory to insert it later with foreign key
                    $this->pendingTranslationInserts[spl_object_hash($object)][] = $translation;
                } else {
                    // persist and compute change set for translation
                    if ($wasPersistedSeparetely) {
                        $ea->recomputeSingleObjectChangeset($uow, $translationMetadata, $translation);
                    } else {
                        $om->persist($translation);
                        $uow->computeChangeSet($translationMetadata, $translation);
                    }
                }
            }
        }
        $this->translatedInLocale[$oid] = $locale;
        // check if we have default translation and need to reset the translation
        if (!$isInsert && strlen($this->defaultLocale)) {
            $this->validateLocale($this->defaultLocale);
            $modifiedChangeSet = $changeSet;
            foreach ($changeSet as $field => $changes) {
                if (in_array($field, $translatableFields)) {
                    if ($locale !== $this->defaultLocale) {
                        $wrapped->setPropertyValue($field, $changes[0]);
                        $ea->setOriginalObjectProperty($uow, $oid, $field, $changes[0]);
                        unset($modifiedChangeSet[$field]);
                    }
                }
            }
            // cleanup current changeset only if working in a another locale different than de default one, otherwise the changeset will always be reverted
            if ($locale !== $this->defaultLocale) {
                $ea->clearObjectChangeSet($uow, $oid);
                // recompute changeset only if there are changes other than reverted translations
                if ($modifiedChangeSet) {
                    foreach ($modifiedChangeSet as $field => $changes) {
                        $ea->setOriginalObjectProperty($uow, $oid, $field, $changes[0]);
                    }
                    $ea->recomputeSingleObjectChangeset($uow, $meta, $object);
                }
            }
        }
    }
}
