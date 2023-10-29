<?php

/*
 * This file is part of the Doctrine Behavioral Extensions package.
 * (c) Gediminas Morkevicius <gediminas.morkevicius@gmail.com> http://www.gediminasm.org
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gedmo\Translatable;

use Doctrine\Common\EventArgs;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\Persistence\Event\LoadClassMetadataEventArgs;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Gedmo\Exception\InvalidArgumentException;
use Gedmo\Exception\RuntimeException;
use Gedmo\Mapping\MappedEventSubscriber;
use Gedmo\Tool\Wrapper\AbstractWrapper;
use Gedmo\Translatable\Mapping\Event\TranslatableAdapter;

/**
 * The translation listener handles the generation and
 * loading of translations for entities which implements
 * the Translatable interface.
 *
 * This behavior can impact the performance of your application
 * since it does an additional query for each field to translate.
 *
 * Nevertheless the annotation metadata is properly cached and
 * it is not a big overhead to lookup all entity annotations since
 * the caching is activated for metadata
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 *
 * @phpstan-type TranslatableConfiguration = array{
 *   fields?: string[],
 *   fallback?: array<string, bool>,
 *   locale?: string,
 *   translationClass?: class-string,
 *   useObjectClass?: class-string,
 * }
 *
 * @phpstan-method TranslatableConfiguration getConfiguration(ObjectManager $objectManager, $class)
 *
 * @method TranslatableAdapter getEventAdapter(EventArgs $args)
 *
 * @final since gedmo/doctrine-extensions 3.11
 */
class TranslatableListener extends MappedEventSubscriber
{
    /**
     * Query hint to override the fallback of translations
     * integer 1 for true, 0 false
     */
    public const HINT_FALLBACK = 'gedmo.translatable.fallback';

    /**
     * Query hint to override the fallback locale
     */
    public const HINT_TRANSLATABLE_LOCALE = 'gedmo.translatable.locale';

    /**
     * Query hint to use inner join strategy for translations
     */
    public const HINT_INNER_JOIN = 'gedmo.translatable.inner_join.translations';

    /**
     * Locale which is set on this listener.
     * If Entity being translated has locale defined it
     * will override this one
     *
     * @var string
     */
    protected $locale = 'en_US';

    /**
     * Default locale, this changes behavior
     * to not update the original record field if locale
     * which is used for updating is not default. This
     * will load the default translation in other locales
     * if record is not translated yet
     */
    private string $defaultLocale = 'en_US';

    /**
     * If this is set to false, when if entity does
     * not have a translation for requested locale
     * it will show a blank value
     */
    private bool $translationFallback = false;

    /**
     * List of translations which do not have the foreign
     * key generated yet - MySQL case. These translations
     * will be updated with new keys on postPersist event
     *
     * @var array<int, array<int, object|Translatable>>
     */
    private array $pendingTranslationInserts = [];

    /**
     * Currently in case if there is TranslationQueryWalker
     * in charge. We need to skip issuing additional queries
     * on load
     */
    private bool $skipOnLoad = false;

    /**
     * Tracks locale the objects currently translated in
     *
     * @var array<int, string>
     */
    private array $translatedInLocale = [];

    /**
     * Whether or not, to persist default locale
     * translation or keep it in original record
     */
    private bool $persistDefaultLocaleTranslation = false;

    /**
     * Tracks translation object for default locale
     *
     * @var array<int, array<string, object|Translatable>>
     */
    private array $translationInDefaultLocale = [];

    /**
     * Default translation value upon missing translation
     */
    private ?string $defaultTranslationValue = null;

    /**
     * Specifies the list of events to listen
     *
     * @return string[]
     */
    public function getSubscribedEvents()
    {
        return [
            'postLoad',
            'postPersist',
            'preFlush',
            'onFlush',
            'loadClassMetadata',
        ];
    }

    /**
     * Set to skip or not onLoad event
     *
     * @param bool $bool
     *
     * @return static
     */
    public function setSkipOnLoad($bool)
    {
        $this->skipOnLoad = (bool) $bool;

        return $this;
    }

    /**
     * Whether or not, to persist default locale
     * translation or keep it in original record
     *
     * @param bool $bool
     *
     * @return static
     */
    public function setPersistDefaultLocaleTranslation($bool)
    {
        $this->persistDefaultLocaleTranslation = (bool) $bool;

        return $this;
    }

    /**
     * Check if should persist default locale
     * translation or keep it in original record
     *
     * @return bool
     */
    public function getPersistDefaultLocaleTranslation()
    {
        return (bool) $this->persistDefaultLocaleTranslation;
    }

    /**
     * Add additional $translation for pending $oid object
     * which is being inserted
     *
     * @param int    $oid
     * @param object $translation
     *
     * @return void
     */
    public function addPendingTranslationInsert($oid, $translation)
    {
        $this->pendingTranslationInserts[$oid][] = $translation;
    }

    /**
     * Maps additional metadata
     *
     * @param LoadClassMetadataEventArgs $eventArgs
     *
     * @phpstan-param LoadClassMetadataEventArgs<ClassMetadata<object>, ObjectManager> $eventArgs
     *
     * @return void
     */
    public function loadClassMetadata(EventArgs $eventArgs)
    {
        $this->loadMetadataForObjectClass($eventArgs->getObjectManager(), $eventArgs->getClassMetadata());
    }

    /**
     * Get the translation class to be used
     * for the object $class
     *
     * @param string $class
     *
     * @phpstan-param class-string $class
     *
     * @return string
     *
     * @phpstan-return class-string
     */
    public function getTranslationClass(TranslatableAdapter $ea, $class)
    {
        return self::$configurations[$this->name][$class]['translationClass'] ?? $ea->getDefaultTranslationClass()
        ;
    }

    /**
     * Enable or disable translation fallback
     * to original record value
     *
     * @param bool $bool
     *
     * @return static
     */
    public function setTranslationFallback($bool)
    {
        $this->translationFallback = (bool) $bool;

        return $this;
    }

    /**
     * Weather or not is using the translation
     * fallback to original record
     *
     * @return bool
     */
    public function getTranslationFallback()
    {
        return $this->translationFallback;
    }

    /**
     * Set the locale to use for translation listener
     *
     * @param string $locale
     *
     * @return static
     */
    public function setTranslatableLocale($locale)
    {
        $this->validateLocale($locale);
        $this->locale = $locale;

        return $this;
    }

    /**
     * Set the default translation value on missing translation
     *
     * @deprecated usage of a non nullable value for defaultTranslationValue is deprecated
     * and will be removed on the next major release which will rely on the expected types
     */
    public function setDefaultTranslationValue(?string $defaultTranslationValue): void
    {
        $this->defaultTranslationValue = $defaultTranslationValue;
    }

    /**
     * Sets the default locale, this changes behavior
     * to not update the original record field if locale
     * which is used for updating is not default
     *
     * @param string $locale
     *
     * @return static
     */
    public function setDefaultLocale($locale)
    {
        $this->validateLocale($locale);
        $this->defaultLocale = $locale;

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
     * defined locale first.
     *
     * @param object        $object
     * @param ClassMetadata $meta
     * @param object        $om
     *
     * @throws RuntimeException if language or locale property is not found in entity
     *
     * @return string
     */
    public function getTranslatableLocale($object, $meta, $om = null)
    {
        $locale = $this->locale;
        $configurationLocale = self::$configurations[$this->name][$meta->getName()]['locale'] ?? null;
        if (null !== $configurationLocale) {
            $class = $meta->getReflectionClass();
            if (!$class->hasProperty($configurationLocale)) {
                throw new RuntimeException("There is no locale or language property ({$configurationLocale}) found on object: {$meta->getName()}");
            }
            $reflectionProperty = $class->getProperty($configurationLocale);
            $reflectionProperty->setAccessible(true);
            $value = $reflectionProperty->getValue($object);
            if (is_object($value) && method_exists($value, '__toString')) {
                $value = $value->__toString();
            }
            if ($this->isValidLocale($value)) {
                $locale = $value;
            }
        } elseif ($om instanceof DocumentManager) {
            [, $parentObject] = $om->getUnitOfWork()->getParentAssociation($object);
            if (null !== $parentObject) {
                $parentMeta = $om->getClassMetadata(get_class($parentObject));
                $locale = $this->getTranslatableLocale($parentObject, $parentMeta, $om);
            }
        }

        return $locale;
    }

    /**
     * Handle translation changes in default locale
     *
     * This has to be done in the preFlush because, when an entity has been loaded
     * in a different locale, no changes will be detected.
     *
     * @return void
     */
    public function preFlush(EventArgs $args)
    {
        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
        $uow = $om->getUnitOfWork();

        foreach ($this->translationInDefaultLocale as $oid => $fields) {
            $trans = reset($fields);

            assert(false !== $trans);

            if ($ea->usesPersonalTranslation(get_class($trans))) {
                $entity = $trans->getObject();
            } else {
                $entity = $uow->tryGetById($trans->getForeignKey(), $trans->getObjectClass());
            }

            if (!$entity) {
                continue;
            }

            try {
                $uow->scheduleForUpdate($entity);
            } catch (ORMInvalidArgumentException $e) {
                foreach ($fields as $field => $trans) {
                    $this->removeTranslationInDefaultLocale($oid, $field);
                }
            }
        }
    }

    /**
     * Looks for translatable objects being inserted or updated
     * for further processing
     *
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
            $config = $this->getConfiguration($om, $meta->getName());
            if (isset($config['fields'])) {
                $this->handleTranslatableObjectUpdate($ea, $object, true);
            }
        }
        // check all scheduled updates for Translatable entities
        foreach ($ea->getScheduledObjectUpdates($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            $config = $this->getConfiguration($om, $meta->getName());
            if (isset($config['fields'])) {
                $this->handleTranslatableObjectUpdate($ea, $object, false);
            }
        }
        // check scheduled deletions for Translatable entities
        foreach ($ea->getScheduledObjectDeletions($uow) as $object) {
            $meta = $om->getClassMetadata(get_class($object));
            $config = $this->getConfiguration($om, $meta->getName());
            if (isset($config['fields'])) {
                $wrapped = AbstractWrapper::wrap($object, $om);
                $transClass = $this->getTranslationClass($ea, $meta->getName());
                \assert($wrapped instanceof AbstractWrapper);
                $ea->removeAssociatedTranslations($wrapped, $transClass, $config['useObjectClass']);
            }
        }
    }

    /**
     * Checks for inserted object to update their translation
     * foreign keys
     *
     * @return void
     */
    public function postPersist(EventArgs $args)
    {
        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
        $object = $ea->getObject();
        $meta = $om->getClassMetadata(get_class($object));
        // check if entity is tracked by translatable and without foreign key
        if ($this->getConfiguration($om, $meta->getName()) && [] !== $this->pendingTranslationInserts) {
            $oid = spl_object_id($object);
            if (array_key_exists($oid, $this->pendingTranslationInserts)) {
                // load the pending translations without key
                $wrapped = AbstractWrapper::wrap($object, $om);
                $objectId = $wrapped->getIdentifier();
                $translationClass = $this->getTranslationClass($ea, get_class($object));
                foreach ($this->pendingTranslationInserts[$oid] as $translation) {
                    if ($ea->usesPersonalTranslation($translationClass)) {
                        $translation->setObject($objectId);
                    } else {
                        $translation->setForeignKey($objectId);
                    }
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
     * @return void
     */
    public function postLoad(EventArgs $args)
    {
        $ea = $this->getEventAdapter($args);
        $om = $ea->getObjectManager();
        $object = $ea->getObject();
        $meta = $om->getClassMetadata(get_class($object));
        $config = $this->getConfiguration($om, $meta->getName());
        $locale = $this->defaultLocale;
        $oid = null;
        if (isset($config['fields'])) {
            $locale = $this->getTranslatableLocale($object, $meta, $om);
            $oid = spl_object_id($object);
            $this->translatedInLocale[$oid] = $locale;
        }

        if ($this->skipOnLoad) {
            return;
        }

        if (isset($config['fields']) && ($locale !== $this->defaultLocale || $this->persistDefaultLocaleTranslation)) {
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
                $translated = $this->defaultTranslationValue;

                foreach ($result as $entry) {
                    if ($entry['field'] == $field) {
                        $translated = $entry['content'] ?? null;

                        break;
                    }
                }

                // update translation
                if ($this->defaultTranslationValue !== $translated
                    || (!$this->translationFallback && (!isset($config['fallback'][$field]) || !$config['fallback'][$field]))
                    || ($this->translationFallback && isset($config['fallback'][$field]) && !$config['fallback'][$field])
                ) {
                    $ea->setTranslationValue($object, $field, $translated);
                    // ensure clean changeset
                    $ea->setOriginalObjectProperty(
                        $om->getUnitOfWork(),
                        $object,
                        $field,
                        $meta->getReflectionProperty($field)->getValue($object)
                    );
                }
            }
        }
    }

    /**
     * Sets translation object which represents translation in default language.
     *
     * @param int                 $oid   hash of basic entity
     * @param string              $field field of basic entity
     * @param object|Translatable $trans Translation object
     *
     * @return void
     */
    public function setTranslationInDefaultLocale($oid, $field, $trans)
    {
        if (!isset($this->translationInDefaultLocale[$oid])) {
            $this->translationInDefaultLocale[$oid] = [];
        }
        $this->translationInDefaultLocale[$oid][$field] = $trans;
    }

    /**
     * @return bool
     */
    public function isSkipOnLoad()
    {
        return $this->skipOnLoad;
    }

    /**
     * Check if object has any translation object which represents translation in default language.
     * This is for internal use only.
     *
     * @param int $oid hash of the basic entity
     *
     * @return bool
     */
    public function hasTranslationsInDefaultLocale($oid)
    {
        return array_key_exists($oid, $this->translationInDefaultLocale);
    }

    protected function getNamespace()
    {
        return __NAMESPACE__;
    }

    /**
     * Validates the given locale
     *
     * @param string $locale locale to validate
     *
     * @throws InvalidArgumentException if locale is not valid
     *
     * @return void
     */
    protected function validateLocale($locale)
    {
        if (!$this->isValidLocale($locale)) {
            throw new InvalidArgumentException('Locale or language cannot be empty and must be set through Listener or Entity');
        }
    }

    /**
     * Check if the given locale is valid
     */
    private function isValidLocale(?string $locale): bool
    {
        return is_string($locale) && strlen($locale);
    }

    /**
     * Creates the translation for object being flushed
     *
     * @throws \UnexpectedValueException if locale is not valid, or
     *                                   primary key is composite, missing or invalid
     */
    private function handleTranslatableObjectUpdate(TranslatableAdapter $ea, object $object, bool $isInsert): void
    {
        $om = $ea->getObjectManager();
        $wrapped = AbstractWrapper::wrap($object, $om);
        $meta = $wrapped->getMetadata();
        $config = $this->getConfiguration($om, $meta->getName());
        // no need cache, metadata is loaded only once in MetadataFactoryClass
        $translationClass = $this->getTranslationClass($ea, $config['useObjectClass']);
        $translationMetadata = $om->getClassMetadata($translationClass);

        // check for the availability of the primary key
        $objectId = $wrapped->getIdentifier();
        // load the currently used locale
        $locale = $this->getTranslatableLocale($object, $meta, $om);

        $uow = $om->getUnitOfWork();
        $oid = spl_object_id($object);
        $changeSet = $ea->getObjectChangeSet($uow, $object);
        $translatableFields = $config['fields'];
        foreach ($translatableFields as $field) {
            $wasPersistedSeparetely = false;
            $skip = isset($this->translatedInLocale[$oid]) && $locale === $this->translatedInLocale[$oid];
            $skip = $skip && !isset($changeSet[$field]) && !$this->getTranslationInDefaultLocale($oid, $field);
            if ($skip) {
                continue; // locale is same and nothing changed
            }
            $translation = null;
            foreach ($ea->getScheduledObjectInsertions($uow) as $trans) {
                if ($locale !== $this->defaultLocale
                    && get_class($trans) === $translationClass
                    && $trans->getLocale() === $this->defaultLocale
                    && $trans->getField() === $field
                    && $this->belongsToObject($ea, $trans, $object)) {
                    $this->setTranslationInDefaultLocale($oid, $field, $trans);

                    break;
                }
            }

            // lookup persisted translations
            foreach ($ea->getScheduledObjectInsertions($uow) as $trans) {
                if (get_class($trans) !== $translationClass
                    || $trans->getLocale() !== $locale
                    || $trans->getField() !== $field) {
                    continue;
                }

                if ($ea->usesPersonalTranslation($translationClass)) {
                    $wasPersistedSeparetely = $trans->getObject() === $object;
                } else {
                    $wasPersistedSeparetely = $trans->getObjectClass() === $config['useObjectClass']
                        && $trans->getForeignKey() === $objectId;
                }

                if ($wasPersistedSeparetely) {
                    $translation = $trans;

                    break;
                }
            }

            // check if translation already is created
            if (!$isInsert && !$translation) {
                \assert($wrapped instanceof AbstractWrapper);

                $translation = $ea->findTranslation(
                    $wrapped,
                    $locale,
                    $field,
                    $translationClass,
                    $config['useObjectClass']
                );
            }

            // create new translation if translation not already created and locale is different from default locale, otherwise, we have the date in the original record
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
                $content = $ea->getTranslationValue($object, $field);
                $translation->setContent($content);
                // check if need to update in database
                $transWrapper = AbstractWrapper::wrap($translation, $om);
                if (((null === $content && !$isInsert) || is_bool($content) || is_int($content) || is_string($content) || !empty($content)) && ($isInsert || !$transWrapper->getIdentifier() || isset($changeSet[$field]))) {
                    if ($isInsert && !$objectId && !$ea->usesPersonalTranslation($translationClass)) {
                        // if we do not have the primary key yet available
                        // keep this translation in memory to insert it later with foreign key
                        $this->pendingTranslationInserts[spl_object_id($object)][] = $translation;
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

            if ($isInsert && null !== $this->getTranslationInDefaultLocale($oid, $field)) {
                // We can't rely on object field value which is created in non-default locale.
                // If we provide translation for default locale as well, the latter is considered to be trusted
                // and object content should be overridden.
                $wrapped->setPropertyValue($field, $this->getTranslationInDefaultLocale($oid, $field)->getContent());
                $ea->recomputeSingleObjectChangeset($uow, $meta, $object);
                $this->removeTranslationInDefaultLocale($oid, $field);
            }
        }
        $this->translatedInLocale[$oid] = $locale;
        // check if we have default translation and need to reset the translation
        if (!$isInsert && strlen($this->defaultLocale)) {
            $this->validateLocale($this->defaultLocale);
            $modifiedChangeSet = $changeSet;
            foreach ($changeSet as $field => $changes) {
                if (in_array($field, $translatableFields, true)) {
                    if ($locale !== $this->defaultLocale) {
                        $ea->setOriginalObjectProperty($uow, $object, $field, $changes[0]);
                        unset($modifiedChangeSet[$field]);
                    }
                }
            }
            $ea->recomputeSingleObjectChangeset($uow, $meta, $object);
            // cleanup current changeset only if working in a another locale different than de default one, otherwise the changeset will always be reverted
            if ($locale !== $this->defaultLocale) {
                $ea->clearObjectChangeSet($uow, $object);
                // recompute changeset only if there are changes other than reverted translations
                if ($modifiedChangeSet || $this->hasTranslationsInDefaultLocale($oid)) {
                    foreach ($modifiedChangeSet as $field => $changes) {
                        $ea->setOriginalObjectProperty($uow, $object, $field, $changes[0]);
                    }
                    foreach ($translatableFields as $field) {
                        if (null !== $this->getTranslationInDefaultLocale($oid, $field)) {
                            $wrapped->setPropertyValue($field, $this->getTranslationInDefaultLocale($oid, $field)->getContent());
                            $this->removeTranslationInDefaultLocale($oid, $field);
                        }
                    }
                    $ea->recomputeSingleObjectChangeset($uow, $meta, $object);
                }
            }
        }
    }

    /**
     * Removes translation object which represents translation in default language.
     * This is for internal use only.
     *
     * @param int    $oid   hash of the basic entity
     * @param string $field field of basic entity
     */
    private function removeTranslationInDefaultLocale(int $oid, string $field): void
    {
        if (isset($this->translationInDefaultLocale[$oid])) {
            if (isset($this->translationInDefaultLocale[$oid][$field])) {
                unset($this->translationInDefaultLocale[$oid][$field]);
            }
            if (!$this->translationInDefaultLocale[$oid]) {
                // We removed the final remaining elements from the
                // translationInDefaultLocale[$oid] array, so we might as well
                // completely remove the entry at $oid.
                unset($this->translationInDefaultLocale[$oid]);
            }
        }
    }

    /**
     * Gets translation object which represents translation in default language.
     * This is for internal use only.
     *
     * @param int    $oid   hash of the basic entity
     * @param string $field field of basic entity
     *
     * @return object|Translatable|null Returns translation object if it exists or NULL otherwise
     */
    private function getTranslationInDefaultLocale(int $oid, string $field)
    {
        return $this->translationInDefaultLocale[$oid][$field] ?? null;
    }

    /**
     * Checks if the translation entity belongs to the object in question
     */
    private function belongsToObject(TranslatableAdapter $ea, object $trans, object $object): bool
    {
        if ($ea->usesPersonalTranslation(get_class($trans))) {
            return $trans->getObject() === $object;
        }

        return $trans->getForeignKey() === $object->getId()
            && ($trans->getObjectClass() === get_class($object));
    }
}
