# Sluggable behavior extension for Doctrine 2

**Sluggable** behavior will build the slug of predefined fields on a given field
which should store the slug

Features:

- Automatic predifined field transformation into slug
- ORM and ODM support using same listener
- Slugs can be unique and styled
- Can be nested with other behaviors
- Annotation, Yaml and Xml mapping support for extensions

[blog_test]: http://gediminasm.org/test "Test extensions on this blog"

Update **2011-04-04**

- Made single listener, one instance can be used for any object manager and any number of them

Update **2010-12-23**

- Full support for unique index on slug field,
no more exceptions during concurrent flushes.

**Notice:**

- You can [test live][blog_test] on this blog
- Public [Sluggable repository](http://github.com/l3pp4rd/DoctrineExtensions "Sluggable extension on Github") is available on github
- Last update date: **2011-06-08**

**Portability:**

- **Sluggable** is now available as [Bundle](http://github.com/stof/DoctrineExtensionsBundle)
ported to **Symfony2** by **Christophe Coevoet**, together with all other extensions

This article will cover the basic installation and functionality of **Sluggable**
behavior

Content:
    
- [Including](#including-extension) the extension
- [Attaching](#event-listener) the **Sluggable Listener**
- Entity [example](#entity)
- Document [example](#document)
- [Yaml](#yaml) mapping example
- [Xml](#xml) mapping example
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

### Attaching the Sluggable Listener to the event manager {#event-listener}

To attach the **Sluggable Listener** to your event system:

    $evm = new \Doctrine\Common\EventManager();
    // ORM and ORM
    $sluggableListener = new \Gedmo\Sluggable\SluggableListener();
    
    $evm->addEventSubscriber($sluggableListener);
    // now this event manager should be passed to entity manager constructor

## Sluggable Entity example: {#entity}

### Sluggable annotations:

- **@gedmo:Sluggable** it will include this field into **slug** generation
- **@gedmo:Slug** it will use this column to store **slug** generated

**Notice:** that Sluggable interface is not necessary, except in cases there
you need to identify entity as being Sluggable. The metadata is loaded only once then
cache is activated

    namespace Entity;
    
    /**
     * @Table(name="articles")
     * @Entity
     */
    class Article
    {
        /** @Id @GeneratedValue @Column(type="integer") */
        private $id;
    
        /**
         * @gedmo:Sluggable
         * @Column(name="title", type="string", length=64)
         */
        private $title;
    
        /**
         * @gedmo:Sluggable
         * @Column(name="code", type="string", length=16)
         */
        private $code;
    
        /**
         * @gedmo:Slug
         * @Column(name="slug", type="string", length=128, unique=true)
         */
        private $slug;
    
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
    
        public function setCode($code)
        {
            $this->code = $code;
        }
    
        public function getCode()
        {
            return $this->code;
        }
    
        public function getSlug()
        {
            return $this->slug;
        }
    }

## Sluggable Document example: {#document}

    namespace Document;
    
    /**
     * @Document(collection="articles")
     */
    class Article
    {
        /** @Id */
        private $id;
    
        /**
         * @gedmo:Sluggable
         * @String
         */
        private $title;
    
        /**
         * @gedmo:Sluggable
         * @String
         */
        private $code;
    
        /**
         * @gedmo:Slug
         * @String
         */
        private $slug;
    
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
    
        public function setCode($code)
        {
            $this->code = $code;
        }
    
        public function getCode()
        {
            return $this->code;
        }
    
        public function getSlug()
        {
            return $this->slug;
        }
    }

## Yaml mapping example {#yaml}

Yaml mapped Article: **/mapping/yaml/Entity.Article.dcm.yml**

    ---
    Entity\Article:
      type: entity
      table: articles
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
            - sluggable
        code:
          type: string
          length: 16
          gedmo:
            - sluggable
        slug:
          type: string
          length: 128
          gedmo:
            slug:
              separator: _
              style: camel
    # or simply:
    #       - slug
      indexes:
        search_idx:
          columns: slug

## Xml mapping example {#xml}

    <?xml version="1.0" encoding="UTF-8"?>
    <doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
                      xmlns:gedmo="http://gediminasm.org/schemas/orm/doctrine-extensions-mapping">
        <entity name="Mapping\Fixture\Xml\Sluggable" table="sluggables">
            <id name="id" type="integer" column="id">
                <generator strategy="AUTO"/>
            </id>
    
            <field name="title" type="string" length="128">
                <gedmo:sluggable position="0"/>
            </field>
            <field name="code" type="string" length="16">
                <gedmo:sluggable/>
            </field>
            <field name="ean" type="string" length="13">
                <gedmo:sluggable position="1"/>
            </field>
            <field name="slug" type="string" length="156" unique="true">
                <gedmo:slug unique="true" style="camel" updatable="false" separator="_"/>
            </field>
        </entity>
    </doctrine-mapping>

## Basic usage examples: {#basic-examples}

### To save **Article** and generate slug simply use:

    $article = new Article();
    $article->setTitle('the title');
    $article->setCode('my code');
    $this->em->persist($article);
    $this->em->flush();
    
    echo $article->getSlug();
    // prints: the-title-my-code

### Some other configuration options:

- **updatable** (optional, default=true) - **true** to update the slug on sluggable field changes, **false** - otherwise
- **unique** (optional, default=true) - **true** if slug should be unique and if identical it will be prefixed, **false** - otherwise
- **separator** (optional, default="-") - separator which will separate words in slug
- **style** (optional, default="default") - **"default"** all letters will be lowercase, **"camel"** - first word letter will be uppercase

    class Article
    {
        // ...
        /**
         * @gedmo:Slug(style="camel", separator="_", updatable=false, unique=false)
         * @Column(name="slug", type="string", length=128, unique=true)
         */
        private $slug;
        // ...
    }

And now test the result:

    $article = new Article();
    $article->setTitle('the title');
    $article->setCode('my code');
    $this->em->persist($article);
    $this->em->flush();
    
    echo $article->getSlug();
    // prints: The_Title_My_Code

## Advanced examples: {#advanced-examples}

### Using TranslationListener to translate our slug

If you want to attach **TranslationListener** also add it to EventManager after
the **SluggableListener**. It is important because slug must be generated first
before the creation of it`s translation.

    $evm = new \Doctrine\Common\EventManager();
    $sluggableListener = new \Gedmo\Sluggable\SluggableListener();
    $evm->addEventSubscriber($sluggableListener);
    $translatableListener = new \Gedmo\Translatable\TranslationListener();
    $translatableListener->setTranslatableLocale('en_us');
    $evm->addEventSubscriber($translatableListener);
    // now this event manager should be passed to entity manager constructor

And the Entity should look like:

    namespace Entity;
    
    /**
     * @Table(name="articles")
     * @Entity
     */
    class Article
    {
        /** @Id @GeneratedValue @Column(type="integer") */
        private $id;
    
        /**
         * @gedmo:Translatable
         * @gedmo:Sluggable
         * @Column(name="title", type="string", length=64)
         */
        private $title;
    
        /**
         * @gedmo:Translatable
         * @gedmo:Sluggable
         * @Column(name="code", type="string", length=16)
         */
        private $code;
    
        /**
         * @gedmo:Translatable
         * @gedmo:Slug
         * @Column(name="slug", type="string", length=128, unique=true)
         */
        private $slug;
    
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
    
        public function setCode($code)
        {
            $this->code = $code;
        }
    
        public function getCode()
        {
            return $this->code;
        }
    
        public function getSlug()
        {
            return $this->slug;
        }
    }

Now the generated slug will be translated by Translatable behavior

Easy like that, any suggestions on improvements are very welcome
