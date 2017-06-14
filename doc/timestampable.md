# Timestampable behavior extension for Doctrine 2

**Timestampable** behavior will automate the update of date fields
on your Entities or Documents. It works through annotations and can update
fields on creation, update, property subset update, or even on specific property value change.

Features:

- Automatic predefined date field update on creation, update, property subset update, and even on record property changes
- ORM and ODM support using same listener
- Specific annotations for properties, and no interface required
- Can react to specific property or relation changes to specific value
- Can be nested with other behaviors
- Annotation, Yaml and Xml mapping support for extensions

Update **2012-06-26**

- Allow multiple values for on="change"

Update **2012-03-10**

- Add [Timestampable traits](#traits)

Update **2011-04-04**

- Made single listener, one instance can be used for any object manager
and any number of them

**Note:**
- Last update date: **2012-01-02**

**Portability:**

- **Timestampable** is now available as [Bundle](http://github.com/stof/StofDoctrineExtensionsBundle)
ported to **Symfony2** by **Christophe Coevoet**, together with all other extensions

This article will cover the basic installation and functionality of **Timestampable** behavior

Content:

- [Including](#including-extension) the extension
- Entity [example](#entity-mapping)
- Document [example](#document-mapping)
- [Yaml](#yaml-mapping) mapping example
- [Xml](#xml-mapping) mapping example
- Advanced usage [examples](#advanced-examples)
- Using [Traits](#traits)

<a name="including-extension"></a>

## Setup and autoloading

Read the [documentation](http://github.com/Atlantic18/DoctrineExtensions/blob/master/doc/annotations.md#em-setup)
or check the [example code](http://github.com/Atlantic18/DoctrineExtensions/tree/master/example)
on how to setup and use the extensions in most optimized way.

<a name="entity-mapping"></a>

## Timestampable Entity example:

### Timestampable annotations:
- **@Gedmo\Mapping\Annotation\Timestampable** this annotation tells that this column is timestampable
by default it updates this column on update. If column is not date, datetime or time
type it will trigger an exception.

Available configuration options:

- **on** - is main option and can be **create, update, change** this tells when it
should be updated
- **field** - only valid if **on="change"** is specified, tracks property or a list of properties for changes
- **value** - only valid if **on="change"** is specified and the tracked field is a single field (not an array), if the tracked field has this **value**

**Note:** that Timestampable interface is not necessary, except in cases where
you need to identify entity as being Timestampable. The metadata is loaded only once then
cache is activated

``` php
<?php
namespace Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Article
{
    /** @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer") */
    private $id;

    /**
     * @ORM\Column(type="string", length=128)
     */
    private $title;

    /**
     * @ORM\Column(name="body", type="string")
     */
    private $body;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime")
     */
    private $updated;

    /**
     * @var \DateTime $contentChanged
     *
     * @ORM\Column(name="content_changed", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="change", field={"title", "body"})
     */
    private $contentChanged;

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

    public function setBody($body)
    {
        $this->body = $body;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function getCreated()
    {
        return $this->created;
    }

    public function getUpdated()
    {
        return $this->updated;
    }

    public function getContentChanged()
    {
        return $this->contentChanged;
    }
}
```

<a name="document-mapping"></a>

## Timestampable Document example:

``` php
<?php
namespace Document;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="articles")
 */
class Article
{
    /** @ODM\Id */
    private $id;

    /**
     * @ODM\Field(type="string")
     */
    private $title;

    /**
     * @ODM\Field(type="string")
     */
    private $body;

    /**
     * @var date $created
     *
     * @ODM\Date
     * @Gedmo\Timestampable(on="create")
     */
    private $created;

    /**
     * @var date $updated
     *
     * @ODM\Date
     * @Gedmo\Timestampable
     */
    private $updated;

    /**
     * @var \DateTime $contentChanged
     *
     * @ODM\Date
     * @Gedmo\Timestampable(on="change", field={"title", "body"})
     */
    private $contentChanged;

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

    public function setBody($body)
    {
        $this->body = $body;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function getCreated()
    {
        return $this->created;
    }

    public function getUpdated()
    {
        return $this->updated;
    }

    public function getContentChanged()
    {
        return $this->contentChanged;
    }
}
```

Now on update and creation these annotated fields will be automatically updated

<a name="yaml-mapping"></a>

## Yaml mapping example:

Yaml mapped Article: **/mapping/yaml/Entity.Article.dcm.yml**

```yaml
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
```

<a name="xml-mapping"></a>

## Xml mapping example

``` xml
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
```

<a name="advanced-examples"></a>

## Advanced examples:

### Using dependency of property changes

Add another entity which would represent Article Type:

``` php
<?php
namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Type
{
    /** @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer") */
    private $id;

    /**
     * @ORM\Column(type="string", length=128)
     */
    private $title;

    /**
     * @ORM\OneToMany(targetEntity="Article", mappedBy="type")
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
```

Now update the Article Entity to reflect published date on Type change:

``` php
<?php
namespace Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Article
{
    /** @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer") */
    private $id;

    /**
     * @ORM\Column(type="string", length=128)
     */
    private $title;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime")
     */
    private $updated;

    /**
     * @ORM\ManyToOne(targetEntity="Type", inversedBy="articles")
     */
    private $type;

    /**
     * @var \DateTime $published
     *
     * @ORM\Column(type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="change", field="type.title", value="Published")
     *
     * or for example
     * @Gedmo\Timestampable(on="change", field="type.title", value={"Published", "Closed"})
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
```

Yaml mapped Article: **/mapping/yaml/Entity.Article.dcm.yml**

``` yaml
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
```

Now few operations to get it all done:

``` php
<?php
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
```

Easy like that, any suggestions on improvements are very welcome

### Creating a UTC DateTime type that stores your datetimes in UTC

First, we define our custom data type (note the type name is datetime and the type extends DateTimeType which simply overrides the default Doctrine type):

``` php
<?php

namespace Acme\DoctrineExtensions\DBAL\Types;

use Doctrine\DBAL\Types\DateTimeType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;

class UTCDateTimeType extends DateTimeType
{
    static private $utc = null;

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }

        if (is_null(self::$utc)) {
            self::$utc = new \DateTimeZone('UTC');
        }

        $value->setTimeZone(self::$utc);

        return $value->format($platform->getDateTimeFormatString());
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }

        if (is_null(self::$utc)) {
            self::$utc = new \DateTimeZone('UTC');
        }

        $val = \DateTime::createFromFormat($platform->getDateTimeFormatString(), $value, self::$utc);

        if (!$val) {
            throw ConversionException::conversionFailed($value, $this->getName());
        }

        return $val;
    }
}
```

Now in Symfony2, we register and override the **datetime** type. **WARNING:** this will override the **datetime** type for all your entities and for all entities in external bundles or extensions, so if you have some entities that require the standard **datetime** type from Doctrine, you must modify the above type and use a different name (such as **utcdatetime**). Additionally, you'll need to modify **Timestampable** so that it includes **utcdatetime** as a valid type.

``` yaml
doctrine:
    dbal:
        types:
            datetime: Acme\DoctrineExtensions\DBAL\Types\UTCDateTimeType
```

And our Entity properties look as expected:

``` php
<?php
/**
 * @var \DateTime $dateCreated
 *
 * @ORM\Column(name="date_created", type="datetime")
 * @Gedmo\Timestampable(on="create")
 */
private $dateCreated;

/**
 * @var \DateTime $dateLastModified
 *
 * @Gedmo\Timestampable(on="update")
 * @ORM\Column(name="date_last_modified", type="datetime")
 */
private $dateLastModified;
```

Now, in our view (suppose we are using Symfony2 and Twig), we can display the datetime (which is persisted in UTC format) in our user's time zone:

``` twig
{{ myEntity.dateCreated | date("d/m/Y g:i a", app.user.timezone) }}
```

Or if the user does not have a timezone, we could expand that to use a system/app/PHP default timezone.

[doctrine_custom_datetime_type]: http://www.doctrine-project.org/docs/orm/2.0/en/cookbook/working-with-datetime.html#handling-different-timezones-with-the-datetime-type "Handling different Timezones with the DateTime Type"

This example is based off [Handling different Timezones with the DateTime Type][doctrine_custom_datetime_type] - however that example may be outdated because it contains some obviously invalid PHP from the TimeZone class.

<a name="traits"></a>

## Traits

You can use timestampable traits for quick **createdAt** **updatedAt** timestamp definitions
when using annotation mapping.
There is also a trait without annotations for easy integration purposes.

**Note:** this feature is only available since php **5.4.0**. And you are not required
to use the Traits provided by extensions.

``` php
<?php
namespace Timestampable\Fixture;

use Gedmo\Timestampable\Traits\TimestampableEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class UsingTrait
{
    /**
     * Hook timestampable behavior
     * updates createdAt, updatedAt fields
     */
    use TimestampableEntity;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(length=128)
     */
    private $title;
}
```

Traits are very simple and if you use different field names I recommend to simply create your
own ones based per project. These ones are standing as an example.
