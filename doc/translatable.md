# Translatable behavior extension for Doctrine

**Translatable** behavior offers a very handy solution for translating specific record fields
in different languages. Further more, it loads the translations automatically for a locale
currently used, which can be set to **Translatable Listener** on it`s initialization or later
for other cases through the **Entity** itself

Features:

- Automatic storage of translations in database
- ORM and ODM support using same listener
- Automatic translation of Entity or Document fields when loaded
- ORM query can use **hint** to translate all records without issuing additional queries
- Can be nested with other behaviors
- Attribute, Annotation and Xml mapping support for extensions

**Note list:**

- Public [Translatable repository](https://github.com/doctrine-extensions/DoctrineExtensions "Translatable extension on Github") is available on github
- Using other extensions on the same Entity fields may result in unexpected way
- May impact your application performance since it does an additional query for translation if loaded without query hint

This article will cover the basic installation and functionality of **Translatable** behavior

Content:

- [Including](#including-extension) the extension
- Entity [example](#entity-domain-object)
- Document [example](#document-domain-object)
- [Xml](#xml-mapping) mapping example
- Basic usage [examples](#basic-examples)
- [Persisting](#multi-translations) multiple translations
- Using ORM query [hint](#orm-query-hint)
- Advanced usage [examples](#advanced-examples)
- Personal [translations](#personal-translations)

<a name="including-extension"></a>

## Setup and autoloading

Read the [documentation](./annotations.md#em-setup)
or check the [example code](../example)
on how to setup and use the extensions in most optimized way.

### Translatable annotations:
- **@Gedmo\Mapping\Annotation\Translatable** it will **translate** this field
- **@Gedmo\Mapping\Annotation\TranslationEntity(class="my\class")** it will use this class to store the generated **translations**
- **@Gedmo\Mapping\Annotation\Locale** or **@Gedmo\Mapping\Annotation\Language** these will identify this column as **locale** or **language**
used to override the global locale

### Translatable attributes:
- **Gedmo\Mapping\Annotation\Translatable** it will **translate** this field
- **Gedmo\Mapping\Annotation\TranslationEntity(class: MyClass::class)** it will use this class to store the generated **translations**
- **Gedmo\Mapping\Annotation\Locale** or **Gedmo\Mapping\Annotation\Language** these will identify this column as **locale** or **language**
  used to override the global locale

<a name="entity-domain-object"></a>

## Translatable Entity example:

**Note:** that Translatable interface is not necessary, except in cases where
you need to identify an entity as being Translatable. The metadata is loaded only once when
cache is activated

### Annotations

```php
<?php
namespace Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Translatable\Translatable;

/**
 * @ORM\Table(name="articles")
 * @ORM\Entity
 */
class Article implements Translatable
{
    /** @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer") */
    private $id;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(name="title", type="string", length=128)
     */
    private $title;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(name="content", type="text")
     */
    private $content;

    /**
     * @Gedmo\Locale
     * Used locale to override Translation listener`s locale
     * this is not a mapped field of entity metadata, just a simple property
     */
    private $locale;

    public function getId()
    {
        return $this->id;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setContent($content)
    {
        $this->content = $content;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setTranslatableLocale($locale)
    {
        $this->locale = $locale;
    }
}
```

### Attributes

```php
<?php
namespace Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Translatable\Translatable;

 #[ORM\Table(name: 'articles')]
 #[ORM\Entity]
class Article implements Translatable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[Gedmo\Translatable]
    #[ORM\Column(name: 'title', type: 'string', length: 128)]
    private $title;

    #[Gedmo\Translatable]
    #[ORM\Column(name: 'content', type: 'text')]
    private $content;

    /**
     * Used locale to override Translation listener`s locale
     * this is not a mapped field of entity metadata, just a simple property
     */
    #[Gedmo\Locale]
    private $locale;

    public function getId()
    {
        return $this->id;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setContent($content)
    {
        $this->content = $content;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setTranslatableLocale($locale)
    {
        $this->locale = $locale;
    }
}
```

<a name="document-domain-object"></a>

## Translatable Document example:

```php
<?php
namespace Document;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Gedmo\Translatable\Translatable;

/**
 * @ODM\Document(collection="articles")
 */
class Article implements Translatable
{
    /** @ODM\Id */
    private $id;

    /**
     * @Gedmo\Translatable
     * @ODM\Field(type="string")
     */
    #[Gedmo\Translatable]
    private $title;

    /**
     * @Gedmo\Translatable
     * @ODM\Field(type="string")
     */
    #[Gedmo\Translatable]
    private $content;

    /**
     * @Gedmo\Locale
     * Used locale to override Translation listener`s locale
     * this is not a mapped field of entity metadata, just a simple property
     */
    #[Gedmo\Locale]
    private $locale;

    public function getId()
    {
        return $this->id;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setContent($content)
    {
        $this->content = $content;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setTranslatableLocale($locale)
    {
        $this->locale = $locale;
    }
}
```

<a name="xml-mapping"></a>

## Xml mapping example

```xml
<?xml version="1.0" encoding="UTF-8"?>
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
                  xmlns:gedmo="http://gediminasm.org/schemas/orm/doctrine-extensions-mapping">

    <entity name="Mapping\Fixture\Xml\Translatable" table="translatables">

        <id name="id" type="integer" column="id">
            <generator strategy="AUTO"/>
        </id>

        <field name="title" type="string" length="128">
            <gedmo:translatable/>
        </field>
        <field name="content" type="text">
            <gedmo:translatable/>
        </field>

        <gedmo:translation entity="Gedmo\Translatable\Entity\Translation" locale="locale"/>

    </entity>

</doctrine-mapping>
```

<a name="basic-examples"></a>

## Basic usage examples:

Currently a global locale used for translations is "en_us" which was
set in **TranslationListener** globally. To save article with its translations:

```php
<?php
$article = new Entity\Article;
$article->setTitle('my title in en');
$article->setContent('my content in en');
$em->persist($article);
$em->flush();
```

This inserted an article and inserted the translations for it in "en_us" locale
only if **en_us** is not the [default locale](#advanced-examples) in case if default locale
matches current locale - it uses original record value as translation

Now lets update our article in different locale:

```php
<?php
// first load the article
$article = $em->find('Entity\Article', 1 /*article id*/);
$article->setTitle('my title in de');
$article->setContent('my content in de');
$article->setTranslatableLocale('de_de'); // change locale
$em->persist($article);
$em->flush();
```

This updated an article and inserted the translations for it in "de_de" locale
To see and load all translations of **Translatable** Entity:

```php
<?php
// reload in different language
$article = $em->find('Entity\Article', 1 /*article id*/);
$article->setLocale('ru_ru');
$em->refresh($article);

$article = $em->find('Entity\Article', 1 /*article id*/);
$repository = $em->getRepository('Gedmo\Translatable\Entity\Translation');
$translations = $repository->findTranslations($article);
/* $translations contains:
Array (
    [de_de] => Array
        (
            [title] => my title in de
            [content] => my content in de
        )

    [en_us] => Array
        (
            [title] => my title in en
            [content] => my content in en
        )
)*/
```

As far as our global locale is now "en_us" and updated article has "de_de" values.
Lets try to load it and it should be translated in English

```php
<?php
$article = $em->getRepository('Entity\Article')->find(1/* id of article */);
echo $article->getTitle();
// prints: "my title in en"
echo $article->getContent();
// prints: "my content in en"
```

<a name="multi-translations"></a>

## Persisting multiple translations

Usually it is more convenient to persist more translations when creating
or updating a record. **Translatable** allows to do that through translation repository.
All additional translations will be tracked by listener and when the flush will be executed,
it will update or persist all additional translations.

**Note:** these translations will not be processed as ordinary fields of your object,
in case if you translate a **slug** additional translation will not know how to generate
the slug, so the value as an additional translation should be processed when creating it.

### Example of multiple translations:

```php
<?php
// persisting multiple translations, assume default locale is EN
$repository = $em->getRepository('Gedmo\\Translatable\\Entity\\Translation');
// it works for ODM also
$article = new Article;
$article->setTitle('My article en');
$article->setContent('content en');

$repository->translate($article, 'title', 'de', 'my article de')
    ->translate($article, 'content', 'de', 'content de')
    ->translate($article, 'title', 'ru', 'my article ru')
    ->translate($article, 'content', 'ru', 'content ru')
;

$em->persist($article);
$em->flush();

// updating same article also having one new translation

$repo
    ->translate($article, 'title', 'lt', 'title lt')
    ->translate($article, 'content', 'lt', 'content lt')
    ->translate($article, 'title', 'ru', 'title ru change')
    ->translate($article, 'content', 'ru', 'content ru change')
    ->translate($article, 'title', 'en', 'title en (default locale) update')
    ->translate($article, 'content', 'en', 'content en (default locale) update')
;
$em->flush();
```

<a name="orm-query-hint"></a>

## Using ORM query hint

By default, behind the scenes, when you load a record - translatable hooks into **postLoad**
event and issues additional query to translate all fields. Imagine that, when you load a collection,
it may issue a lot of queries just to translate those fields. Including array hydration,
it is not possible to hook any **postLoad** event since it is not an
entity being hydrated. These are the main reasons why **TranslationWalker** was created.

**TranslationWalker** uses a query **hint** to hook into any **select type query**,
and when you execute the query, no matter which hydration method you use, it automatically
joins the translations for all fields, so you could use ordering filtering or whatever you
want on **translated fields** instead of original record fields.

And in result there is only one query for all this happiness.

If you use translation [fallbacks](#advanced-examples) it will be also in the same single
query and during the hydration process it will replace the empty fields in case if they
do not have a translation in currently used locale.

Now enough talking, here is an example:

```php
<?php
$dql = <<<___SQL
  SELECT a, c, u
  FROM Article a
  LEFT JOIN a.comments c
  JOIN c.author u
  WHERE a.title LIKE '%translated_title%'
  ORDER BY a.title
___SQL;

$query = $em->createQuery($dql);
// set the translation query hint
$query->setHint(
    \Doctrine\ORM\Query::HINT_CUSTOM_OUTPUT_WALKER,
    'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker'
);

$articles = $query->getResult(); // object hydration
$articles = $query->getArrayResult(); // array hydration
```

And even a subselect:

```php
<?php
$dql = <<<___SQL
  SELECT a, c, u
  FROM Article a
  LEFT JOIN a.comments c
  JOIN c.author u
  WHERE a.id IN (
    SELECT a2.id
    FROM Article a2
    WHERE a2.title LIKE '%something_translated%'
      AND a2.status = 1
  )
  ORDER BY a.title
___SQL;

$query = $em->createQuery($dql);
$query->setHint(
    \Doctrine\ORM\Query::HINT_CUSTOM_OUTPUT_WALKER,
    'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker'
);
```

**NOTE:** if you use memcache or apc. You should set locale and other options like fallbacks
to query through hints. Otherwise the query will be cached with a first used locale

```php
<?php
// locale
$query->setHint(
    \Gedmo\Translatable\TranslatableListener::HINT_TRANSLATABLE_LOCALE,
    'en' // take locale from session or request etc.
);
// fallback
$query->setHint(
    \Gedmo\Translatable\TranslatableListener::HINT_FALLBACK,
    1 // fallback to default values in case if record is not translated
);

$articles = $query->getResult(); // object hydration
```

There's no need for any words anymore.. right?
I recommend you to use it extensively since it is a way better performance, even in
cases where you need to load single translated entity.

**Note**: Even in **COUNT** select statements translations are joined to leave a
possibility to filter by translated field, if you do not need it, just do not set
the **hint**. Also take into account that it is not possible to translate components
in **JOIN WITH** statement, example

```
JOIN a.comments c WITH c.message LIKE '%will_not_be_translated%'`
```

**Note**: any **find** related method calls cannot hook this hint automagically, we
will use a different approach when **persister overriding feature** will be
available in **Doctrine**

In case if **translation query walker** is used, you can additionally override:

### Overriding translation fallback

```php
<?php
$query->setHint(\Gedmo\Translatable\TranslatableListener::HINT_FALLBACK, 1);
```

will fallback to default locale translations instead of empty values if used.
And will override the translation listener setting for fallback.

```php
<?php
$query->setHint(\Gedmo\Translatable\TranslatableListener::HINT_FALLBACK, 0);
```

will do the opposite.

### Using inner join strategy

```php
<?php
$query->setHint(\Gedmo\Translatable\TranslatableListener::HINT_INNER_JOIN, true);
```

will use **INNER** joins
for translations instead of **LEFT** joins, so that in case if you do not want untranslated
records in your result set for instance.

### Overriding translatable locale

```php
<?php
$query->setHint(\Gedmo\Translatable\TranslatableListener::HINT_TRANSLATABLE_LOCALE, 'en');
```

would override the translation locale used to translate the resultset.

**Note:** all these query hints lasts only for the specific query.

<a name="advanced-examples"></a>

## Advanced examples:

### Default locale

In some cases we need a default translation as a fallback if record does not have
a translation on globally used locale. In that case Translation Listener takes the
current value of Entity. So if **default locale** is specified and it matches the
locale in which record is being translated - it will not create extra translation
but use original values instead. If translation fallback is set to **false** it
will fill untranslated values as blanks

To set the default locale:

```php
<?php
$translatableListener->setDefaultLocale('en_us');
```

To set translation fallback:

```php
<?php
$translatableListener->setTranslationFallback(true); // default is false
```

**Note**: Default locale should be set on the **TranslatableListener** initialization
once, since it can impact your current records if it will be changed. As it
will not store extra record in translation table by default.

If you need to store translation in default locale, set:

```php
<?php
$translatableListener->setPersistDefaultLocaleTranslation(true); // default is false
```

This would always store translations in all locales, also keeping original record
translated field values in default locale set.

To set a default translation value upon a missing translation:

``` php
<?php
$translatableListener->setDefaultTranslationValue(''); // default is null
```

**Note**: By default the value is null, but it may cause a Type error for non-nullable getter upon a missing translation.

### Translation Entity

In some cases if there are thousands of records or even more.. we would like to
have a single table for translations of this Entity in order to increase the performance
on translation loading speed. This example will show how to specify a different Entity for
your translations by extending the mapped superclass.

ArticleTranslation Entity:

**Note:** this example is using annotations and attributes for mapping, you should use
one of them, not both.

```php
<?php
namespace Entity\Translation;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Translatable\Entity\MappedSuperclass\AbstractTranslation;
use Gedmo\Translatable\Entity\Repository\TranslationRepository;

/**
 * @ORM\Table(name="article_translations", indexes={
 *      @ORM\Index(name="article_translation_idx", columns={"locale", "object_class", "field", "foreign_key"})
 * })
 * @ORM\Entity(repositoryClass="Gedmo\Translatable\Entity\Repository\TranslationRepository")
 */
 #[ORM\Table(name: 'article_translations')]
 #[ORM\Index(name: 'article_translation_idx', columns: ['locale', 'object_class', 'field', 'foreign_key'])]
 #[ORM\Entity(repositoryClass: TranslationRepository::class)]
class ArticleTranslation extends AbstractTranslation
{
    /**
     * All required columns are mapped through inherited superclass
     */
}
```

**Note:** We specified the repository class to be used from extension.
It is handy for specific methods common to the Translation Entity

**Note:** This Entity will be used instead of default Translation Entity
only if we specify a class annotation `@Gedmo\TranslationEntity(class="my\translation\entity")`
or a class attribute `#[Gedmo\TranslationEntity(class: ArticleTranslation::class)]`

```php
<?php

use Doctrine\ORM\Mapping as ORM;
use Entity\Translation\ArticleTranslation;

/**
 * @ORM\Table(name="articles")
 * @ORM\Entity
 * @Gedmo\TranslationEntity(class="Entity\Translation\ArticleTranslation")
 */
#[ORM\Table(name: 'articles')]
#[ORM\Entity]
#[Gedmo\TranslationEntity(class: ArticleTranslation::class)]
class Article
{
    // ...
}
```

Now all translations of Article will be stored and queried from specific table

<a name="personal-translations"></a>

## Personal translations

Translatable has **AbstractPersonalTranslation** mapped superclass, which must
be extended and mapped based on your **entity** which you want to translate.
Note: translations are not automapped because of user preference based on cascades
or other possible choices, which user can make.
Personal translations uses foreign key constraint which is fully managed by ORM and
allows to have a collection of related translations. User can use it anyway he likes, etc.:
implementing array access on entity, using left join to fill collection and so on.

Note: that [query hint](#orm-query-hint) will work on personal translations the same way.
You can always use a left join like for standard doctrine collections.

Usage example (using both annotations and attributes, you should only use one of them):

```php
<?php
namespace Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Entity\CategoryTranslation;

/**
 * @ORM\Entity
 * @Gedmo\TranslationEntity(class="Entity\CategoryTranslation")
 */
 #[ORM\Entity]
 #[Gedmo\TranslationEntity(class: CategoryTranslation::class)]
class Category
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     */
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    private $id;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(length=64)
     */
    #[ORM\Column(length: 64)]
    #[Gedmo\Translatable]
    private $title;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(type="text", nullable=true)
     */
    #[ORM\Column(type: 'text', nullable: true)]
    #[Gedmo\Translatable]
    private $description;

    /**
     * @ORM\OneToMany(
     *   targetEntity="CategoryTranslation",
     *   mappedBy="object",
     *   cascade={"persist", "remove"}
     * )
     */
    #[ORM\OneToMany(targetEntity: CategoryTranslation::class, mappedBy: 'object', cascade: ['persist', 'remove'])]
    private $translations;

    public function __construct()
    {
        $this->translations = new ArrayCollection();
    }

    public function getTranslations()
    {
        return $this->translations;
    }

    public function addTranslation(CategoryTranslation $t)
    {
        if (!$this->translations->contains($t)) {
            $this->translations[] = $t;
            $t->setObject($this);
        }
    }

    public function getId()
    {
        return $this->id;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function __toString()
    {
        return $this->getTitle();
    }
}
```

Now the translation entity for the Category:

```php
<?php
namespace Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Translatable\Entity\MappedSuperclass\AbstractPersonalTranslation;

/**
 * @ORM\Entity
 * @ORM\Table(name="category_translations",
 *     uniqueConstraints={@ORM\UniqueConstraint(name="lookup_unique_idx", columns={
 *         "locale", "object_id", "field"
 *     })}
 * )
 */
#[ORM\Entity]
#[ORM\Table(name: 'category_translations')]
#[ORM\UniqueConstraint(name: 'lookup_unique_idx', columns: ['locale', 'object_id', 'field'])]
class CategoryTranslation extends AbstractPersonalTranslation
{
    /**
     * Convenient constructor
     *
     * @param string $locale
     * @param string $field
     * @param string $value
     */
    public function __construct($locale, $field, $value)
    {
        $this->setLocale($locale);
        $this->setField($field);
        $this->setContent($value);
    }

    /**
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="translations")
     * @ORM\JoinColumn(name="object_id", referencedColumnName="id", onDelete="CASCADE")
     */
    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'translations')]
    #[ORM\JoinColumn(name: 'object_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected $object;
}
```

Some example code to persist with translations:

```php
<?php
// assumes default locale is "en"
$food = new Entity\Category;
$food->setTitle('Food');
$food->addTranslation(new Entity\CategoryTranslation('lt', 'title', 'Maistas'));

$fruits = new Entity\Category;
$fruits->setParent($food);
$fruits->setTitle('Fruits');
$fruits->addTranslation(new Entity\CategoryTranslation('lt', 'title', 'Vaisiai'));
$fruits->addTranslation(new Entity\CategoryTranslation('ru', 'title', 'rus trans'));

$em->persist($food);
$em->persist($fruits);
$em->flush();
```

This would create translations for english and lithuanian, and for fruits, **ru** additionally.

Easy like that, any suggestions on improvements are very welcome


### Example code to use Personal Translations with (Symfony Sonata) i18n Forms:

Suppose you have a Sonata Backend with a simple form like:

```php
<?php
protected function configureFormFields(FormMapper $formMapper)    {
    $formMapper
        ->with('General')
        ->add('title', 'text')
        ->end()
    ;
}
```

Then you can turn it into an i18n Form by providing the following changes.

```php
<?php
protected function configureFormFields(FormMapper $formMapper)
{
    $formMapper
        ->with('General')
            ->add('title', 'translatable_field', array(
                'field'                => 'title',
                'personal_translation' => 'ExampleBundle\Entity\Translation\ProductTranslation',
                'property_path'        => 'translations',
            ))
        ->end()
    ;
}

```

To accomplish this you can add the following code in your bundle:

https://gist.github.com/2437078

<Bundle>/Form/TranslatedFieldType.php
<Bundle>/Form/EventListener/addTranslatedFieldSubscriber.php
<Bundle>/Resources/services.yml

Then you can change to your needs:

```php
    'field'                => 'title', //you need to provide which field you wish to translate
    'personal_translation' => 'ExampleBundle\Entity\Translation\ProductTranslation', //the personal translation entity
```


### Translations field type using Personal Translations with Symfony:

You can use [A2lixTranslationFormBundle](https://github.com/a2lix/TranslationFormBundle) to facilitate your translations.
