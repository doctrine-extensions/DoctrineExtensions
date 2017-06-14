# Blameable behavior extension for Doctrine 2

**Blameable** behavior will automate the update of username or user reference fields
on your Entities or Documents. It works through annotations and can update
fields on creation, update, property subset update, or even on specific property value change.

This is very similar to Timestampable but sets a string or user object for a user association.

If you map the blame onto a string field, this extension will try to assign the user name.
If you map the blame onto a association field, this extension will try to assign the user
object to it.

Note that you need to set the user on the BlameableListener (unless you use the
Symfony2 extension which does automatically assign the current security context
user).


Features:

- Automatic predefined user field update on creation, update, property subset update, and even on record property changes
- ORM and ODM support using same listener
- Specific annotations for properties, and no interface required
- Can react to specific property or relation changes to specific value
- Can be nested with other behaviors
- Annotation, Yaml and Xml mapping support for extensions


**Symfony:**

- **Blameable** is available as [Bundle](http://github.com/stof/StofDoctrineExtensionsBundle)
for **Symfony2**, together with all other extensions

This article will cover the basic installation and functionality of **Blameable** behavior

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

## Blameable Entity example:

### Blameable annotations:
- **@Gedmo\Mapping\Annotation\Blameable** this annotation tells that this column is blameable
by default it updates this column on update. If column is not a string field or an association
it will trigger an exception.

Available configuration options:

- **on** - is main option and can be **create, update, change** this tells when it
should be updated
- **field** - only valid if **on="change"** is specified, tracks property or a list of properties for changes
- **value** - only valid if **on="change"** is specified and the tracked field is a single field (not an array), if the tracked field has this **value**
then it updates the blame

**Note:** that Blameable interface is not necessary, except in cases there
you need to identify entity as being Blameable. The metadata is loaded only once then
cache is activated

Column is a string field:

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
     * @var string $createdBy
     *
     * @Gedmo\Blameable(on="create")
     * @ORM\Column(type="string")
     */
    private $createdBy;

    /**
     * @var string $updatedBy
     *
     * @Gedmo\Blameable(on="update")
     * @ORM\Column(type="string")
     */
    private $updatedBy;

    /**
     * @var string $contentChangedBy
     *
     * @ORM\Column(name="content_changed_by", type="string", nullable=true)
     * @Gedmo\Blameable(on="change", field={"title", "body"})
     */
    private $contentChangedBy;

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

    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    public function getUpdatedBy()
    {
        return $this->updatedBy;
    }

    public function getContentChangedBy()
    {
        return $this->contentChangedBy;
    }
}
```

Column is an association:

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
     * @ODM\String
     */
    private $body;

    /**
     * @var User $createdBy
     *
     * @Gedmo\Blameable(on="create")
     * @ORM\ManyToOne(targetEntity="Path\To\Entity\User")
     * @ORM\JoinColumn(name="created_by", referencedColumnName="id")
     */
    private $createdBy;

    /**
     * @var User $updatedBy
     *
     * @Gedmo\Blameable(on="update")
     * @ORM\ManyToOne(targetEntity="Path\To\Entity\User")
     * @ORM\JoinColumn(name="updated_by", referencedColumnName="id")
     */
    private $updatedBy;

    /**
     * @var User $contentChangedBy
     *
     * @Gedmo\Blameable(on="change", fields={"title", "body"})
     * @ORM\ManyToOne(targetEntity="Path\To\Entity\User")
     * @ORM\JoinColumn(name="content_changed_by", referencedColumnName="id")
     */
    private $contentChangedBy;

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

    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    public function getUpdatedBy()
    {
        return $this->updatedBy;
    }

    public function getContentChangedBy()
    {
        return $this->contentChangedBy;
    }
}
```

<a name="document-mapping"></a>

## Blameable Document example:

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
     * @var string $createdBy
     *
     * @ODM\Field(type="string")
     * @Gedmo\Blameable(on="create")
     */
    private $createdBy;

    /**
     * @var string $updatedBy
     *
     * @ODM\Field(type="string")
     * @Gedmo\Blameable
     */
    private $updatedBy;

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

    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    public function getUpdatedBy()
    {
        return $this->updatedBy;
    }
}
```

Now on update and creation these annotated fields will be automatically updated

<a name="yaml-mapping"></a>

## Yaml mapping example:

Yaml mapped Article: **/mapping/yaml/Entity.Article.dcm.yml**

```
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
    createdBy:
      type: string
      gedmo:
        blameable:
          on: create
    updatedBy:
      type: string
      gedmo:
        blameable:
          on: update
```

<a name="xml-mapping"></a>

## Xml mapping example

``` xml
<?xml version="1.0" encoding="UTF-8"?>
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
                  xmlns:gedmo="http://gediminasm.org/schemas/orm/doctrine-extensions-mapping">

    <entity name="Mapping\Fixture\Xml\Blameable" table="blameables">
        <id name="id" type="integer" column="id">
            <generator strategy="AUTO"/>
        </id>

        <field name="createdBy" type="string">
            <gedmo:blameable on="create"/>
        </field>
        <field name="updatedBy" type="string">
            <gedmo:blameable on="update"/>
        </field>
        <field name="publishedBy" type="string" nullable="true">
            <gedmo:blameable on="change" field="status.title" value="Published"/>
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

Now update the Article Entity to reflect publishedBy on Type change:

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
     * @var string $createdBy
     *
     * @Gedmo\Blameable(on="create")
     * @ORM\Column(type="string")
     */
    private $createdBy;

    /**
     * @var string $updatedBy
     *
     * @Gedmo\Blameable(on="update")
     * @ORM\Column(type="string")
     */
    private $updatedBy;

    /**
     * @ORM\ManyToOne(targetEntity="Type", inversedBy="articles")
     */
    private $type;

    /**
     * @var string $publishedBy
     *
     * @ORM\Column(type="string", nullable=true)
     * @Gedmo\Blameable(on="change", field="type.title", value="Published")
     */
    private $publishedBy;

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

    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    public function getUpdatedBy()
    {
        return $this->updatedBy;
    }

    public function getPublishedBy()
    {
        return $this->publishedBy;
    }
}
```

Yaml mapped Article: **/mapping/yaml/Entity.Article.dcm.yml**

```
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
    createdBy:
      type: string
      gedmo:
        blameable:
          on: create
    updatedBy:
      type: string
      gedmo:
        blameable:
          on: update
    publishedBy:
      type: string
      gedmo:
        blameable:
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
// article: $createdBy, $updatedBy were set

$type = new Type;
$type->setTitle('Published');

$article = $em->getRepository('Entity\Article')->findByTitle('My Article');
$article->setType($type);

$em->persist($article);
$em->persist($type);
$em->flush();
// article: $publishedBy, $updatedBy were set

$article->getPublishedBy(); // the user that published this article
```

Easy like that, any suggestions on improvements are very welcome


<a name="traits"></a>

## Traits

You can use blameable traits for quick **createdBy** **updatedBy** string definitions
when using annotation mapping.
There is also a trait without annotations for easy integration purposes.

**Note:** this feature is only available since php **5.4.0**. And you are not required
to use the Traits provided by extensions.

``` php
<?php
namespace Blameable\Fixture;

use Gedmo\Blameable\Traits\BlameableEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class UsingTrait
{
    /**
     * Hook blameable behavior
     * updates createdBy, updatedBy fields
     */
    use BlameableEntity;

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

The Traits are very simplistic - if you use different field names it is recommended to simply create your
own Traits specific to your project. The ones provided by this bundle can be used as example.
