# Sluggable behavior extension for Doctrine 2

**Sluggable** behavior will build the slug of predefined fields on a given field
which should store the slug

Features:

- Automatic predifined field transformation into slug
- ORM and ODM support using same listener
- Slugs can be unique and styled
- Can be nested with other behaviors
- Annotation, Yaml and Xml mapping support for extensions
- Multiple slugs, diferent slugs can link to same fields
- Built-in slug handlers, for tree path based slugs or linked by relation

[blog_test]: http://gediminasm.org/test "Test extensions on this blog"

Update **2010-09-11**

- Refactored sluggable for doctrine2.2 by specifieng slug fields directly in slug annotation
- Slug handler functionality, possibility to create custom ones or use built-in
tree path handler or linked slug through single valued association
- Updated documentation mapping examples for 2.1.x version or higher

Update **2011-04-04**

- Made single listener, one instance can be used for any object manager and any number of them

Update **2010-12-23**

- Full support for unique index on slug field,
no more exceptions during concurrent flushes.

**Notice:**

- You can [test live][blog_test] on this blog
- Public [Sluggable repository](http://github.com/l3pp4rd/DoctrineExtensions "Sluggable extension on Github") is available on github
- Last update date: **2011-09-11**

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
- Using [slug handlers](#slug-handlers)

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
    // ORM and ODM
    $sluggableListener = new \Gedmo\Sluggable\SluggableListener();
    
    $evm->addEventSubscriber($sluggableListener);
    // now this event manager should be passed to entity manager constructor

## Sluggable Entity example: {#entity}

### Sluggable annotations:

- **@Gedmo\Mapping\Annotation\Slug** it will use this column to store **slug** generated
**fields** option must be specified, an array of field names to slug

**Note:** that Sluggable interface is not necessary, except in cases there
you need to identify entity as being Sluggable. The metadata is loaded only once then
cache is activated

**Note:** 2.1.x version of extensions used @Gedmo\Mapping\Annotation\Sluggable to identify
the field for slug

    namespace Entity;
    
    use Gedmo\Mapping\Annotation as Gedmo;
    use Doctrine\ORM\Mapping as ORM;
    
    /**
     * @ORM\Table(name="articles")
     * @ORM\Entity
     */
    class Article
    {
        /** 
         * @ORM\Id
         * @ORM\GeneratedValue
         * @ORM\Column(type="integer")
         */
        private $id;
    
        /**
         * @ORM\Column(length=64)
         */
        private $title;
    
        /**
         * @ORM\Column(length=16)
         */
        private $code;
    
        /**
         * @Gedmo\Slug(fields={"title", "code"})
         * @ORM\Column(length=128, unique=true)
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
    
    use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
    use Gedmo\Mapping\Annotation as Gedmo;
    
    /**
     * @ODM\Document(collection="articles")
     */
    class Article
    {
        /** 
         * @ODM\Id
         */
        private $id;
    
        /**
         * @ODM\String
         */
        private $title;
    
        /**
         * @ODM\String
         */
        private $code;
    
        /**
         * @Gedmo\Slug(fields={"title", "code"})
         * @ODM\String
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
        code:
          type: string
          length: 16
        slug:
          type: string
          length: 128
          gedmo:
            slug:
              separator: _
              style: camel
              fields:
                - title
                - code
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
    
            <field name="title" type="string" length="128"/>
            <field name="code" type="string" length="16"/>
            <field name="ean" type="string" length="13"/>
            <field name="slug" type="string" length="156" unique="true">
                <gedmo:slug unique="true" style="camel" updatable="false" separator="_">
                    <fields>
                        <field>title</field>
                        <field>code</field>
                        <field>ean</field>
                    </fields>
                </gedmo:slug>
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

### Some other configuration options for **slug** annotation:

- **fields** (required, default=[]) - list of fields for slug
- **updatable** (optional, default=true) - **true** to update the slug on sluggable field changes, **false** - otherwise
- **unique** (optional, default=true) - **true** if slug should be unique and if identical it will be prefixed, **false** - otherwise
- **separator** (optional, default="-") - separator which will separate words in slug
- **style** (optional, default="default") - **"default"** all letters will be lowercase, **"camel"** - first word letter will be uppercase
- **handlers** (optional, default=[]) - list of slug handlers, like tree path slug, or customized, for example see bellow

**TreeSlugHandler**

    /**
     * @Gedmo\Mapping\Annotation\Slug(handlers={
     *      @Gedmo\Mapping\Annotation\SlugHandler(class="Gedmo\Sluggable\Handler\TreeSlugHandler", options={
     *          @Gedmo\Mapping\Annotation\SlugHandlerOption(name="parentRelationField", value="parent"),
     *          @Gedmo\Mapping\Annotation\SlugHandlerOption(name="separator", value="/")
     *      })
     * }, fields={"title", "code"})
     * @Doctrine\ORM\Mapping\Column(length=64, unique=true)
     */
    private $slug;

**RelativeSlugHandler**:

    /**
     * Person domain object class
     *
     * @Gedmo\Mapping\Annotation\Slug(handlers={
     *      @Gedmo\Mapping\Annotation\SlugHandler(class="Gedmo\Sluggable\Handler\RelativeSlugHandler", options={
     *          @Gedmo\Mapping\Annotation\SlugHandlerOption(name="relationField", value="category"),
     *          @Gedmo\Mapping\Annotation\SlugHandlerOption(name="relationSlugField", value="slug"),
     *          @Gedmo\Mapping\Annotation\SlugHandlerOption(name="separator", value="/")
     *      })
     * }, fields={"title", "code"})
     * @Doctrine\ORM\Mapping\Column(length=64, unique=true)
     */
    private $slug;

**Note:** if you used **RelativeSlugHandler** - relation object should use in order to sync changes:

**InversedRelativeSlugHandler**

    /**
     * Category domain object class
     *
     * @Gedmo\Mapping\Annotation\Slug(handlers={
     *      @Gedmo\Mapping\Annotation\SlugHandler(class="Gedmo\Sluggable\Handler\InversedRelativeSlugHandler", options={
     *          @Gedmo\Mapping\Annotation\SlugHandlerOption(name="relationClass", value="App\Entity\Person"),
     *          @Gedmo\Mapping\Annotation\SlugHandlerOption(name="mappedBy", value="category"),
     *          @Gedmo\Mapping\Annotation\SlugHandlerOption(name="inverseSlugField", value="slug")
     *      })
     * }, fields={"title"})
     * @Doctrine\ORM\Mapping\Column(length=64, unique=true)
     */
    private $slug;

### Example
    
    class Article
    {
        // ...
        /**
         * @Gedmo\Slug(fields={"title"}, style="camel", separator="_", updatable=false, unique=false)
         * @Doctrine\ORM\Mapping\Column(length=128, unique=true)
         */
        private $slug;
        // ...
 
        // ...
        /**
         * @Doctrine\ORM\Mapping\Column(length=128)
         */
        private $title;
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
    
    use Gedmo\Mapping\Annotation as Gedmo;
    use Doctrine\ORM\Mapping as ORM;
    
    /**
     * @ORM\Table(name="articles")
     * @ORM\Entity
     */
    class Article
    {
        /** 
         * @ORM\Id
         * @ORM\GeneratedValue
         * @ORM\Column(type="integer")
         */
        private $id;
    
        /**
         * @Gedmo\Translatable
         * @ORM\Column(length=64)
         */
        private $title;
    
        /**
         * @Gedmo\Translatable
         * @ORM\Column(length=16)
         */
        private $code;
        
        /**
         * @Gedmo\Translatable
         * @Gedmo\Slug(fields={"title", "code"})
         * @ORM\Column(length=128, unique=true)
         */
        private $slug;
        
        /**
         * @ORM\Column(type="string", length=64)
         */
        private $uniqueTitle;
        
        /**
         * @Gedmo\Slug(fields={"uniqueTitle"})
         * @ORM\Column(type="string", length=128, unique=true)
         */
        private $uniqueSlug;
    
        
    
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
        
        public function getUniqueSlug()
        {
            return $this->uniqueSlug;
        }
    }

Now the generated slug will be translated by Translatable behavior

## Using slug handlers: {#slug-handlers}

There are built-in slug handlers like described in configuration options of slug, but there
can be also customized slug handlers depending on use cases. Usually the most logic use case
is for related slug. For instance if user has a **ManyToOne relation to a **Company** we
would like to have a url like **http://example.com/knplabs/gedi where **KnpLabs**
is a company and user name is **Gedi**. In this case relation has a path separator **/**

Easy like that, any suggestions on improvements are very welcome
