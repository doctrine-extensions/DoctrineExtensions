<h1>Translatable behavior extension for Doctrine 2</h1>

**Translatable** behavior offers a very handy solution for translating specific record fields
in diferent languages. Further more, it loads the translations automatically for a locale
currently used, which can be set to **Translatable Listener** on it`s initialization or later
for other cases through the **Entity** itself
    
Features:

- Automatic storage of translations in database
- ORM and ODM support using same listener -Automatic translation of Entity or
Document fields then loaded
- Can be nested with other behaviors
- Annotation and Yaml mapping support for extensions

[blog_test]: http://gediminasm.org/test "Test extensions on this blog"

Update **2011-04-04**
- Made single listener, one instance can be used for any object manager
and any number of them

**Notice list:**

- You can [test live][blog_test] on this blog 
- Public [Translatable repository](http://github.com/l3pp4rd/DoctrineExtensions "Translatable extension on Github") is available on github
- Using other extensions on the same Entity fields may result in unexpected way
- May inpact your application performace since it does an additional query for translation
- Last update date: **2011-04-04**

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
- Basic usage [examples](#basic-examples)
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

Easy like that, any sugestions on improvements are very welcome
