# Loggable behavioral extension for Doctrine2

**Loggable** behavior tracks your record changes and is able to
manage versions.
    
Features:

- Automatic storage of log entries in database
- ORM and ODM support using same listener
- Can be nested with other behaviors
- Objects can be reverted to previous versions
- Annotation, Yaml and Xml mapping support for extensions

[blog_reference]: http://gediminasm.org/article/loggable-behavioral-extension-for-doctrine2 "Loggable extension for Doctrine2 tracks record changes and version management"
[blog_test]: http://gediminasm.org/test "Test extensions on this blog"

Update **2011-04-04**

- Made single listener, one instance can be used for any object manager
and any number of them


**Note:**

- You can [test live][blog_test] on this blog
- Public [Loggable repository](http://github.com/l3pp4rd/DoctrineExtensions "Loggable extension on Github") is available on github
- Last update date: **2012-01-02**

**Portability:**

- **Loggable** is now available as [Bundle](http://github.com/stof/StofDoctrineExtensionsBundle)
ported to **Symfony2** by **Christophe Coevoet**, together with all other extensions

This article will cover the basic installation and functionality of **Loggable**
behavior

Content:
    
- [Including](#including-extension) the extension
- Entity [example](#entity-mapping)
- Document [example](#document-mapping)
- [Yaml](#yaml-mapping) mapping example
- [Xml](#xml-mapping) mapping example
- Basic usage [examples](#basic-examples)

<a name="including-extension"></a>

## Setup and autoloading

Read the [documentation](http://github.com/l3pp4rd/DoctrineExtensions/blob/master/doc/annotations.md#em-setup)
or check the [example code](http://github.com/l3pp4rd/DoctrineExtensions/tree/master/example)
on how to setup and use the extensions in most optimized way.

### Loggable annotations:

- **@Gedmo\Mapping\Annotation\Loggable(logEntryClass="my\class")** this class annotation 
will use store logs to optionaly specified **logEntryClass**
- **@Gedmo\Mapping\Annotation\Versioned** tracks annotated property for changes

### Loggable username:

In order to set the username, when adding the loggeable listener you need to set it this way:

``` php
$loggableListener = new Gedmo\Loggable\LoggableListener;
$loggableListener->setAnnotationReader($cachedAnnotationReader);
$loggableListener->setUsername('admin');
$evm->addEventSubscriber($loggableListener);
```
<a name="entity-mapping"></a>

## Loggable Entity example:

**Note:** that Loggable interface is not necessary, except in cases there
you need to identify entity as being Loggable. The metadata is loaded only once when
cache is active

``` php
<?php
namespace Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @Entity
 * @Gedmo\Loggable
 */
class Article
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @Gedmo\Versioned
     * @ORM\Column(name="title", type="string", length=8)
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
```

<a name="document-mapping"></a>

## Loggable Document example:

``` php
<?php
namespace Document;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="articles")
 * @Gedmo\Loggable
 */
class Article
{
    /** @ODM\Id */
    private $id;

    /**
     * @ODM\String
     * @Gedmo\Versioned
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
```

<a name="yaml-mapping"></a>

## Yaml mapping example

Yaml mapped Article: **/mapping/yaml/Entity.Article.dcm.yml**

```
---
Entity\Article:
  type: entity
  table: articles
  gedmo:
    loggable:
# using specific personal LogEntryClass class:
      logEntryClass: My\LogEntry
# without specifying the LogEntryClass class:
#   loggable: true
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
```

<a name="xml-mapping"></a>

## Xml mapping example

``` xml
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
```

<a name="basic-examples"></a>

## Basic usage examples:

``` php
<?php
$article = new Entity\Article;
$article->setTitle('my title');
$em->persist($article);
$em->flush();
```

This inserted an article and inserted the logEntry for it, which contains
all new changeset. In case if there is **OneToOne or ManyToOne** relation,
it will store only identifier of that object to avoid storing proxies

Now lets update our article:

``` php
<?php
// first load the article
$article = $em->find('Entity\Article', 1 /*article id*/);
$article->setTitle('my new title');
$em->persist($article);
$em->flush();
```

This updated an article and inserted the logEntry for update action with new changeset
Now lets revert it to previous version:

``` php
<?php
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
```

Easy like that, any suggestions on improvements are very welcome
