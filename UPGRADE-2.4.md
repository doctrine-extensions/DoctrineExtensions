# UPGRADE FROM 2.3 TO 2.4

## Translatable

**NOTE:** BC break

- Translatable was refactored completely and data migration is necessary. Read the **doc/translatable.md**
- Annotations **@TranslationEntity** **Locale** **Language** are no longer available.
- option **fallback** for annotation **@Translatable** is no longer available. Was used to manage separate field
fallbacks.
- If you used **Gedmo\Translatable\Translatable** interface, it is no longer available and should be removed.
- methods for **TranslatableListener** `setDefaultLocale` and `getDefaultLocale` are no longer available, there is no
more default locale.
- listener method `addPendingTranslationInsert` is no longer available. Was used internally but left as public.
- listener method `getTranslationClass` is no longer available.
- methods for **TranslatableListener** `setPersistDefaultLocaleTranslation` and `getPersistDefaultLocaleTranslation`
are no longer available. Translations are always persisted in all locales.
- listener methods `setTranslationFallback` and `getTranslationFallback` has changed. Now equivalents are: `setFallbackLocales`
which takes an array of fallback locales, and `getFallbackLocales` which returns an array, was boolean before.
- In consequence, **TranslatableListener::HINT_FALLBACK** will take an array as fallback locale list.
- Translation query hints now require **TranslatableListener::HINT_TRANSLATABLE_LOCALE** to be set. This caused cache
issues before and was confusing when facing them. Query hints now are necessary to take effect, none of the properties
set to listener will be used in **translation query**.
- by default **TranslatableListener** will translate in **en** locale (was **en_US** before).
- Translations now will contain all translatable properties in direct mapping, which means there will be only one left
join to translate an entity. Also there woun't be any casting issues in translatable queries.
- Generate translations for translated entities or documents, using **Gedmo\Translatable\Command\GenerateTranslationsCommand**
which should be hooked to doctrine console script.

### Migrate translations

**IMPORTANT:** make a backup of your database and source code.

@TODO: run migration command to migrate previous translations

## Translator

**NOTE:** BC break

- Removed in favor of translatable refactoring.

## Sluggable

**NOTE:** BC break with low probability

- If there was a listener method `addManagedFilter` used anywhere in your code. You should change it to
`addFilterToIgnore` which takes a filter classname as a parameter. These filters will be ignored during slug
persistence. The general purpose was to support **Softdeleteable** and create more general filter management for
all extension listeners.
- If you used **Gedmo\Sluggable\Sluggable** interface, it is no longer available and should be removed.

## Tree

### NestedSet

- now works together with **Softdeleteable** updates node lft - rgt values even if node was softdeleted, so later it
could be restored.

## General changes which should not impact your code:

- All class names like **Loggable - logEntryClass** supports now relative class names as everywhere else in orm mapping.

before:

    <?php

    namespace MyNamespace\Entity;

    use Gedmo\Mapping\Annotation as Gedmo;
    use Doctrine\ORM\Mapping as ORM;

    /**
     * @ORM\Entity
     * @Gedmo\Loggable(logEntryClass="MyNamespace\Entity\MyLogEntry")
     */
    class Article

now can also be:

    <?php

    namespace MyNamespace\Entity;

    use Gedmo\Mapping\Annotation as Gedmo;
    use Doctrine\ORM\Mapping as ORM;

    /**
     * @ORM\Entity
     * @Gedmo\Loggable(logEntryClass="MyLogEntry")
     */
    class Article

