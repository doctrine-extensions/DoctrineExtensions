# Translatable behavior extension for Doctrine 2

**Translatable** behavior offers a very handy solution for translating specific record fields
in diferent languages. Further more, it loads the translations automatically for a locale
currently used, which can be set to **Translatable Listener** on it`s initialization or later
for other cases through the **Entity** itself
    
Features:

- Automatic storage of translations in database
- ORM and ODM support using same listener
- Automatic translation of Entity or Document fields then loaded
- ORM query can use **hint** to translate all records without issuing additional queries
- Can be nested with other behaviors
- Annotation, Yaml and Xml mapping support for extensions

[blog_test]: http://gediminasm.org/test "Test extensions on this blog"

Update **2011-04-21**

- Implemented multiple translation persistense through repository

Update **2011-04-16**

- Made an ORM query **hint** to hook into any select type query, which will join the translations
and let you **filter, order or search** by translated fields directly. It also will translate
all selected **collections or simple components** without issuing additional queries. It also
supports translation fallbacks
- For performance reasons, translation fallbacks are disabled by default

Update **2011-04-04**

- Made single listener, one instance can be used for any object manager
and any number of them

**Notice list:**

- You can [test live][blog_test] on this blog 
- Public [Translatable repository](http://github.com/l3pp4rd/DoctrineExtensions "Translatable extension on Github") is available on github
- Using other extensions on the same Entity fields may result in unexpected way
- May inpact your application performace since it does an additional query for translation
- Last update date: **2011-06-08**

**Portability:**

- **Translatable** is now available as [Bundle](http://github.com/stof/DoctrineExtensionsBundle)
ported to **Symfony2** by **Christophe Coevoet**, together with all other extensions

This article will cover the basic installation and functionality of **Translatable** behavior

Content:

- [Including](#including-extension) the extension
- [Attaching](#event-listener) the **Translatable Listener**
- Entity [example](#entity)
- Document [example](#document)
- [Yaml](#yaml) mapping example
- [Xml](#xml) mapping example
- Basic usage [examples](#basic-examples)
- [Persisting](#multi-translations) multiple translations
- Using ORM query [hint](#orm-query-hint)
- Advanced usage [examples](#advanced-examples)

## Setup and autoloading {#including-extension}

If you using the source from github repository, initial directory structure for
the extension library should look like this:

    ...
    /DoctrineExtensions
        /lib
            /Gedmo
                /Exception
                /Loggable
                /Mapping
                /Sluggable
                /Timestampable
                /Translatable
                /Tree
        /tests
            ...
    ...

First of all we need to setup the autoloading of extensions:

    $classLoader = new \Doctrine\Common\ClassLoader('Gedmo', "/path/to/library/DoctrineExtensions/lib");
    $classLoader->register();

This behavior requires an additional metadata path to be specified in order to have a translation
table and translation Entity or Document available. To configure it correctly you need to add new annotation
driver into driver chain with a specific location and namespace

### Translation metadata Annotation driver mapped into driver chain:

    $chainDriverImpl = new \Doctrine\ORM\Mapping\Driver\DriverChain();
    $yourDefaultDriverImpl = new \Doctrine\ORM\Mapping\Driver\YamlDriver('/yml/mapping/files');
    $translatableDriverImpl = $doctrineOrmConfig->newDefaultAnnotationDriver(
        '/path/to/library/DoctrineExtensions/lib/Gedmo/Translatable/Entity' // Document for ODM
    );
    $chainDriverImpl->addDriver($yourDefaultDriverImpl, 'Entity');
    $chainDriverImpl->addDriver($translatableDriverImpl, 'Gedmo\Translatable');
    $doctrineOrmConfig->setMetadataDriverImpl($chainDriverImpl);

**Notice:** there can be many annotation drivers in driver chain

**Notice:** Translation Entity or Document is required for storing all translations.

If you need a translation table per single Entity or Document, we will cover how to setup it later

### Attaching the Translation Listener to the event manager {#event-listener}

To attach the **Translation Listener** to your event system and to set the translation locale
to be used in global scope for all Entities or Documents:

    $evm = new \Doctrine\Common\EventManager();
    // ORM and ODM
    $translatableListener = new \Gedmo\Translatable\TranslationListener();
    
    $translatableListener->setTranslatableLocale('en_us');
    // in real world app the locale should be loaded from session, example:
    // Session::getInstance()->read('locale');
    $evm->addEventSubscriber($translatableListener);
    // now this event manager should be passed to entity manager constructor

### Translatable annotations:
- **@gedmo:Translatable** it will **translate** this field
- **@gedmo:TranslationEntity(class="my\class")** it will use this class to store **translations** generated
- **@gedmo:Locale or @gedmo:Language** this will identify this column as **locale** or **language**
used to override the global locale

## Translatable Entity example: {#entity}

**Notice:** that Translatable interface is not necessary, except in cases there
you need to identify entity as being Translatable. The metadata is loaded only once then
cache is activated

    namespace Entity;
    
    use Gedmo\Translatable\Translatable;
    /**
     * @Table(name="articles")
     * @Entity
     */
    class Article implements Translatable
    {
        /** @Id @GeneratedValue @Column(type="integer") */
        private $id;
    
        /**
         * @gedmo:Translatable
         * @Column(name="title", type="string", length=128)
         */
        private $title;
    
        /**
         * @gedmo:Translatable
         * @Column(name="content", type="text")
         */
        private $content;
    
        /**
         * @gedmo:Locale
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

## Translatable Document example: {#document}

    namespace Document;
    
    use Gedmo\Translatable\Translatable;
    /**
     * @Document(collection="articles")
     */
    class Article implements Translatable
    {
        /** @Id */
        private $id;
    
        /**
         * @gedmo:Translatable
         * @String
         */
        private $title;
    
        /**
         * @gedmo:Translatable
         * @String
         */
        private $content;
    
        /**
         * @gedmo:Locale
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

## Yaml mapping example {#yaml}

Yaml mapped Article: **/mapping/yaml/Entity.Article.dcm.yml**

    ---
    Entity\Article:
      type: entity
      table: articles
      gedmo:
        translation:
          locale: localeField
    # using specific personal translation class:
    #     entity: Translatable\Fixture\CategoryTranslation
      id:
        id:
          type: integer
          generator:
            strategy: AUTO
      fields:
        title:
          type: string
          length: 64
          gedmo:
            - translatable
        content:
          type: text
          gedmo:
            - translatable

## Xml mapping example {#xml}

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

## Basic usage examples: {#basic-examples}

Currently a global locale used for translations is "en_us" which was
set in **TranslationListener** globaly. To save article with its translations:

    $article = new Entity\Article;
    $article->setTitle('my title in en');
    $article->setContent('my content in en');
    $em->persist($article);
    $em->flush();

This inserted an article and inserted the translations for it in "en_us" locale
Now lets update our article in diferent locale:

    // first load the article
    $article = $em->find('Entity\Article', 1 /*article id*/);
    $article->setTitle('my title in de');
    $article->setContent('my content in de');
    $article->setTranslatableLocale('de_de'); // change locale
    $em->persist($article);
    $em->flush();

This updated an article and inserted the translations for it in "de_de" locale
To see and load all translations of **Translatable** Entity:

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

As far as our global locale is now "en_us" and updated article has "de_de" values.
Lets try to load it and it should be translated in English

    $article = $em->getRepository('Entity\Article')->find(1/* id of article */);
    echo $article->getTitle();
    // prints: "my title in en"
    echo $article->getContent();
    // prints: "my content in en"

## Persisting multiple translations {#multi-translations}

Usually it is more convinient to persist more translations when creating
or updating a record. **Translatable** allows to do that through translation repository.
All additional translations will be tracked by listener and when the flush will be executed,
it will update or persist all additional translations.

**Notice:** these translations will not be processed as ordinary fields of your object,
in case if you translate a **slug** additional translation will not know how to generate
the slug, so the value as an additional translation should be processed when creating it.

### Example of multiple translations:

    // persisting multiple translations, assume default locale is EN
    $repository = $em->getRepository('Gedmo\\Translatable\\Entity\\Translation');
    // it works for ODM also
    $article = new Article;
    $article->setTitle('My article en');
    $article->setContent('content en');

    $repository->translate($article, 'title', 'de', 'my article de')
        ->translate($article, 'content', 'de', 'content de')
        ->translate($article, 'title', 'ru', 'my article ru')
        ->translate($article, 'content', 'ru', 'content ru');

    $em->persist($article);
    $em->flush();

## Using ORM query hint {#orm-query-hint}

By default, behind the scenes, when you load a record - translatable hooks into **postLoad**
event and issues additional query to translate all fields. Imagine that when you load a collection,
when it issues a lot of queries just to translate those fields. Also if you want to hydrate
result as an **array**, it is not possible to hook any **postLoad** event since it is not an
entity being hydrated. These are the main reason why **TranslationWalker** was born.

**TranslationWalker** uses a query **hint** to hook into any **select type query**,
and when you execute the query, no matter which hydration method you use, it automatically
joins the translations for all fields, so you could use ordering filtering or whatever you
want on **translations of the fields** instead of original record fields.

And in result there is only one query for all this happyness.

If you use translation [fallbacks](#advanced-examples) it will be also in the same single
query and during the hydration process it will replace the empty fields in case if they
do not have a translation in currently used locale.

Now enough talking, here is an example:

    $dql = "SELECT a, c, u FROM Article a "
         . "LEFT JOIN a.comments c "
         . "JOIN c.author u "
         . "WHERE a.title LIKE '%translated_title%' "
         . "ORDER BY a.title";
    
    $query = $em->createQuery($dql);
    // set the translation query hint
    $query->setHint(
        \Doctrine\ORM\Query::HINT_CUSTOM_OUTPUT_WALKER,
        'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker'
    );
    
    $articles = $query->getResult(); // object hydration
    $articles = $query->getArrayResult(); // array hydration

And even a subselect:

    $subSelect = "SELECT a2.id FROM Article a2 "
        . "WHERE a2.title LIKE '%something_translated%'";
    $dql = "SELECT a, c, u FROM Article a "
        . "LEFT JOIN a.comments c "
        . "JOIN c.author u "
        . "WHERE a.id IN ({$subSelect}) "
        . "ORDER BY a.title";
    
    $query = $em->createQuery($dql);
    $query->setHint(
        \Doctrine\ORM\Query::HINT_CUSTOM_OUTPUT_WALKER,
        'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker'
    );
    
    $articles = $query->getResult(); // object hydration

Theres no need for any words anymore.. right?
I recommend you to use it extensively since it is a way better performance, even in
cases where you need to load single translated entity.

Notice: Even in **COUNT** select statements translations are joined to leave a
possibility to filter by translated field, if you do not need it, just do not set
the **hint**. Also take into account that it is not possibble to translate components
in **JOIN WITH** statement, example `JOIN a.comments c WITH c.message LIKE '%will_not_be_translated%'`

Notice: any **find** related method calls cannot hook this hint automagically, we
will use a different approach when **persister overriding feature** will be
available in **Doctrine** 

## Advanced examples: {#advanced-examples}

### Default locale

In some cases we need a default translation as a fallback if record does not have
a translation on globaly used locale. In that case Translation Listener takes the
current value of Entity. But there is a way to specify a **default locale**
which would force Entity to not update it`s field if current locale is not a default

To set the default locale:

    $translationListener->setDefaultLocale('en_us');

Default locale should be set on the **TranslationListener** initialization
once, since it can impact your current records if it will be changed.

### Translation Entity

In some cases if there are thousands of records or even more.. we would like to
have a single table for translations of this Entity in order to increase the performance
on translation loading speed. This example will show how to specify a diferent Entity for
your translations by extending the mapped superclass.

ArticleTranslation Entity:

    namespace Entity\Translation;
    
    use Gedmo\Translatable\Entity\AbstractTranslation;
    
    /**
     * @Table(name="article_translations", indexes={
     *      @index(name="article_translation_idx", columns={"locale", "objectClass", "foreign_key", "field"})
     * })
     * @Entity(repositoryClass="Gedmo\Translatable\Entity\Repository\TranslationRepository")
     */
    class ArticleTranslation extends AbstractTranslation
    {
        /**
         * All required columns are mapped through inherited superclass
         */
    }

**Notice:** We specified the repository class to be used from extension.
It is handy for specific methods common to the Translation Entity

**Notice:** This Entity will be used instead of default Translation Entity
only if we specify a class annotation @gedmo:TranslationEntity(class="my\translation\entity"):

    /**
     * @Table(name="articles")
     * @Entity
     * @gedmo:TranslationEntity(class="Entity\Translation\ArticleTranslation")
     */
    class Article
    {
        // ...
    }

Now all translations of Article will be stored and queried from specific table

Easy like that, any suggestions on improvements are very welcome
