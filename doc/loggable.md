# Loggable behavioral extension for Doctrine

**Loggable** behavior tracks your record changes and is able to
manage versions.

Features:

- Automatic storage of log entries in database
- ORM and ODM support using same listener
- Can be nested with other behaviors
- Objects can be reverted to previous versions
- Attributes, Annotation and Xml mapping support for extensions

This article will cover the basic installation and functionality of **Loggable**
behavior

Content:

- [Including](#including-extension) the extension
- Entity [example](#entity-mapping)
- Document [example](#document-mapping)
- [Xml](#xml-mapping) mapping example
- Basic usage [examples](#basic-examples)

<a name="including-extension"></a>

## Setup and autoloading

Read the [documentation](./annotations.md#em-setup)
or check the [example code](../example)
on how to setup and use the extensions in most optimized way.

### Loggable annotations:

- **@Gedmo\Mapping\Annotation\Loggable(logEntryClass="My\LoggableModel")** this class annotation will store logs to optionally
  specified **logEntryClass**. The class provided in this annotation MUST implement ``Gedmo\Loggable\LogEntryInterface``. You will
  still need to specify versioned fields with the following annotation.
- **@Gedmo\Mapping\Annotation\Versioned** tracks annotated property for changes

### Loggable attributes:

- **\#[Gedmo\Mapping\Annotation\Loggable(logEntryClass: My\LoggableModel::class]** this class attribute will store logs to optionally
  specified **logEntryClass**. The class provided in this attribute MUST implement ``Gedmo\Loggable\LogEntryInterface``. You will
  still need to specify versioned fields with the following attribute.
- **\#[Gedmo\Mapping\Annotation\Versioned]** tracks attributed property for changes

### Loggable username:

In order to set the username, when adding the loggable listener you need to set it this way:

```php
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

**Note:** this example is using annotations and attributes for mapping, you should use
one of them, not both.

```php
<?php
namespace Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @Gedmo\Loggable
 */
#[ORM\Entity]
#[Gedmo\Loggable]
class Article
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;

    /**
     * @Gedmo\Versioned
     * @ORM\Column(name="title", type="string", length=8)
     */
    #[Gedmo\Versioned]
    #[ORM\Column(name: 'title', type: Types::STRING, length: 8)]
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

```php
<?php
namespace Document;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Types\Type;

/**
 * @ODM\Document(collection="articles")
 * @Gedmo\Loggable
 */
#[Gedmo\Loggable]
#[ODM\Document(collection: 'articles')]
class Article
{
    /** @ODM\Id */
    #[ODM\Id]
    private $id;

    /**
     * @ODM\Field(type="string")
     * @Gedmo\Versioned
     */
    #[Gedmo\Versioned]
    #[ODM\Field(type: Type::STRING)]
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

<a name="xml-mapping"></a>

## Xml mapping example

```xml
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

<a name="custom-logentry-class"></a>

## Custom LogEntry class

```php
<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Loggable\Entity\MappedSuperclass\AbstractLogEntry;

/**
 * @ORM\Table(
 *     options={"row_format":"DYNAMIC"},
 *     indexes={
 *         @ORM\Index(name="log_class_lookup_idx", columns={"object_class"}),
 *         @ORM\Index(name="log_date_lookup_idx", columns={"logged_at"}),
 *         @ORM\Index(name="log_user_lookup_idx", columns={"username"}),
 *         @ORM\Index(name="log_version_lookup_idx", columns={"object_id", "object_class", "version"})
 *     }
 * )
 * @ORM\Entity()
 */
#[ORM\Entity]
#[ORM\Table(options: ['row_format' => 'DYNAMIC'])]
#[ORM\Index(name: 'log_class_lookup_idx', columns: ['object_class'])]
#[ORM\Index(name: 'log_date_lookup_idx', columns: ['logged_at'])]
#[ORM\Index(name: 'log_user_lookup_idx', columns: ['username'])]
#[ORM\Index(name: 'log_version_lookup_idx', columns: ['object_id', 'object_class', 'version'])]
class ParameterHistory extends AbstractLogEntry
{
    /*
     * All required columns are mapped through inherited superclass
     */
}
```

<a name="basic-examples"></a>

## Basic usage examples:

```php
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

```php
<?php
// first load the article
$article = $em->find('Entity\Article', 1 /*article id*/);
$article->setTitle('my new title');
$em->persist($article);
$em->flush();
```

This updated an article and inserted the logEntry for update action with new changeset
Now lets revert it to previous version:

```php
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
