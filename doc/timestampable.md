# Timestampable behavior extension for Doctrine 2

**Timestampable** behavior will automate the update of date fields
on your Entities or Documents. It works through annotations and can update 
fields on creation, update or even on specific property value change.

Features:

- Automatic predifined date field update on creation, update and even on record property changes
- ORM and ODM support using same listener
- Specific annotations for properties, and no interface required
- Can react to specific property or relation changes to specific value 
- Can be nested with other behaviors 
- Annotation, Yaml and Xml mapping support for extensions

[blog_test]: http://gediminasm.org/test "Test extensions on this blog"

Update **2011-04-04**
 
- Made single listener, one instance can be used for any object manager
and any number of them

**Notice list:**

- You can [test live][blog_test] on this blog
- Public [Timestampable repository](http://github.com/l3pp4rd/DoctrineExtensions "Timestampable extension on Github") is available on github
- Last update date: **2011-06-08**

**Portability:**

- **Timestampable** is now available as [Bundle](http://github.com/stof/DoctrineExtensionsBundle)
ported to **Symfony2** by **Christophe Coevoet**, together with all other extensions

This article will cover the basic installation and functionality of **Timestampable** behavior

Content:

- [Including](#including-extension) the extension
- [Attaching](#event-listener) the **Sluggable Listener**
- Entity [example](#entity)
- Document [example](#document)
- [Yaml](#yaml) mapping example
- [Xml](#xml) mapping example
- Advanced usage [examples](#advanced-examples)

## Setup and autoloading {#including-extension}

If you using the source from github repository, initial directory structure for
the extension library should look like this:

    ...
    /DoctrineExtensions
        /lib
            /Gedmo
                /Exception
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

### Attaching the Timestampable Listener to the event manager {#event-listener}

To attach the **Timestampable Listener** to your event system:

    $evm = new \Doctrine\Common\EventManager();
    // ORM and ORM
    $timestampableListener = new \Gedmo\Timestampable\TimestampableListener();
    
    $evm->addEventSubscriber($timestampableListener);
    // now this event manager should be passed to entity manager constructor

## Timestampable Entity example: {#entity}

### Timestampable annotations:
- **@gedmo:Timestampable** this annotation tells that this column is timestampable
by default it updates this column on update. If column is not date, datetime or time
type it will trigger an exception.

Available configuration options:
- **on** - is main option and can be **create, update, change** this tells when it 
should be updated
- **field** - only valid if **on="change"** is specified, tracks property for changes
- **value** - only valid if **on="change"** is specified, if tracked field has this **value** 
then it updates timestamp

**Notice:** that Timestampable interface is not necessary, except in cases there
you need to identify entity as being Timestampable. The metadata is loaded only once then
cache is activated

    namespace Entity;
    
    /**
     * @Entity
     */
    class Article
    {
        /** @Id @GeneratedValue @Column(type="integer") */
        private $id;
    
        /**
         * @Column(type="string", length=128)
         */
        private $title;
    
        /**
         * @var datetime $created
         *
         * @gedmo:Timestampable(on="create")
         * @Column(type="date")
         */
        private $created;
    
        /**
         * @var datetime $updated
         *
         * @Column(type="datetime")
         * @gedmo:Timestampable(on="update")
         */
        private $updated;
    
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
    
        public function getCreated()
        {
            return $this->created;
        }
    
        public function getUpdated()
        {
            return $this->updated;
        }
    }

## Timestampable Document example: {#document}

    namespace Document;
    
    /**
     * @Document(collection="articles")
     */
    class Article
    {
        /** @Id */
        private $id;
    
        /**
         * @String
         */
        private $title;
    
        /**
         * @var timestamp $created
         *
         * @Timestamp
         * @gedmo:Timestampable(on="create")
         */
        private $created;
    
        /**
         * @var date $updated
         *
         * @Date
         * @gedmo:Timestampable
         */
        private $updated;
    
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
    
        public function getCreated()
        {
            return $this->created;
        }
    
        public function getUpdated()
        {
            return $this->updated;
        }
    }

Now on update and creation these annotated fields will be automatically updated

## Yaml mapping example: {#yaml}

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
        created:
          type: date
          gedmo:
            timestampable:
              on: create
        updated:
          type: datetime
          gedmo:
            timestampable:
              on: update

## Xml mapping example {#xml}

    <?xml version="1.0" encoding="UTF-8"?>
    <doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
                      xmlns:gedmo="http://gediminasm.org/schemas/orm/doctrine-extensions-mapping">
    
        <entity name="Mapping\Fixture\Xml\Timestampable" table="timestampables">
            <id name="id" type="integer" column="id">
                <generator strategy="AUTO"/>
            </id>
    
            <field name="created" type="datetime">
                <gedmo:timestampable on="create"/>
            </field>
            <field name="updated" type="datetime">
                <gedmo:timestampable on="update"/>
            </field>
            <field name="published" type="datetime" nullable="true">
                <gedmo:timestampable on="change" field="status.title" value="Published"/>
            </field>
    
            <many-to-one field="status" target-entity="Status">
                <join-column name="status_id" referenced-column-name="id"/>
            </many-to-one>
        </entity>
    
    </doctrine-mapping>

## Advanced examples: {#advanced-examples}

### Using dependency of property changes

Add another entity which would represent Article Type:

    namespace Entity;
    
    /**
     * @Entity
     */
    class Type
    {
        /** @Id @GeneratedValue @Column(type="integer") */
        private $id;
    
        /**
         * @Column(type="string", length=128)
         */
        private $title;
    
        /**
         * @OneToMany(targetEntity="Article", mappedBy="type")
         */
        private $articles;
    
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
    }

Now update the Article Entity to reflect published date on Type change:

    namespace Entity;
    
    /**
     * @Entity
     */
    class Article
    {
        /** @Id @GeneratedValue @Column(type="integer") */
        private $id;
    
        /**
         * @Column(type="string", length=128)
         */
        private $title;
    
        /**
         * @var datetime $created
         *
         * @gedmo:Timestampable(on="create")
         * @Column(type="date")
         */
        private $created;
    
        /**
         * @var datetime $updated
         *
         * @Column(type="datetime")
         * @gedmo:Timestampable(on="update")
         */
        private $updated;
    
        /**
         * @ManyToOne(targetEntity="Type", inversedBy="articles")
         */
        private $type;
    
        /**
         * @var datetime $published
         *
         * @Column(type="datetime", nullable=true)
         * @gedmo:Timestampable(on="change", field="type.title", value="Published")
         */
        private $published;
    
        public function setType($type)
        {
            $this->type = $type;
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
    
        public function getCreated()
        {
            return $this->created;
        }
    
        public function getUpdated()
        {
            return $this->updated;
        }
    
        public function getPublished()
        {
            return $this->published;
        }
    }

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
        created:
          type: date
          gedmo:
            timestampable:
              on: create
        updated:
          type: datetime
          gedmo:
            timestampable:
              on: update
        published:
          type: datetime
          gedmo:
            timestampable:
              on: change
              field: type.title
              value: Published
      manyToOne:
        type:
          targetEntity: Entity\Type
          inversedBy: articles

Now few operations to get it all done:

    $article = new Article;
    $article->setTitle('My Article');
    
    $em->persist($article);
    $em->flush();
    // article: $created, $updated were set
    
    $type = new Type;
    $type->setTitle('Published');
    
    $article = $em->getRepository('Entity\Article')->findByTitle('My Article');
    $article->setType($type);
    
    $em->persist($article);
    $em->persist($type);
    $em->flush();
    // article: $published, $updated were set
    
    $article->getPublished()->format('Y-m-d'); // the date article type changed to published

Easy like that, any suggestions on improvements are very welcome
