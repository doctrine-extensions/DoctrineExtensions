# Loggable behavioral extension for Doctrine2

**Loggable** behavior tracks your record changes and is able to
manage versions.
    
Features:

- Automatic storage of log entries in database
- ORM and ODM support using same listener
- Can be nested with other behaviors
- Objects can be reverted to previous versions
- Annotation, Yaml and Xml mapping support for extensions

[blog_test]: http://gediminasm.org/test "Test extensions on this blog"

Update **2011-04-04**

- Made single listener, one instance can be used for any object manager
and any number of them


**Notice:**

- You can [test live][blog_test] on this blog
- Public [Loggable repository](http://github.com/l3pp4rd/DoctrineExtensions "Loggable extension on Github") is available on github
- Last update date: **2011-06-08**

**Portability:**

- **Loggable** is now available as [Bundle](http://github.com/stof/DoctrineExtensionsBundle)
ported to **Symfony2** by **Christophe Coevoet**, together with all other extensions

This article will cover the basic installation and functionality of **Loggable**
behavior

Content:
    
- [Including](#including-extension) the extension
- [Attaching](#event-listener) the **Loggable Listener**
- Entity [example](#entity)
- Document [example](#document)
- [Yaml](#yaml) mapping example
- [Xml](#xml) mapping example
- Basic usage [examples](#basic-examples)

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

This behavior requires an additional metadata path to be specified in order to have a logEntry
table for log entries. To configure it correctly you need to add new annotation
driver into driver chain with a specific location and namespace

### Loggable metadata Annotation driver mapped into driver chain:

    $chainDriverImpl = new \Doctrine\ORM\Mapping\Driver\DriverChain();
    $yourDefaultDriverImpl = new \Doctrine\ORM\Mapping\Driver\YamlDriver('/yml/mapping/files');
    $loggableDriverImpl = $doctrineOrmConfig->newDefaultAnnotationDriver(
        '/path/to/library/DoctrineExtensions/lib/Gedmo/Loggable/Entity' // Document for ODM
    );
    $chainDriverImpl->addDriver($yourDefaultDriverImpl, 'Entity');
    $chainDriverImpl->addDriver($loggableDriverImpl, 'Gedmo\Loggable');
    $doctrineOrmConfig->setMetadataDriverImpl($chainDriverImpl);

**Notice:** there can be many annotation drivers in driver chain

**Notice:** Loggable Entity or Document is required for storing all logs.

If you need a log entry table per single Entity or Document, we will cover how to setup it later

### Attaching the Loggable Listener to the event manager {#event-listener}

    $evm = new \Doctrine\Common\EventManager();
    // ORM and ODM
    $loggableListener = new \Gedmo\Loggable\LoggableListener();
    
    $loggableListener->setUsername('currently_loggedin_user');
    // in real world app the username should be loaded from session, example:
    // Session::getInstance()->read('user')->getUsername();
    $evm->addEventSubscriber($loggableListener);
    // now this event manager should be passed to entity manager constructor

### Loggable annotations:

- **@gedmo:Loggable(logEntryClass="my\class")** this class annotation 
will use store logs to optionaly specified **logEntryClass**
- **@gedmo:Versioned** tracks annotated property for changes

## Loggable Entity example: {#entity}

**Notice:** that Loggable interface is not necessary, except in cases there
you need to identify entity as being Loggable. The metadata is loaded only once when
cache is active

    namespace Entity;
    
    /**
     * @Entity
     * @gedmo:Loggable
     */
    class Article
    {
        /**
         * @Column(name="id", type="integer")
         * @Id
         * @GeneratedValue(strategy="IDENTITY")
         */
        private $id;
    
        /**
         * @gedmo:Versioned
         * @Column(name="title", type="string", length=8)
         */
        private $title;
    
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

## Loggable Document example: {#document}

    namespace Document;
    
    /**
     * @Document(collection="articles")
     * @gedmo:Loggable
     */
    class Article
    {
        /** @Id */
        private $id;
    
        /**
         * @String
         * @gedmo:Versioned
         */
        private $title;
    
        public function __toString()
        {
            return $this->title;
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
    }

## Yaml mapping example {#yaml}

Yaml mapped Article: **/mapping/yaml/Entity.Article.dcm.yml**

    ---
    Entity\Article:
      type: entity
      table: articles
      gedmo:
        loggable:
    # using specific personal LogEntryClass class:
          logEntryClass: My\LogEntry
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
            - versioned
        content:
          type: text

## Xml mapping example {#xml}

    <?xml version="1.0" encoding="UTF-8"?>
    <doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
                      xmlns:gedmo="http://gediminasm.org/schemas/orm/doctrine-extensions-mapping">
    
        <entity name="Mapping\Fixture\Xml\Loggable" table="loggables">
    
            <id name="id" type="integer" column="id">
                <generator strategy="AUTO"/>
            </id>
    
            <field name="title" type="string" length="128">
                <gedmo:versioned/>
            </field>
            <many-to-one field="status" target-entity="Status">
                <join-column name="status_id" referenced-column-name="id"/>
                <gedmo:versioned/>
            </many-to-one>
    
            <gedmo:loggable log-entry-class="Gedmo\Loggable\Entity\LogEntry"/>
    
        </entity>
    
    </doctrine-mapping>

## Basic usage examples: {#basic-examples}

    $article = new Entity\Article;
    $article->setTitle('my title');
    $em->persist($article);
    $em->flush();

This inserted an article and inserted the logEntry for it, which contains
all new changeset. In case if there is **OneToOne or ManyToOne** relation,
it will store only identifier of that object to avoid storing proxies

Now lets update our article:

    // first load the article
    $article = $em->find('Entity\Article', 1 /*article id*/);
    $article->setTitle('my new title');
    $em->persist($article);
    $em->flush();

This updated an article and inserted the logEntry for update action with new changeset
Now lets revert it to previous version:

    // first check our log entries
    $repo = $em->getRepository('Gedmo\Loggable\Entity\LogEntry'); // we use default log entry class
    $article = $em->find('Entity\Article', 1 /*article id*/);
    $logs = $repo->getLogEntries($article);
    /* $logs contains 2 logEntries */
    // lets revert to first version
    $repo->revert($article, 1/*version*/);
    // notice article is not persisted yet, you need to persist and flush it
    echo $article->getTitle(); // prints "my title"
    $em->persist($article);
    $em->flush();
    // if article had changed relation, it would be reverted also.

Easy like that, any suggestions on improvements are very welcome
